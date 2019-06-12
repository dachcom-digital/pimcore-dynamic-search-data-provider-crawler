<?php

namespace DsWebCrawlerBundle\EventSubscriber;

use DynamicSearchBundle\Context\ContextDataInterface;
use DynamicSearchBundle\Logger\LoggerInterface;

interface EventSubscriberInterface extends \Symfony\Component\EventDispatcher\EventSubscriberInterface
{
    /**
     * @param LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger);

    /**
     * @param ContextDataInterface $contextData
     */
    public function setContextData(ContextDataInterface $contextData);
}