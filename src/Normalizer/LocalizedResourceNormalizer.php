<?php

namespace DsWebCrawlerBundle\Normalizer;

use DynamicSearchBundle\Context\ContextDefinitionInterface;
use DynamicSearchBundle\Exception\NormalizerException;
use DynamicSearchBundle\Normalizer\Resource\NormalizedDataResource;
use DynamicSearchBundle\Normalizer\Resource\ResourceMeta;
use DynamicSearchBundle\Normalizer\Resource\ResourceMetaInterface;
use DynamicSearchBundle\Resource\Container\ResourceContainerInterface;
use Pimcore\Model\Asset;
use Pimcore\Model\DataObject;
use Pimcore\Model\Document;
use Symfony\Component\OptionsResolver\OptionsResolver;
use VDB\Spider\Resource as SpiderResource;

class LocalizedResourceNormalizer extends AbstractResourceNormalizer
{
    protected array $options;

    public static function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired(['locales', 'skip_not_localized_documents']);
        $resolver->setAllowedTypes('locales', ['string[]', 'null']);
        $resolver->setAllowedTypes('skip_not_localized_documents', ['bool']);
        $resolver->setDefaults(['skip_not_localized_documents' => true]);
        $resolver->setDefaults(['locales' => null]);
    }

    public function setOptions(array $options): void
    {
        $this->options = $options;
    }

    protected function normalizePage(ContextDefinitionInterface $contextDefinition, ResourceContainerInterface $resourceContainer): array
    {
        /** @var Document $document */
        $document = $resourceContainer->getResource();

        // @todo: localized hardlink data detection!
        // @todo: Related document detection! (some content parts could be inherited)
        // @todo: How to handle Snippets?

        $documentLocale = $document->getProperty('language');

        if (empty($documentLocale)) {
            if ($this->options['skip_not_localized_documents'] === false) {
                throw new NormalizerException(sprintf('Cannot determinate locale aware document id "%s": no language property given.', $document->getId()));
            } else {
                return [];
            }
        }

        $documentId = sprintf('%s_%s_%d', 'document', $documentLocale, $document->getId());
        $path = $document->getRealFullPath();
        $resourceMeta = new ResourceMeta($documentId, $document->getId(), 'document', $document->getType(), null, ['path' => $path], ['locale' => $documentLocale]);

        return [new NormalizedDataResource($resourceContainer, $resourceMeta)];
    }

    protected function normalizeAsset(ContextDefinitionInterface $contextDefinition, ResourceContainerInterface $resourceContainer): array
    {
        /** @var Asset $asset */
        $asset = $resourceContainer->getResource();

        $documentId = sprintf('%s_%d', 'asset', $asset->getId());
        $path = $asset->getRealFullPath();
        $resourceMeta = new ResourceMeta($documentId, $asset->getId(), 'asset', $asset->getType(), null, ['path' => $path], ['locale' => null]);

        return [new NormalizedDataResource($resourceContainer, $resourceMeta)];
    }

    protected function normalizeDataObject(ContextDefinitionInterface $contextDefinition, ResourceContainerInterface $resourceContainer): array
    {
        /** @var DataObject\Concrete $object */
        $object = $resourceContainer->getResource();

        $linkGenerator = $object->getClass()->getLinkGenerator();
        if (!$linkGenerator instanceof DataObject\ClassDefinition\LinkGeneratorInterface) {
            throw new NormalizerException(sprintf('no link generator for object "%d" found. cannot re-crawl.', $object->getId()));
        }

        $normalizedResources = [];
        foreach ($this->getLocales() as $locale) {
            $documentId = sprintf('%s_%s_%d', 'object', $locale, $object->getId());
            $path = $linkGenerator->generate($object, ['_locale' => $locale]);
            $normalizedResources[] = new NormalizedDataResource(
                $resourceContainer,
                new ResourceMeta(
                    $documentId,
                    $object->getId(),
                    'object',
                    $object->getType(),
                    $object->getClassName(),
                    ['path' => $path],
                    ['locale' => $locale]
                )
            );
        }

        return $normalizedResources;
    }

    protected function generateResourceMetaFromHtmlResource(SpiderResource $resource): ?ResourceMetaInterface
    {
        $crawler = $resource->getCrawler();
        $stream = $resource->getResponse()->getBody();
        $stream->rewind();

        $resourceId = null;
        $resourceCollectionType = null;
        $resourceType = null;
        $resourceSubType = null;

        $objectQuery = '//meta[@name="dynamic-search:object-id"]';
        $objectTypeQuery = '//meta[@name="dynamic-search:object-type"]';
        $objectSubTypeQuery = '//meta[@name="dynamic-search:object-sub-type"]';
        $pageQuery = '//meta[@name="dynamic-search:page-id"]';

        if ($crawler->filterXpath($objectQuery)->count() > 0) {
            $resourceCollectionType = 'object';
            $resourceType = 'object';
            $resourceId = (string) $crawler->filterXpath($objectQuery)->attr('content');
            if ($crawler->filterXpath($objectTypeQuery)->count() > 0) {
                $resourceType = (string) $crawler->filterXpath($objectTypeQuery)->attr('content');
            }
            if ($crawler->filterXpath($objectSubTypeQuery)->count() > 0) {
                $resourceSubType = (string) $crawler->filterXpath($objectSubTypeQuery)->attr('content');
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
            if ($this->options['skip_not_localized_documents'] === false) {
                throw new NormalizerException(sprintf('Cannot determinate locale aware document id "%s": no language property given.', $resourceId));
            } else {
                return null;
            }
        }

        if (empty($contentLanguage)) {
            return null;
        }

        $documentId = sprintf('%s_%s_%d', $resourceCollectionType, $contentLanguage, $resourceId);

        return new ResourceMeta($documentId, $resourceId, $resourceCollectionType, $resourceType, $resourceSubType);
    }

    protected function generateResourceMetaFromPdfResource(array $resourceAttributes): ?ResourceMetaInterface
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

    protected function getLocales(): array
    {
        if ($this->options['locales'] === null) {
            return \Pimcore\Tool::getValidLanguages();
        }

        return $this->options['locales'];
    }
}
