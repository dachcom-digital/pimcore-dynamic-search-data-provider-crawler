<?php

namespace DsWebCrawlerBundle\EventSubscriber;

use DsWebCrawlerBundle\DsWebCrawlerEvents;
use DynamicSearchBundle\DynamicSearchEvents;
use DynamicSearchBundle\Event\NewDataEvent;
use DynamicSearchBundle\EventDispatcher\DynamicSearchEventDispatcherInterface;
use DynamicSearchBundle\Logger\LoggerInterface;
use DynamicSearchBundle\Normalizer\Resource\ResourceMetaInterface;
use Symfony\Component\EventDispatcher\GenericEvent;
use VDB\Spider\Resource;

class NotifyEventSubscriber implements EventSubscriberInterface
{
    protected string $contextName;
    protected string $contextDispatchType;
    protected string $crawlType;
    protected ?ResourceMetaInterface $resourceMeta = null;
    protected LoggerInterface $logger;
    protected DynamicSearchEventDispatcherInterface $eventDispatcher;

    public function __construct(DynamicSearchEventDispatcherInterface $eventDispatcher)
    {
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
            DsWebCrawlerEvents::DS_WEB_CRAWLER_VALID_RESOURCE_DOWNLOADED => 'notifyPersisted',
        ];
    }

    public function notifyPersisted(GenericEvent $event): void
    {
        /** @var \SplFileObject $resource */
        $resource = $event->getArgument('resource');

        if (!file_exists($resource->getRealPath())) {
            return;
        }

        $resourceContent = file_get_contents($resource->getRealPath());

        $rawContent = unserialize($resourceContent);

        if (!$rawContent instanceof Resource) {
            return;
        }

        $newDataEvent = new NewDataEvent($this->contextDispatchType, $this->contextName, $rawContent, $this->crawlType, $this->resourceMeta);

        $this->eventDispatcher->dispatch($newDataEvent, DynamicSearchEvents::NEW_DATA_AVAILABLE);
    }
}
