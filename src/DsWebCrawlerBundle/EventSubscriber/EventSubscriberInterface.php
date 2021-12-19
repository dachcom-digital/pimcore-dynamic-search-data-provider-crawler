<?php

namespace DsWebCrawlerBundle\EventSubscriber;

use DynamicSearchBundle\Logger\LoggerInterface;
use DynamicSearchBundle\Normalizer\Resource\ResourceMetaInterface;

interface EventSubscriberInterface extends \Symfony\Component\EventDispatcher\EventSubscriberInterface
{
    public function setLogger(LoggerInterface $logger): void;

    public function setContextName(string $contextName): void;

    public function setContextDispatchType(string $contextDispatchType): void;

    public function setCrawlType(string $crawlType): void;

    public function setResourceMeta(?ResourceMetaInterface $resourceMeta): void;
}
