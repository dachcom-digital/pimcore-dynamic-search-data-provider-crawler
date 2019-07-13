<?php

namespace DsWebCrawlerBundle\Normalizer;

use DynamicSearchBundle\Context\ContextDataInterface;
use DynamicSearchBundle\Exception\NormalizerException;
use DynamicSearchBundle\Normalizer\Resource\NormalizedDataResource;
use DynamicSearchBundle\Normalizer\Resource\ResourceMeta;
use DynamicSearchBundle\Resource\Container\ResourceContainerInterface;
use Pimcore\Model\Asset;
use Pimcore\Model\DataObject;
use Pimcore\Model\Document;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\OptionsResolver\OptionsResolver;
use VDB\Spider\Resource as SpiderResource;

class DefaultResourceNormalizer extends AbstractResourceNormalizer
{
    /**
     * @var array
     */
    protected $options;

    /**
     * {@inheritDoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
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
    protected function normalizePage(ContextDataInterface $contextData, ResourceContainerInterface $resourceContainer)
    {
        /** @var Document $document */
        $document = $resourceContainer->getResource();

        if ($contextData->getContextDispatchType() === ContextDataInterface::CONTEXT_DISPATCH_TYPE_UPDATE) {

            // @todo: Related document detection! (some content parts could be inherited)

            $this->executeCrawl($dataProvider, $contextData, $document->getRealFullPath());

        }

        if ($contextData->getContextDispatchType() === ContextDataInterface::CONTEXT_DISPATCH_TYPE_DELETE) {

            // @todo: Related document detection! (some content parts could be inherited)

            $documentId = sprintf('%s_%d', 'document', $document->getId());
            $resourceMeta = new ResourceMeta($documentId, $document->getId(), 'document', $document->getType());

            return [new NormalizedDataResource($resourceContainer, $resourceMeta)];

        }

        return [];

    }

    /**
     * {@inheritDoc}
     */
    protected function normalizeAsset(ContextDataInterface $contextData, ResourceContainerInterface $resourceContainer)
    {
        /** @var Asset $asset */
        $asset = $resourceContainer->getResource();

        if ($contextData->getContextDispatchType() === ContextDataInterface::CONTEXT_DISPATCH_TYPE_UPDATE) {
            $this->executeCrawl($dataProvider, $contextData, $asset->getRealFullPath());
        }

        if ($contextData->getContextDispatchType() === ContextDataInterface::CONTEXT_DISPATCH_TYPE_DELETE) {

            $documentId = sprintf('%s_%d', 'asset', $asset->getId());
            $resourceMeta = new ResourceMeta($documentId, $asset->getId(), 'asset', $asset->getType());

            return [new NormalizedDataResource($resourceContainer, $resourceMeta)];
        }

        return [];

    }

    /**
     * {@inheritDoc}
     */
    protected function normalizeDataObject(ContextDataInterface $contextData, ResourceContainerInterface $resourceContainer)
    {
        /** @var DataObject\Concrete $object */
        $object = $resourceContainer->getResource();

        if ($contextData->getContextDispatchType() === ContextDataInterface::CONTEXT_DISPATCH_TYPE_UPDATE) {

            /** @var DataObject\ClassDefinition\LinkGeneratorInterface $linkGenerator */
            $linkGenerator = $object->getClass()->getLinkGenerator();
            if ($linkGenerator instanceof DataObject\ClassDefinition\LinkGeneratorInterface) {
                $this->executeCrawl($dataProvider, $contextData, $linkGenerator->generate($object));
            } else {
                throw new NormalizerException(sprintf('no link generator for object "%d" found. cannot recrawl.', $object->getId()));
            }
        }

        if ($contextData->getContextDispatchType() === ContextDataInterface::CONTEXT_DISPATCH_TYPE_DELETE) {

            $normalizedResources = [];
            $documentId = sprintf('%s_%d', 'object', $object->getId());
            $resourceMeta = new ResourceMeta($documentId, $object->getId(), 'object', $object->getType());
            $normalizedResources[] = new NormalizedDataResource(null, $resourceMeta);

            return $normalizedResources;
        }

        return [];

    }

    /**
     * {@inheritDoc}
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

        $documentId = sprintf('%s_%d', $resourceCollectionType, $resourceId);

        return new ResourceMeta($documentId, $resourceId, $resourceCollectionType, $resourceType);

    }

    /**
     * {@inheritDoc}
     */
    protected function generateResourceMetaFromPdfResource(array $resourceAttributes)
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
}