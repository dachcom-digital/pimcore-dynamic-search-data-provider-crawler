<?php

namespace DsWebCrawlerBundle\Service;

use DynamicSearchBundle\Normalizer\Resource\ResourceMetaInterface;

interface CrawlerServiceInterface
{
    /**
     * @param string $contextName
     * @param string $contextDispatchType
     * @param array  $providerConfiguration
     */
    public function initFullCrawl(string $contextName, string $contextDispatchType, array $providerConfiguration);

    /**
     * @param ResourceMetaInterface $resourceMeta
     * @param string                $contextName
     * @param string                $contextDispatchType
     * @param array                 $providerConfiguration
     */
    public function initSingleCrawl(ResourceMetaInterface $resourceMeta, string $contextName, string $contextDispatchType, array $providerConfiguration);

    /**
     * @return void
     */
    public function process();

}