<?php

namespace DsWebCrawlerBundle\Normalizer;

use DynamicSearchBundle\Context\ContextDataInterface;
use DynamicSearchBundle\Exception\NormalizerException;
use DynamicSearchBundle\Exception\OmitResourceException;
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
use Pimcore\Model\Document;
use Pimcore\Model\Document\Page;
use Pimcore\Model\Element\ElementInterface;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\OptionsResolver\OptionsResolver;
use VDB\Spider\Resource as SpiderResource;

class LocalizedResourceNormalizer implements ResourceNormalizerInterface
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
        $resolver->setRequired('locales');
        $resolver->setAllowedTypes('locales', ['string[]']);
        $resolver->setDefaults(['locales' => \Pimcore\Tool::getValidLanguages()]);
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
     * @throws NormalizerException
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
            throw new NormalizerException(sprintf('Unable to load data provider "%s".', $contextData->getDataProviderName()));
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
     * @throws NormalizerException
     * @throws OmitResourceException
     */
    protected function normalizePage(ContextDataInterface $contextData, ResourceContainerInterface $resourceContainer, DataProviderInterface $dataProvider)
    {
        /** @var Document $document */
        $document = $resourceContainer->getResource();

        // @todo: Hardlink data detection!
        // @todo: Related document detection! (some content parts could be inherited)

        if ($contextData->getContextDispatchType() === ContextDataInterface::CONTEXT_DISPATCH_TYPE_UPDATE) {
            $this->executeCrawl($dataProvider, $contextData, $document->getRealFullPath());

            throw new OmitResourceException();

        }

        // => deleted resource, just generate resource meta

        $documentLocale = $document->getProperty('language');

        if (empty($documentLocale)) {
            throw new NormalizerException(sprintf('Cannot determinate locale aware document id "%s": no language property given.', $document->getId()));
        }

        $documentId = sprintf('%s_%s_%d', 'document', $documentLocale, $document->getId());
        $resourceMeta = new ResourceMeta($documentId, $document->getId(), 'document', $document->getType(), ['locale' => $documentLocale]);
        return [new NormalizedDataResource($resourceContainer, $resourceMeta)];
    }

    /**
     * @param ContextDataInterface       $contextData
     * @param ResourceContainerInterface $resourceContainer
     * @param DataProviderInterface      $dataProvider
     *
     * @return array
     * @throws NormalizerException
     * @throws OmitResourceException
     */
    protected function normalizeAsset(ContextDataInterface $contextData, ResourceContainerInterface $resourceContainer, DataProviderInterface $dataProvider)
    {
        /** @var Asset $asset */
        $asset = $resourceContainer->getResource();

        if ($contextData->getContextDispatchType() === ContextDataInterface::CONTEXT_DISPATCH_TYPE_UPDATE) {
            $this->executeCrawl($dataProvider, $contextData, $asset->getRealFullPath());

            throw new OmitResourceException();

        }

        // => deleted resource, just generate resource meta

        $documentId = sprintf('%s_%d', 'asset', $asset->getId());
        $resourceMeta = new ResourceMeta($documentId, $asset->getId(), 'asset', $asset->getType(), ['locale' => null]);
        return [new NormalizedDataResource($resourceContainer, $resourceMeta)];

    }

    /**
     * @param ContextDataInterface       $contextData
     * @param ResourceContainerInterface $resourceContainer
     * @param DataProviderInterface      $dataProvider
     *
     * @return array
     * @throws NormalizerException
     * @throws OmitResourceException
     */
    protected function normalizeDataObject(ContextDataInterface $contextData, ResourceContainerInterface $resourceContainer, DataProviderInterface $dataProvider)
    {
        /** @var DataObject\Concrete $object */
        $object = $resourceContainer->getResource();

        if ($contextData->getContextDispatchType() === ContextDataInterface::CONTEXT_DISPATCH_TYPE_UPDATE) {

            /** @var DataObject\ClassDefinition\LinkGeneratorInterface $linkGenerator */
            $linkGenerator = $object->getClass()->getLinkGenerator();
            if ($linkGenerator instanceof DataObject\ClassDefinition\LinkGeneratorInterface) {
                foreach ($this->options['locales'] as $locale) {
                    $this->executeCrawl($dataProvider, $contextData, $linkGenerator->generate($object, ['_locale' => $locale]));
                }
            } else {
                throw new NormalizerException(sprintf('no link generator for object "%d" found. cannot recrawl.', $object->getId()));
            }

            throw new OmitResourceException();

        }

        // => deleted resource, just generate resource meta

        $normalizedResources = [];
        foreach ($this->options['locales'] as $locale) {
            $documentId = sprintf('%s_%s_%d', 'object', $locale, $object->getId());
            $resourceMeta = new ResourceMeta($documentId, $object->getId(), 'object', $object->getType(), ['locale' => $locale]);
            $normalizedResources[] = new NormalizedDataResource(null, $resourceMeta);
        }

        return $normalizedResources;

    }

    /**
     * @param ContextDataInterface       $contextData
     * @param ResourceContainerInterface $resourceContainer
     *
     * @return array
     */
    protected function normalizeSpiderResource(ContextDataInterface $contextData, ResourceContainerInterface $resourceContainer)
    {
        $resourceMeta = null;
        if ($resourceContainer->hasAttribute('html')) {
            $resourceMeta = $this->generateResourceMetaFromHtmlResource($resourceContainer->getResource());
        } elseif ($resourceContainer->hasAttribute('pdf_content')) {
            $resourceMeta = $this->generateResourceMetaFromPdfResource($resourceContainer->getAttributes());
        }

        if ($resourceMeta === null) {
            return [];
        }

        return [new NormalizedDataResource($resourceContainer, $resourceMeta)];
    }

    /**
     * @param SpiderResource $resource
     *
     * @return ResourceMetaInterface|null
     */
    protected function generateResourceMetaFromHtmlResource(SpiderResource $resource)
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
        $objectTypeQuery = '//meta[@name="dynamic-search:object-type"]';
        $pageQuery = '//meta[@name="dynamic-search:page-id"]';

        if ($crawler->filterXpath($objectQuery)->count() > 0) {
            $resourceCollectionType = 'object';
            $resourceType = 'object';
            $resourceId = (string) $crawler->filterXpath($objectQuery)->attr('content');
            if ($crawler->filterXpath($objectTypeQuery)->count() > 0) {
                $resourceType = (string) $crawler->filterXpath($objectTypeQuery)->attr('content');
            }
        } elseif ($crawler->filterXpath($pageQuery)->count() > 0) {
            $resourceCollectionType = 'document';
            $resourceType = 'page';
            $resourceId = (string) $crawler->filterXpath($pageQuery)->attr('content');
        }

        if (empty($resourceId)) {
            return null;
        }

        $contentLanguage = $resource->getResponse()->getHeaderLine('Content-Language');
        $contentLanguage = strtolower(str_replace('-', '_', $contentLanguage));

        if (empty($contentLanguage)) {
            return null;
        }

        $documentId = sprintf('%s_%s_%d', $resourceCollectionType, $contentLanguage, $resourceId);

        return new ResourceMeta($documentId, $resourceId, $resourceCollectionType, $resourceType, ['locale' => $contentLanguage]);

    }

    /**
     * @param array $resourceAttributes
     *
     * @return ResourceMetaInterface|null
     */
    public function generateResourceMetaFromPdfResource(array $resourceAttributes)
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

        return new ResourceMeta($documentId, $resourceId, $resourceCollectionType, $resourceType, ['locale' => null]);

    }

    /**
     * @param DataProviderInterface $dataProvider
     * @param ContextDataInterface  $contextData
     * @param string                $path
     *
     * @throws NormalizerException
     */
    protected function executeCrawl(DataProviderInterface $dataProvider, ContextDataInterface $contextData, string $path)
    {
        /** @var ContextDataInterface $newContext */
        $newContext = clone $contextData;
        $newContext->updateRuntimeValue('path', $path);

        try {
            $dataProvider->execute($newContext);
        } catch (\Throwable $e) {
            throw new NormalizerException(sprintf('Error while re-crawling path "%s". Error was: %s', $path, $e->getMessage()));
        }
    }

}