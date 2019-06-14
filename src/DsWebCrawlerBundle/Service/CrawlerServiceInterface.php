<?php

namespace DsWebCrawlerBundle\Service;

use DynamicSearchBundle\Context\ContextDataInterface;
use DynamicSearchBundle\Logger\LoggerInterface;

interface CrawlerServiceInterface
{
    /**
     * @param LoggerInterface      $logger
     * @param ContextDataInterface $contextData
     */
    public function init(LoggerInterface $logger, ContextDataInterface $contextData);

    public function process();

}