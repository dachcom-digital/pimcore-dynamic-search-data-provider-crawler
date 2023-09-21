<?php

namespace DsWebCrawlerBundle\Spider\Discoverer;

use Symfony\Component\DomCrawler\Crawler;
use VDB\Spider\Discoverer\CrawlerDiscoverer;
use VDB\Spider\Resource;

class XPathExpressionDiscoverer extends CrawlerDiscoverer
{
    protected function getFilteredCrawler(Resource $resource): Crawler
    {
        return $resource->getCrawler()->filterXPath($this->selector);
    }
}
