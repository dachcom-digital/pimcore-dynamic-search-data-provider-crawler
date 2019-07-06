<?php

namespace DsWebCrawlerBundle\Normalizer;

use DynamicSearchBundle\Context\ContextDataInterface;
use DynamicSearchBundle\Exception\RuntimeException;
use DynamicSearchBundle\Manager\DataManagerInterface;
use DynamicSearchBundle\Manager\TransformerManagerInterface;
use DynamicSearchBundle\Normalizer\Resource\NormalizedDataResource;
use DynamicSearchBundle\Normalizer\ResourceNormalizerInterface;
use DynamicSearchBundle\Provider\DataProviderInterface;
use DynamicSearchBundle\Transformer\Container\ResourceContainerInterface;
use Pimcore\Model\Asset;
use Pimcore\Model\DataObject;
use Pimcore\Model\Document\Page;
use Pimcore\Model\Element\ElementInterface;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\OptionsResolver\OptionsResolver;
use VDB\Spider\Resource as SpiderResource;

class ResourceNormalizer implements ResourceNormalizerInterface
{
    /**
     * @var array
     */
    protected $options;

    /**
     * @var TransformerManagerInterface
     */
    protected $transformerManager;

    /**
     * @var DataManagerInterface
     */
    protected $dataManager;

    /**
     * @param TransformerManagerInterface $transformerManager
     * @param DataManagerInterface        $dataManager
     */
    public function __construct(
        TransformerManagerInterface $transformerManager,
        DataManagerInterface $dataManager
    ) {
        $this->transformerManager = $transformerManager;
        $this->dataManager = $dataManager;
    }

    /**
     * {@inheritDoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(['locale_aware_resources' => false]);
        $resolver->setRequired('locale_aware_resources');
    }

    /**
     * {@inheritDoc}
     */
    public function setOptions(array $options)
    {
        $this->options = $options;
    }

    /**
     * {@inheritDoc}
     */
    public function normalizeToResourceStack(ContextDataInterface $contextData, ResourceContainerInterface $resourceContainer): array
    {
        if ($resourceContainer->getResource() instanceof SpiderResource) {
            $resourceId = $this->generateResourceId($resourceContainer);
            return [new NormalizedDataResource($resourceContainer, $resourceId)];
        } elseif ($contextData->getContextDispatchType() === ContextDataInterface::CONTEXT_DISPATCH_TYPE_DELETE) {
            return $this->onDeletion($resourceContainer);
        } elseif ($contextData->getContextDispatchType() === ContextDataInterface::CONTEXT_DISPATCH_TYPE_UPDATE) {
            return $this->onUpdate($resourceContainer, $contextData);
        }

        return [];
    }

    /**
     * @param ResourceContainerInterface $resourceContainer
     *
     * @return array
     */
    protected function onDeletion(ResourceContainerInterface $resourceContainer)
    {
        $resource = $resourceContainer->getResource();

        if (!$resource instanceof ElementInterface) {
            return [];
        }

        if ($resource instanceof Page) {

            // @todo: Hardlink data detection!

            $buildOptions = [];
            if ($this->options['locale_aware_resources'] === true) {
                $documentLocale = $resource->getProperty('language');
                if (empty($documentLocale)) {
                    throw new RuntimeException(sprintf('Cannot determinate locale aware document id "%s": no language property given.', $resource->getId()));
                } else {
                    $buildOptions['locale'] = $documentLocale;
                }
            }

            $resourceId = $this->generateResourceId($resourceContainer, $buildOptions);

            return [new NormalizedDataResource(null, $resourceId)];
        }

        if ($resource instanceof Asset) {

            $resourceId = $this->generateResourceId($resourceContainer);

            return [new NormalizedDataResource(null, $resourceId)];
        }

        if ($resource instanceof DataObject) {

            $normalizedResources = [];
            if ($this->options['locale_aware_resources'] === true) {
                foreach (\Pimcore\Tool::getValidLanguages() as $language) {
                    $resourceId = $this->generateResourceId($resourceContainer, ['locale' => $language]);
                    $normalizedResources[] = new NormalizedDataResource(null, $resourceId);
                }
            } else {
                $resourceId = $this->generateResourceId($resourceContainer);
                $normalizedResources[] = new NormalizedDataResource(null, $resourceId);
            }

            return $normalizedResources;
        }

    }

    /**
     * @param ResourceContainerInterface $resourceContainer
     * @param ContextDataInterface       $contextData
     *
     * @return array
     */
    protected function onUpdate(ResourceContainerInterface $resourceContainer, ContextDataInterface $contextData)
    {
        $resource = $resourceContainer->getResource();

        if (!$resource instanceof ElementInterface) {
            return [];
        }

        try {
            $dataProvider = $this->dataManager->getDataProvider($contextData);
        } catch (\Throwable $e) {
            throw new RuntimeException(sprintf('Unable to load data provider "%s".', $contextData->getDataProviderName()));
        }

        if ($resource instanceof Page) {

            // @todo: Hardlink data detection!

            $this->executeCrawl($dataProvider, $contextData, $resource->getRealFullPath());
            return [];
        }

        if ($resource instanceof DataObject) {

            /** @var DataObject\ClassDefinition\LinkGeneratorInterface $linkGenerator */
            $linkGenerator = $resource->getClass()->getLinkGenerator();
            if ($linkGenerator instanceof DataObject\ClassDefinition\LinkGeneratorInterface) {
                if ($this->options['locale_aware_resources'] === true) {
                    foreach (\Pimcore\Tool::getValidLanguages() as $language) {
                        $this->executeCrawl($dataProvider, $contextData, $linkGenerator->generate($resource, ['_locale' => $language]));
                    }
                } else {
                    $this->executeCrawl($dataProvider, $contextData, $linkGenerator->generate($resource));
                }
            } else {
                throw new RuntimeException(sprintf('no link generator for object "%d" found. cannot recrawl.', $resource->getId()));
            }
        }

        return [];

    }

    protected function executeCrawl(DataProviderInterface $dataProvider, ContextDataInterface $contextData, string $path)
    {
        /** @var ContextDataInterface $newContext */
        $newContext = clone $contextData;
        $newContext->updateRuntimeValue('path', $path);
        $dataProvider->execute($newContext);
    }

    /**
     * @param ResourceContainerInterface $resourceContainer
     * @param array                      $buildOptions
     *
     * @return string|null
     */
    protected function generateResourceId(ResourceContainerInterface $resourceContainer, array $buildOptions = [])
    {
        if ($resourceContainer->getResource() instanceof SpiderResource) {
            if ($resourceContainer->hasAttribute('html')) {
                return $this->generateResourceIdFromHtmlResource($resourceContainer->getResource());
            } elseif ($resourceContainer->hasAttribute('pdf_content')) {
                return $this->generateResourceIdFromPdfResource($resourceContainer->getAttributes());
            } else {
                return null;
            }
        } elseif ($resourceContainer->getResource() instanceof ElementInterface) {
            return $this->generateResourceIdFromPimcoreResource($resourceContainer->getResource(), $buildOptions);
        }

        return null;
    }

    /**
     * @param SpiderResource $resource
     *
     * @return string|null
     */
    protected function generateResourceIdFromHtmlResource(SpiderResource $resource)
    {
        /** @var Crawler $crawler */
        $crawler = $resource->getCrawler();
        $stream = $resource->getResponse()->getBody();
        $stream->rewind();

        $value = null;
        $documentType = null;

        $objectQuery = '//meta[@name="dynamic-search:object-id"]';
        $pageQuery = '//meta[@name="dynamic-search:page-id"]';

        if ($crawler->filterXpath($objectQuery)->count() > 0) {
            $documentType = 'object';
            $value = (string) $crawler->filterXpath($objectQuery)->attr('content');
        } elseif ($crawler->filterXpath($pageQuery)->count() > 0) {
            $documentType = 'page';
            $value = (string) $crawler->filterXpath($pageQuery)->attr('content');
        }

        if (empty($value)) {
            return null;
        }

        if ($this->options['locale_aware_resources'] === true) {
            $contentLanguage = $resource->getResponse()->getHeaderLine('Content-Language');
            $contentLanguage = strtolower(str_replace('-', '_', $contentLanguage));

            if (empty($contentLanguage)) {
                return null;
            }

            return sprintf('%s_%s_%d', $documentType, $contentLanguage, $value);
        }

        return sprintf('%s_%d', $documentType, $value);

    }

    /**
     * @param array $resourceAttributes
     *
     * @return string|null
     */
    public function generateResourceIdFromPdfResource(array $resourceAttributes)
    {
        $assetMeta = $resourceAttributes['asset_meta'];

        if (empty($assetMeta)) {
            return null;
        }

        $value = null;
        if (!empty($assetMeta['id'])) {
            $value = $assetMeta['id'];
        }

        if ($value === null) {
            return null;
        }

        $value = sprintf('asset_%d', $value);

        return $value;

    }

    /**
     * @param ElementInterface $resource
     * @param array            $buildOptions
     *
     * @return string|null
     */
    protected function generateResourceIdFromPimcoreResource(ElementInterface $resource, array $buildOptions)
    {
        $locale = isset($buildOptions['locale']) ? $buildOptions['locale'] : null;
        $documentType = null;
        $id = null;

        if ($resource instanceof DataObject) {
            $documentType = 'object';
            $id = $resource->getId();
        } elseif ($resource instanceof Page) {
            $documentType = 'page';
            $id = $resource->getId();
        } elseif ($resource instanceof Asset\Document) {
            $documentType = 'asset';
            $id = $resource->getId();
            $locale = null;
        }

        if ($documentType === null) {
            return null;
        }

        if ($locale !== null) {
            return sprintf('%s_%s_%d', $documentType, $locale, $id);
        }

        return sprintf('%s_%d', $documentType, $id);

    }
}