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

    /**
     * @param string $contextDispatchType
     */
    public function setContextDispatchType(string $contextDispatchType);

    /**
     * @param array $runtimeValues
     */
    public function setRuntimeValues(array $runtimeValues = []);
}