<?php

namespace DsWebCrawlerBundle\EventSubscriber;

use DynamicSearchBundle\Logger\LoggerInterface;
use DynamicSearchBundle\Normalizer\Resource\ResourceMetaInterface;

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
     * @param string $crawlType
     */
    public function setCrawlType(string $crawlType);

    /**
     * @param ResourceMetaInterface|null $resourceMeta
     */
    public function setResourceMeta(?ResourceMetaInterface $resourceMeta);
}
