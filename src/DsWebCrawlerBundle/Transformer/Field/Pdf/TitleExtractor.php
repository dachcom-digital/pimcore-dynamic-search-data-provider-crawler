<?php

namespace DsWebCrawlerBundle\Transformer\Field\Pdf;

use DynamicSearchBundle\Transformer\Container\DocumentContainerInterface;
use DynamicSearchBundle\Transformer\Container\FieldContainer;
use DynamicSearchBundle\Transformer\Container\FieldContainerInterface;
use DynamicSearchBundle\Transformer\FieldTransformerInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use VDB\Spider\Resource;

class TitleExtractor implements FieldTransformerInterface
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
        return false;
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
    public function transformData(string $dispatchTransformerName, DocumentContainerInterface $transformedData): ?FieldContainerInterface
    {
        if (!$transformedData->hasResource()) {
            return null;
        }

        /** @var Resource $resource */
        $resource = $transformedData->getResource();

        $value = null;
        if ($transformedData->hasAttribute('asset_meta')) {
            $assetMeta = $transformedData->getAttribute('asset_meta');
            if (is_array($assetMeta) && is_string($assetMeta['key'])) {
                $value = $assetMeta['key'];
            }
        }

        if ($value === null) {
            $value = basename($resource->getUri()->toString());
        }

        return new FieldContainer($value);

    }
}