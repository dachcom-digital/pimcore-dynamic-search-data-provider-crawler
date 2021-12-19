<?php

namespace DsWebCrawlerBundle\Resource\FieldTransformer\Html;

use DynamicSearchBundle\Resource\Container\ResourceContainerInterface;
use DynamicSearchBundle\Resource\FieldTransformerInterface;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\OptionsResolver\OptionsResolver;
use VDB\Spider\Resource as SpiderResource;

class HtmlTagExtractor implements FieldTransformerInterface
{
    protected array $options;

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired(['tag', 'return_multiple']);

        $resolver->setAllowedTypes('tag', ['string']);
        $resolver->setAllowedTypes('return_multiple', ['boolean']);

        $resolver->setDefaults([
            'tag'             => 'h1',
            'return_multiple' => false
        ]);
    }

    public function setOptions(array $options): void
    {
        $this->options = $options;
    }

    public function transformData(string $dispatchTransformerName, ResourceContainerInterface $resourceContainer): mixed
    {
        if (!$resourceContainer->hasAttribute('resource')) {
            return null;
        }

        /** @var SpiderResource $resource */
        $resource = $resourceContainer->getAttribute('resource');

        /** @var Crawler $crawler */
        $crawler = $resource->getCrawler();
        $stream = $resource->getResponse()->getBody();
        $stream->rewind();

        $query = sprintf('//%s', $this->options['tag']);
        if ($crawler->filterXpath($query)->count() === 0) {
            return null;
        }

        $elements = $crawler->filterXpath($query);

        $tagElements = $elements->each(function (Crawler $node) {
            return trim(preg_replace('/\s+/', ' ', $node->text()));
        });

        if ($this->options['return_multiple'] === true) {
            $value = $tagElements;
        } else {
            $value = $tagElements[0];
        }

        return $value;
    }
}
