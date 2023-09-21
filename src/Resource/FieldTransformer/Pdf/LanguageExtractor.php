<?php

namespace DsWebCrawlerBundle\Resource\FieldTransformer\Pdf;

use DynamicSearchBundle\Resource\Container\ResourceContainerInterface;
use DynamicSearchBundle\Resource\FieldTransformerInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LanguageExtractor implements FieldTransformerInterface
{
    protected array $options;

    public function configureOptions(OptionsResolver $resolver): void
    {
    }

    public function setOptions(array $options): void
    {
        $this->options = $options;
    }

    public function transformData(string $dispatchTransformerName, ResourceContainerInterface $resourceContainer): ?string
    {
        if (!$resourceContainer->hasResource()) {
            return null;
        }

        $value = 'all';
        if ($resourceContainer->hasAttribute('asset_meta')) {
            $assetMeta = $resourceContainer->getAttribute('asset_meta');
            if (is_array($assetMeta) && is_string($assetMeta['language'])) {
                $value = $assetMeta['language'];
            }
        }

        return $value;
    }
}
