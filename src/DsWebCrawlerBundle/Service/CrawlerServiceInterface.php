<?php

namespace DsWebCrawlerBundle\Service;

use DynamicSearchBundle\Logger\LoggerInterface;

interface CrawlerServiceInterface
{
    /**
     * @param LoggerInterface $logger
     * @param string          $contextName
     * @param string          $contextDispatchType
     * @param array           $providerConfiguration
     * @param array           $runtimeOptions
     */
    public function init(LoggerInterface $logger, string $contextName, string $contextDispatchType, array $providerConfiguration, array $runtimeOptions = []);

    public function process();

}