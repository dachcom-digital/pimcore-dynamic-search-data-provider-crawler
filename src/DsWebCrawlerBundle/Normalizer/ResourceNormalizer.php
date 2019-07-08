<?php

namespace DsWebCrawlerBundle\Normalizer;

use DynamicSearchBundle\Context\ContextDataInterface;
use DynamicSearchBundle\Exception\RuntimeException;
use DynamicSearchBundle\Manager\DataManagerInterface;
use DynamicSearchBundle\Manager\TransformerManagerInterface;
use DynamicSearchBundle\Normalizer\Resource\NormalizedDataResource;
use DynamicSearchBundle\Normalizer\Resource\ResourceMeta;
use DynamicSearchBundle\Normalizer\Resource\ResourceMetaInterface;
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
            return $this->normalizeSpiderResource($contextData, $resourceContainer);
        } else {
            return $this->normalizePimcoreResource($contextData, $resourceContainer);
        }
    }

    /**
     * @param ContextDataInterface       $contextData
     * @param ResourceContainerInterface $resourceContainer
     *
     * @return array
     */
    protected function normalizeSpiderResource(ContextDataInterface $contextData, ResourceContainerInterface $resourceContainer)
    {
        $dataResource = $this->generateDataResource($resourceContainer);
        if ($dataResource !== null) {
            return [$dataResource];
        }

        return [];
    }

    /**
     * @param ContextDataInterface       $contextData
     * @param ResourceContainerInterface $resourceContainer
     *
     * @return array
     */
    protected function normalizePimcoreResource(ContextDataInterface $contextData, ResourceContainerInterface $resourceContainer)
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
            return $this->normalizePage($contextData, $resourceContainer, $dataProvider);
        }

        if ($resource instanceof Asset) {
            return $this->normalizeAsset($contextData, $resourceContainer, $dataProvider);
        }

        if ($resource instanceof DataObject) {
            return $this->normalizeDataObject($contextData, $resourceContainer, $dataProvider);
        }

        return [];

    }

    /**
     * @param ContextDataInterface       $contextData
     * @param ResourceContainerInterface $resourceContainer
     * @param DataProviderInterface      $dataProvider
     *
     * @return array
     */
    protected function normalizePage(ContextDataInterface $contextData, ResourceContainerInterface $resourceContainer, DataProviderInterface $dataProvider)
    {
        $resource = $resourceContainer->getResource();
        $reCrawlData = $contextData->getContextDispatchType() === ContextDataInterface::CONTEXT_DISPATCH_TYPE_UPDATE;

        // @todo: Hardlink data detection!
        // @todo: Related document detection! (some content parts could be inherited)

        if ($reCrawlData === true) {
            $this->executeCrawl($dataProvider, $contextData, $resource->getRealFullPath());
            return [];
        }

        $buildOptions = [];
        if ($this->options['locale_aware_resources'] === true) {
            $documentLocale = $resource->getProperty('language');
            if (empty($documentLocale)) {
                throw new RuntimeException(sprintf('Cannot determinate locale aware document id "%s": no language property given.', $resource->getId()));
            } else {
                $buildOptions['locale'] = $documentLocale;
            }
        }

        $dataResource = $this->generateDataResource($resourceContainer, $buildOptions);
        if ($dataResource !== null) {
            return [$dataResource];
        }

        return [];
    }

    /**
     * @param ContextDataInterface       $contextData
     * @param ResourceContainerInterface $resourceContainer
     * @param DataProviderInterface      $dataProvider
     *
     * @return array
     */
    protected function normalizeAsset(ContextDataInterface $contextData, ResourceContainerInterface $resourceContainer, DataProviderInterface $dataProvider)
    {
        $resource = $resourceContainer->getResource();
        $reCrawlData = $contextData->getContextDispatchType() === ContextDataInterface::CONTEXT_DISPATCH_TYPE_UPDATE;

        if ($reCrawlData === true) {
            $this->executeCrawl($dataProvider, $contextData, $resource->getRealFullPath());
            return [];
        }

        $dataResource = $this->generateDataResource($resourceContainer);
        if ($dataResource !== null) {
            return [$dataResource];
        }

        return [];

    }

    /**
     * @param ContextDataInterface       $contextData
     * @param ResourceContainerInterface $resourceContainer
     * @param DataProviderInterface      $dataProvider
     *
     * @return array
     */
    protected function normalizeDataObject(ContextDataInterface $contextData, ResourceContainerInterface $resourceContainer, DataProviderInterface $dataProvider)
    {
        $resource = $resourceContainer->getResource();
        $reCrawlData = $contextData->getContextDispatchType() === ContextDataInterface::CONTEXT_DISPATCH_TYPE_UPDATE;

        if ($reCrawlData === true) {

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

            return [];

        }

        $normalizedResources = [];
        if ($this->options['locale_aware_resources'] === true) {
            foreach (\Pimcore\Tool::getValidLanguages() as $language) {
                $dataResource = $this->generateDataResource($resourceContainer, ['locale' => $language]);
                if ($dataResource !== null) {
                    $normalizedResources[] = $dataResource;
                }
            }
        } else {
            $dataResource = $this->generateDataResource($resourceContainer);
            if ($dataResource !== null) {
                $normalizedResources[] = $dataResource;
            }
        }

        return $normalizedResources;

    }

    /**
     * @param DataProviderInterface $dataProvider
     * @param ContextDataInterface  $contextData
     * @param string                $path
     */
    protected function executeCrawl(DataProviderInterface $dataProvider, ContextDataInterface $contextData, string $path)
    {
        /** @var ContextDataInterface $newContext */
        $newContext = clone $contextData;
        $newContext->updateRuntimeValue('path', $path);

        try {
            $dataProvider->execute($newContext);
        } catch (\Throwable $e) {
            throw new RuntimeException(sprintf('Error while re-crawling path "%s". Error was: %s', $path, $e->getMessage()));
        }
    }

    /**
     * @param ResourceContainerInterface $resourceContainer
     * @param array                      $buildOptions
     *
     * @return null|NormalizedDataResource
     */
    protected function generateDataResource(ResourceContainerInterface $resourceContainer, array $buildOptions = [])
    {
        $resourceMeta = null;

        if ($resourceContainer->getResource() instanceof SpiderResource) {
            if ($resourceContainer->hasAttribute('html')) {
                $resourceMeta = $this->generateResourceIdFromHtmlResource($resourceContainer->getResource());
            } elseif ($resourceContainer->hasAttribute('pdf_content')) {
                $resourceMeta = $this->generateResourceIdFromPdfResource($resourceContainer->getAttributes());
            } else {
                return null;
            }
        } elseif ($resourceContainer->getResource() instanceof ElementInterface) {
            $resourceMeta = $this->generateResourceIdFromPimcoreResource($resourceContainer->getResource(), $buildOptions);
        }

        if ($resourceMeta === null) {
            return null;
        }

        return new NormalizedDataResource($resourceContainer, $resourceMeta, $buildOptions);
    }

    /**
     * @param SpiderResource $resource
     *
     * @return ResourceMetaInterface|null
     */
    protected function generateResourceIdFromHtmlResource(SpiderResource $resource)
    {
        /** @var Crawler $crawler */
        $crawler = $resource->getCrawler();
        $stream = $resource->getResponse()->getBody();
        $stream->rewind();

        $documentId = null;
        $resourceId = null;
        $resourceCollectionType = null;
        $resourceType = null;

        $objectQuery = '//meta[@name="dynamic-search:object-id"]';
        $pageQuery = '//meta[@name="dynamic-search:page-id"]';

        if ($crawler->filterXpath($objectQuery)->count() > 0) {
            $resourceCollectionType = 'object';
            $resourceType = 'object'; // @todo: determinate object type?
            $resourceId = (string) $crawler->filterXpath($objectQuery)->attr('content');
        } elseif ($crawler->filterXpath($pageQuery)->count() > 0) {
            $resourceCollectionType = 'document';
            $resourceType = 'page';
            $resourceId = (string) $crawler->filterXpath($pageQuery)->attr('content');
        }

        if (empty($resourceId)) {
            return null;
        }

        if ($this->options['locale_aware_resources'] === true) {
            $contentLanguage = $resource->getResponse()->getHeaderLine('Content-Language');
            $contentLanguage = strtolower(str_replace('-', '_', $contentLanguage));

            if (empty($contentLanguage)) {
                return null;
            }

            $documentId = sprintf('%s_%s_%d', $resourceCollectionType, $contentLanguage, $resourceId);
        } else {
            $documentId = sprintf('%s_%d', $resourceCollectionType, $resourceId);
        }

        return new ResourceMeta($documentId, $resourceId, $resourceCollectionType, $resourceType);

    }

    /**
     * @param array $resourceAttributes
     *
     * @return ResourceMetaInterface|null
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

        if (empty($value)) {
            return null;
        }

        $resourceId = $value;
        $resourceCollectionType = 'asset';
        $resourceType = 'document';
        $documentId = sprintf('asset_%d', $value);

        return new ResourceMeta($documentId, $resourceId, $resourceCollectionType, $resourceType);

    }

    /**
     * @param ElementInterface $resource
     * @param array            $buildOptions
     *
     * @return ResourceMetaInterface|null
     */
    protected function generateResourceIdFromPimcoreResource(ElementInterface $resource, array $buildOptions)
    {
        $locale = isset($buildOptions['locale']) ? $buildOptions['locale'] : null;

        $documentId = null;
        $resourceId = null;
        $resourceCollectionType = null;
        $resourceType = null;

        if ($resource instanceof DataObject) {
            $resourceCollectionType = 'object';
            $resourceType = $resource->getType();
            $resourceId = $resource->getId();
        } elseif ($resource instanceof Asset) {
            $resourceCollectionType = 'asset';
            $resourceType = $resource->getType();
            $resourceId = $resource->getId();
        } elseif ($resource instanceof Page) {
            $resourceCollectionType = 'page';
            $resourceType = $resource->getType();
            $resourceId = $resource->getId();
        }

        if ($resourceCollectionType === null) {
            return null;
        }

        if ($locale !== null) {
            $documentId = sprintf('%s_%s_%d', $resourceCollectionType, $locale, $resourceId);
        } else {
            $documentId = sprintf('%s_%d', $resourceCollectionType, $resourceId);
        }

        return new ResourceMeta($documentId, $resourceId, $resourceCollectionType, $resourceType);

    }
}