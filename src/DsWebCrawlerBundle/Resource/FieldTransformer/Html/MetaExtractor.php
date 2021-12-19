<?php

namespace DsWebCrawlerBundle\Resource\FieldTransformer\Html;

use DynamicSearchBundle\Resource\Container\ResourceContainerInterface;
use DynamicSearchBundle\Resource\FieldTransformerInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use VDB\Spider\Resource as SpiderResource;

class MetaExtractor implements FieldTransformerInterface
{
    protected array $options;

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired('name');
        $resolver->setAllowedTypes('name', ['string']);
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

        /** @var SpiderResource $resource */
        $resource = $resourceContainer->getResource();

        $crawler = $resource->getCrawler();
        $stream = $resource->getResponse()->getBody();
        $stream->rewind();

        $query = sprintf('//meta[@name="%s"]', $this->options['name']);
        if ($crawler->filterXpath($query)->count() === 0) {
            return null;
        }

        return (string) $crawler->filterXpath($query)->attr('content');
    }
}
