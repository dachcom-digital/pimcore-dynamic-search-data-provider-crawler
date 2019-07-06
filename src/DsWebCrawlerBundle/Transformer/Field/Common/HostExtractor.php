<?php

namespace DsWebCrawlerBundle\Transformer\Field\Common;

use DynamicSearchBundle\Transformer\Container\ResourceContainerInterface;
use DynamicSearchBundle\Transformer\FieldTransformerInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use VDB\Spider\Resource;

class HostExtractor implements FieldTransformerInterface
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
    public function transformData(string $dispatchTransformerName, ResourceContainerInterface $resourceContainer)
    {
        if (!$resourceContainer->hasResource()) {
            return null;
        }

        /** @var Resource $resource */
        $resource = $resourceContainer->getResource();

        $value = $resource->getUri()->getHost();

        return $value;

    }
}