<?php

namespace DsWebCrawlerBundle\Transformer\Field\Html;

use DynamicSearchBundle\Transformer\Container\ResourceContainerInterface;
use DynamicSearchBundle\Transformer\FieldTransformerInterface;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\OptionsResolver\OptionsResolver;
use VDB\Spider\Resource;

class TitleExtractor implements FieldTransformerInterface
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

        /** @var Crawler $crawler */
        $crawler = $resource->getCrawler();
        $stream = $resource->getResponse()->getBody();
        $stream->rewind();

        if ($crawler->filterXpath('//title')->count() === 0) {
            return null;
        }

        $value = (string) $crawler->filterXpath('//title')->text();

        return $value;

    }
}