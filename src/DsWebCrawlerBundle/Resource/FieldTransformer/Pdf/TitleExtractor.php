<?php

namespace DsWebCrawlerBundle\Resource\FieldTransformer\Pdf;

use DynamicSearchBundle\Resource\Container\ResourceContainerInterface;
use DynamicSearchBundle\Resource\FieldTransformerInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use VDB\Spider\Resource as SpiderResource;

class TitleExtractor implements FieldTransformerInterface
{
    /**
     * @var array
     */
    protected $options;

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        return false;
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
    public function transformData(string $dispatchTransformerName, ResourceContainerInterface $resourceContainer)
    {
        if (!$resourceContainer->hasResource()) {
            return null;
        }

        /** @var SpiderResource $resource */
        $resource = $resourceContainer->getResource();

        $value = null;
        if ($resourceContainer->hasAttribute('asset_meta')) {
            $assetMeta = $resourceContainer->getAttribute('asset_meta');
            if (is_array($assetMeta) && is_string($assetMeta['key'])) {
                $value = $assetMeta['key'];
            }
        }

        if ($value === null) {
            $value = basename($resource->getUri()->toString());
        }

        return $value;
    }
}
