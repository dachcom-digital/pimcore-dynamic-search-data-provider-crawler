<?php

namespace DsWebCrawlerBundle\Resource\FieldTransformer\Pdf;

use DynamicSearchBundle\Resource\Container\ResourceContainerInterface;
use DynamicSearchBundle\Resource\FieldTransformerInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TextExtractor implements FieldTransformerInterface
{
    protected array $options;

    public function configureOptions(OptionsResolver $resolver): void
    {
    }

    public function setOptions(array $options): void
    {
        $this->options = $options;
    }

    public function transformData(string $dispatchTransformerName, ResourceContainerInterface $resourceContainer): mixed
    {
        $value = null;
        if ($resourceContainer->hasAttribute('pdf_content')) {
            $value = $resourceContainer->getAttribute('pdf_content');
        }

        if (empty($value)) {
            return null;
        }

        return $value;
    }
}
