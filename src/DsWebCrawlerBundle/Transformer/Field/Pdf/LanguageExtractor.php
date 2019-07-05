<?php

namespace DsWebCrawlerBundle\Transformer\Field\Pdf;

use DynamicSearchBundle\Transformer\Container\DocumentContainerInterface;
use DynamicSearchBundle\Transformer\Container\FieldContainer;
use DynamicSearchBundle\Transformer\Container\FieldContainerInterface;
use DynamicSearchBundle\Transformer\FieldTransformerInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LanguageExtractor implements FieldTransformerInterface
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

        $value = 'all';
        if ($transformedData->hasAttribute('asset_meta')) {
            $assetMeta = $transformedData->getAttribute('asset_meta');
            if (is_array($assetMeta) && is_string($assetMeta['language'])) {
                $value = $assetMeta['language'];
            }
        }

        return new FieldContainer($value);
    }
}