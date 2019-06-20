<?php

namespace DsWebCrawlerBundle\EventSubscriber;

use DynamicSearchBundle\Logger\LoggerInterface;

interface EventSubscriberInterface extends \Symfony\Component\EventDispatcher\EventSubscriberInterface
{
    /**
     * @param LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger);

    /**
     * @param string $contextName
     */
    public function setContextName(string $contextName);
}