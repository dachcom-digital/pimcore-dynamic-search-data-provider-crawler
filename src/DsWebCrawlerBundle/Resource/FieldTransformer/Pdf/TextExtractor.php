<?php

namespace DsWebCrawlerBundle\Resource\FieldTransformer\Pdf;

use DynamicSearchBundle\Resource\Container\ResourceContainerInterface;
use DynamicSearchBundle\Resource\FieldTransformerInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TextExtractor implements FieldTransformerInterface
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