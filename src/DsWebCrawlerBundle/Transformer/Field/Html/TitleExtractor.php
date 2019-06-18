<?php

namespace DsWebCrawlerBundle\Transformer\Field\Html;

use DynamicSearchBundle\Transformer\Container\DataContainerInterface;
use DynamicSearchBundle\Transformer\Container\FieldContainer;
use DynamicSearchBundle\Transformer\Container\FieldContainerInterface;
use DynamicSearchBundle\Transformer\FieldTransformerInterface;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\OptionsResolver\OptionsResolver;
use VDB\Spider\Resource;

class TitleExtractor implements FieldTransformerInterface
{
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
    public function transformData(array $options, string $dispatchTransformerName, DataContainerInterface $transformedData): ?FieldContainerInterface
    {
        if (!$transformedData->hasDataAttribute('resource')) {
            return null;
        }

         /** @var Resource $resource */
        $resource = $transformedData->getDataAttribute('resource');

        /** @var Crawler $crawler */
        $crawler = $resource->getCrawler();
        $stream = $resource->getResponse()->getBody();
        $stream->rewind();

        if ($crawler->filterXpath('//title')->count() === 0) {
            return null;
        }

        $value = $crawler->filterXpath('//title')->text();

        return new FieldContainer($value);

    }
}