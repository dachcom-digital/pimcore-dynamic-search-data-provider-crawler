<?php

namespace DsWebCrawlerBundle\Normalizer;

use DynamicSearchBundle\Manager\TransformerManagerInterface;
use DynamicSearchBundle\Normalizer\ResourceIdBuilderInterface;
use DynamicSearchBundle\Transformer\Container\DocumentContainerInterface;
use Pimcore\Model\DataObject;
use Pimcore\Model\Document\Page;
use Pimcore\Model\Element\ElementInterface;
use Symfony\Component\DomCrawler\Crawler;
use VDB\Spider\Resource as SpiderResource;

class ResourceIdBuilder implements ResourceIdBuilderInterface
{
    /**
     * @var TransformerManagerInterface
     */
    protected $transformerManager;

    /**
     * @var array
     */
    protected $normalizerOptions;

    /**
     * @param TransformerManagerInterface $transformerManager
     */
    public function __construct(TransformerManagerInterface $transformerManager)
    {
        $this->transformerManager = $transformerManager;
    }

    /**
     * {@inheritDoc}
     */
    public function setOptions(array $normalizerOptions)
    {
        $this->normalizerOptions = $normalizerOptions;
    }

    /**
     * {@inheritDoc}
     */
    public function build(DocumentContainerInterface $documentContainer, array $buildOptions = [])
    {
        if ($documentContainer->getResource() instanceof SpiderResource) {
            if ($documentContainer->hasAttribute('html')) {
                return $this->buildFromHtmlResource($documentContainer->getResource());
            } elseif ($documentContainer->hasAttribute('pdf_content')) {
                return $this->buildFromPdfResource($documentContainer->getAttributes());
            } else {
                return null;
            }
        } elseif ($documentContainer->getResource() instanceof ElementInterface) {
            return $this->buildFromPimcoreResource($documentContainer->getResource(), $buildOptions);
        }

        return null;
    }

    /**
     * @param SpiderResource $resource
     *
     * @return string|null
     */
    protected function buildFromHtmlResource(SpiderResource $resource)
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

        if ($this->normalizerOptions['locale_aware_resources'] === true) {
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
    public function buildFromPdfResource(array $resourceAttributes)
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
    protected function buildFromPimcoreResource(ElementInterface $resource, array $buildOptions)
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
