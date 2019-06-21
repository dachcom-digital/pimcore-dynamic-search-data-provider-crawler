<?php

namespace DsWebCrawlerBundle\Transformer\Field\Html;

use DynamicSearchBundle\Transformer\Container\DataContainerInterface;
use DynamicSearchBundle\Transformer\Container\FieldContainer;
use DynamicSearchBundle\Transformer\Container\FieldContainerInterface;
use DynamicSearchBundle\Transformer\FieldTransformerInterface;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\OptionsResolver\OptionsResolver;
use VDB\Spider\Resource;

class DocumentIdExtractor implements FieldTransformerInterface
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

        $value = null;
        $documentType = null;

        $objectQuery = '//meta[@name="dynamic-search:object-id"]';
        $pageQuery = '//meta[@name="dynamic-search:page-id"]';

        if ($crawler->filterXpath($objectQuery)->count() > 0) {
            $documentType = 'object';
            $value = (string) $crawler->filterXpath($objectQuery)->attr('content');
        } elseif ($crawler->filterXpath($pageQuery)->count() > 0) {
            $documentType = 'page';
            $value = (string) $crawler->filterXpath($pageQuery)->attr('content');
        }

        if (empty($value)) {
            return null;
        }

        $value = sprintf('%s_%d', $documentType, $value);

        return new FieldContainer($value);

    }
}