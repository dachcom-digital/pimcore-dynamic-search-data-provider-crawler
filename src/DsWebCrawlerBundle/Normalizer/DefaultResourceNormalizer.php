<?php

namespace DsWebCrawlerBundle\Normalizer;

use DynamicSearchBundle\Context\ContextDefinitionInterface;
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
     * {@inheritdoc}
     */
    public static function configureOptions(OptionsResolver $resolver)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function setOptions(array $options)
    {
        $this->options = $options;
    }

    /**
     * {@inheritdoc}
     */
    protected function normalizePage(ContextDefinitionInterface $contextDefinition, ResourceContainerInterface $resourceContainer)
    {
        /** @var Document $document */
        $document = $resourceContainer->getResource();

        // @todo: localized hardlink data detection!
        // @todo: Related document detection! (some content parts could be inherited)
        // @todo: How to handle Snippets?

        $documentId = sprintf('%s_%d', 'document', $document->getId());
        $path = $document->getRealFullPath();
        $resourceMeta = new ResourceMeta($documentId, $document->getId(), 'document', $document->getType(), null, ['path' => $path]);

        return [new NormalizedDataResource($resourceContainer, $resourceMeta)];
    }

    /**
     * {@inheritdoc}
     */
    protected function normalizeAsset(ContextDefinitionInterface $contextDefinition, ResourceContainerInterface $resourceContainer)
    {
        /** @var Asset $asset */
        $asset = $resourceContainer->getResource();

        $documentId = sprintf('%s_%d', 'asset', $asset->getId());
        $path = $asset->getRealFullPath();
        $resourceMeta = new ResourceMeta($documentId, $asset->getId(), 'asset', $asset->getType(), null, ['path' => $path]);

        return [new NormalizedDataResource($resourceContainer, $resourceMeta)];
    }

    /**
     * {@inheritdoc}
     */
    protected function normalizeDataObject(ContextDefinitionInterface $contextDefinition, ResourceContainerInterface $resourceContainer)
    {
        /** @var DataObject\Concrete $object */
        $object = $resourceContainer->getResource();

        /** @var DataObject\ClassDefinition\LinkGeneratorInterface $linkGenerator */
        $linkGenerator = $object->getClass()->getLinkGenerator();
        if ($linkGenerator instanceof DataObject\ClassDefinition\LinkGeneratorInterface) {
            $documentId = sprintf('%s_%d', 'object', $object->getId());
            $path = $linkGenerator->generate($object);
            $resourceMeta = new ResourceMeta($documentId, $object->getId(), 'object', $object->getType(), $object->getClassName(), ['path' => $path]);
            $normalizedResources = new NormalizedDataResource(null, $resourceMeta);
        } else {
            throw new NormalizerException(sprintf('no link generator for object "%d" found. cannot recrawl.', $object->getId()));
        }

        return $normalizedResources;
    }

    /**
     * {@inheritdoc}
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

        return new ResourceMeta($documentId, $resourceId, $resourceCollectionType, $resourceType, null);
    }

    /**
     * {@inheritdoc}
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

        return new ResourceMeta($documentId, $resourceId, $resourceCollectionType, $resourceType, null);
    }
}
