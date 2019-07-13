<?php

namespace DsWebCrawlerBundle\Resource\FieldTransformer\Html;

use DynamicSearchBundle\Resource\Container\ResourceContainerInterface;
use DynamicSearchBundle\Resource\FieldTransformerInterface;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\OptionsResolver\OptionsResolver;
use VDB\Spider\Resource;

class MetaExtractor implements FieldTransformerInterface
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
        $resolver->setRequired('name');
        $resolver->setAllowedTypes('name', ['string']);
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

        /** @var Crawler $crawler */
        $crawler = $resource->getCrawler();
        $stream = $resource->getResponse()->getBody();
        $stream->rewind();

        $query = sprintf('//meta[@name="%s"]', $this->options['name']);
        if ($crawler->filterXpath($query)->count() === 0) {
            return null;
        }

        $value = (string) $crawler->filterXpath($query)->attr('content');

        return $value;

    }
}