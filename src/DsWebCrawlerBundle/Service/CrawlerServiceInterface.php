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
     * @param array           $runtimeValues
     */
    public function init(LoggerInterface $logger, string $contextName, string $contextDispatchType, array $providerConfiguration, array $runtimeValues = []);

    public function process();

}