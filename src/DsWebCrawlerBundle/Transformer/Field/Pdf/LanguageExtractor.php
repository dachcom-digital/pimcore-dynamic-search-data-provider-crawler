<?php

namespace DsWebCrawlerBundle\Transformer\Field\Pdf;

use DynamicSearchBundle\Transformer\Container\DataContainerInterface;
use DynamicSearchBundle\Transformer\Container\FieldContainer;
use DynamicSearchBundle\Transformer\Container\FieldContainerInterface;
use DynamicSearchBundle\Transformer\FieldTransformerInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LanguageExtractor implements FieldTransformerInterface
{
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
    public function transformData(array $options, string $dispatchTransformerName, DataContainerInterface $transformedData): ?FieldContainerInterface
    {
        if (!$transformedData->hasDataAttribute('resource')) {
            return null;
        }

        $value = 'all';
        if ($transformedData->hasDataAttribute('asset_meta')) {
            $assetMeta = $transformedData->getDataAttribute('asset_meta');
            if (is_array($assetMeta) && is_string($assetMeta['language'])) {
                $value = $assetMeta['language'];
            }
        }

        return new FieldContainer($value);

    }
}