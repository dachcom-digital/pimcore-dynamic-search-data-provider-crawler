<?php

namespace DsWebCrawlerBundle\Transformer\Field\Common;

use DynamicSearchBundle\Transformer\Container\DataContainerInterface;
use DynamicSearchBundle\Transformer\Container\FieldContainer;
use DynamicSearchBundle\Transformer\Container\FieldContainerInterface;
use DynamicSearchBundle\Transformer\FieldTransformerInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use VDB\Spider\Resource;

class HostExtractor implements FieldTransformerInterface
{
    /**
     * {@inheritDoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
    }

    /**
     * {@inheritDoc}
     */
    public function transformData(array $options, string $dispatchTransformerName, DataContainerInterface $transformedData): ?FieldContainerInterface
    {
        if (!$transformedData->hasDataAttribute('resource')) {
            return null;
        }

        /** @var Resource $resource */
        $resource = $transformedData->getDataAttribute('resource');

        $value = $resource->getUri()->getHost();

        return new FieldContainer($value);

    }
}