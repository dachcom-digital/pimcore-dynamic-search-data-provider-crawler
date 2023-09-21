<?php

namespace DsWebCrawlerBundle\EventSubscriber;

use DsWebCrawlerBundle\DsWebCrawlerBundle;
use DsWebCrawlerBundle\DsWebCrawlerEvents;
use DynamicSearchBundle\DynamicSearchEvents;
use DynamicSearchBundle\Event\ErrorEvent;
use DynamicSearchBundle\EventDispatcher\DynamicSearchEventDispatcherInterface;
use DynamicSearchBundle\Logger\LoggerInterface;
use DynamicSearchBundle\Normalizer\Resource\ResourceMetaInterface;
use Symfony\Contracts\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\GenericEvent;
use VDB\Spider\Event\SpiderEvents;

class AbortEventSubscriber implements EventSubscriberInterface
{
    protected bool $dispatched;
    protected string $contextName;
    protected string $contextDispatchType;
    protected string $crawlType;
    protected ?ResourceMetaInterface $resourceMeta = null;
    protected LoggerInterface $logger;
    protected DynamicSearchEventDispatcherInterface $eventDispatcher;

    public function __construct(DynamicSearchEventDispatcherInterface $eventDispatcher)
    {
        $this->dispatched = false;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function setContextName(string $contextName): void
    {
        $this->contextName = $contextName;
    }

    public function setContextDispatchType(string $contextDispatchType): void
    {
        $this->contextDispatchType = $contextDispatchType;
    }

    public function setCrawlType(string $crawlType): void
    {
        $this->crawlType = $crawlType;
    }

    public function setResourceMeta(?ResourceMetaInterface $resourceMeta): void
    {
        $this->resourceMeta = $resourceMeta;
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            SpiderEvents::SPIDER_CRAWL_USER_STOPPED  => 'stoppedCrawler',
            DsWebCrawlerEvents::DS_WEB_CRAWLER_ERROR => 'errorCrawler',
        ];
    }

    public function stoppedCrawler(Event $event): void
    {
        // only trigger once.
        if ($this->dispatched === true) {
            return;
        }

        $this->dispatched = true;
        $newDataEvent = new ErrorEvent($this->contextName, 'crawler has been stopped by user', DsWebCrawlerBundle::PROVIDER_NAME);
        $this->eventDispatcher->dispatch($newDataEvent, DynamicSearchEvents::ERROR_DISPATCH_ABORT);
    }

    public function errorCrawler(GenericEvent $event): void
    {
        // only trigger once.
        if ($this->dispatched === true) {
            return;
        }

        $this->dispatched = true;
        $errorEvent = new ErrorEvent($this->contextName, $event->getArgument('message'), DsWebCrawlerBundle::PROVIDER_NAME, $event->getArgument('exception'));
        $this->eventDispatcher->dispatch($errorEvent, DynamicSearchEvents::ERROR_DISPATCH_CRITICAL);
    }
}
