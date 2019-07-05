<?php

namespace DsWebCrawlerBundle\Transformer\Field\Html;

use DynamicSearchBundle\Transformer\Container\DocumentContainerInterface;
use DynamicSearchBundle\Transformer\Container\FieldContainer;
use DynamicSearchBundle\Transformer\Container\FieldContainerInterface;
use DynamicSearchBundle\Transformer\FieldTransformerInterface;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\OptionsResolver\OptionsResolver;
use VDB\Spider\Resource;

class HtmlTagExtractor implements FieldTransformerInterface
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
        $resolver->setRequired(['tag', 'return_multiple']);

        $resolver->setAllowedTypes('tag', ['string']);
        $resolver->setAllowedTypes('return_multiple', ['boolean']);

        $resolver->setDefaults([
            'tag'             => 'h1',
            'return_multiple' => false
        ]);
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
        if (!$transformedData->hasAttribute('resource')) {
            return null;
        }

        /** @var Resource $resource */
        $resource = $transformedData->getAttribute('resource');

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

        return new FieldContainer($value);

    }
}