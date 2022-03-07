<?php

namespace DsWebCrawlerBundle\Service;

use DynamicSearchBundle\Normalizer\Resource\ResourceMetaInterface;

interface CrawlerServiceInterface
{
    public function initFullCrawl(string $contextName, string $contextDispatchType, array $providerConfiguration): void;

    public function initSingleCrawl(ResourceMetaInterface $resourceMeta, string $contextName, string $contextDispatchType, array $providerConfiguration): void;

    public function process(): void;
}
