<?php

namespace DsWebCrawlerBundle\Service;

use DynamicSearchBundle\Logger\LoggerInterface;

interface CrawlerServiceInterface
{
    /**
     * @param LoggerInterface $logger
     * @param string          $contextName
     * @param array           $providerConfiguration
     */
    public function init(LoggerInterface $logger, string $contextName, array $providerConfiguration);

    public function process();

}