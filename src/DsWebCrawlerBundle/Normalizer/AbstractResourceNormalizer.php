<?php

namespace DsWebCrawlerBundle\Normalizer;

use DynamicSearchBundle\Context\ContextDataInterface;
use DynamicSearchBundle\Exception\NormalizerException;
use DynamicSearchBundle\Manager\DataManagerInterface;
use DynamicSearchBundle\Manager\TransformerManagerInterface;
use DynamicSearchBundle\Normalizer\Resource\NormalizedDataResource;
use DynamicSearchBundle\Normalizer\Resource\ResourceMetaInterface;
use DynamicSearchBundle\Normalizer\ResourceNormalizerInterface;
use DynamicSearchBundle\Resource\Container\ResourceContainerInterface;
use Pimcore\Model\Asset;
use Pimcore\Model\DataObject;
use Pimcore\Model\Document\Page;
use Pimcore\Model\Element\ElementInterface;
use VDB\Spider\Resource as SpiderResource;

abstract class AbstractResourceNormalizer implements ResourceNormalizerInterface
{
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

        if ($resource instanceof Page) {
            return $this->normalizePage($contextData, $resourceContainer);
        }

        if ($resource instanceof Asset) {
            return $this->normalizeAsset($contextData, $resourceContainer);
        }

        if ($resource instanceof DataObject) {
            return $this->normalizeDataObject($contextData, $resourceContainer);
        }

        return [];

    }

    /**
     * @param ContextDataInterface       $contextData
     * @param ResourceContainerInterface $resourceContainer
     *
     * @return array
     * @throws NormalizerException
     */
    protected abstract function normalizePage(ContextDataInterface $contextData, ResourceContainerInterface $resourceContainer);

    /**
     * @param ContextDataInterface       $contextData
     * @param ResourceContainerInterface $resourceContainer
     *
     * @return array
     * @throws NormalizerException
     */
    protected abstract function normalizeAsset(ContextDataInterface $contextData, ResourceContainerInterface $resourceContainer);

    /**
     * @param ContextDataInterface       $contextData
     * @param ResourceContainerInterface $resourceContainer
     *
     * @return array
     * @throws NormalizerException
     */
    protected abstract function normalizeDataObject(ContextDataInterface $contextData, ResourceContainerInterface $resourceContainer);

    /**
     * @param SpiderResource $resource
     *
     * @return ResourceMetaInterface|null
     * @throws NormalizerException
     */
    protected abstract function generateResourceMetaFromHtmlResource(SpiderResource $resource);

    /**
     * @param array $resourceAttributes
     *<
     * @return ResourceMetaInterface|null
     * @throws NormalizerException
     */
    protected abstract function generateResourceMetaFromPdfResource(array $resourceAttributes);

}