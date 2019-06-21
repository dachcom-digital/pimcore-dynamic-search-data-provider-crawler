<?php

namespace DsWebCrawlerBundle\Transformer\Field\Pdf;

use DynamicSearchBundle\Transformer\Container\DataContainerInterface;
use DynamicSearchBundle\Transformer\Container\FieldContainer;
use DynamicSearchBundle\Transformer\Container\FieldContainerInterface;
use DynamicSearchBundle\Transformer\FieldTransformerInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DocumentIdExtractor implements FieldTransformerInterface
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

        $value = null;
        if ($transformedData->hasDataAttribute('asset_meta')) {
            $assetMeta = $transformedData->getDataAttribute('asset_meta');
            if (is_array($assetMeta) && !empty($assetMeta['id'])) {
                $value = $assetMeta['id'];
            }
        }

        if ($value === null) {
            return null;
        }

        $value = sprintf('asset_%d', $value);

        return new FieldContainer($value);

    }
}