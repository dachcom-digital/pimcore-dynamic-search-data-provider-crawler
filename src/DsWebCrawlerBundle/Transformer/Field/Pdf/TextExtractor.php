<?php

namespace DsWebCrawlerBundle\Transformer\Field\Pdf;

use DynamicSearchBundle\Transformer\Container\DataContainerInterface;
use DynamicSearchBundle\Transformer\Container\FieldContainer;
use DynamicSearchBundle\Transformer\Container\FieldContainerInterface;
use DynamicSearchBundle\Transformer\FieldTransformerInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TextExtractor implements FieldTransformerInterface
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
        $value = null;
        if ($transformedData->hasDataAttribute('pdf_content')) {
            $value = $transformedData->getDataAttribute('pdf_content');
        }

        if (empty($value)) {
            return null;
        }

        return new FieldContainer($value);

    }
}