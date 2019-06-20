<?php

namespace DsWebCrawlerBundle\EventSubscriber;

use DsWebCrawlerBundle\DsWebCrawlerEvents;
use DynamicSearchBundle\DynamicSearchEvents;
use DynamicSearchBundle\Event\NewDataEvent;
use DynamicSearchBundle\EventDispatcher\DynamicSearchEventDispatcherInterface;
use DynamicSearchBundle\Logger\LoggerInterface;
use Symfony\Component\EventDispatcher\GenericEvent;
use VDB\Spider\Resource;

class NotifyEventSubscriber implements EventSubscriberInterface
{
    /**
     * @var string
     */
    protected $contextName;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var DynamicSearchEventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @param DynamicSearchEventDispatcherInterface $eventDispatcher
     */
    public function __construct(DynamicSearchEventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @param string $contextName
     */
    public function setContextName(string $contextName)
    {
        $this->contextName = $contextName;
    }

    /**
     * @param LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            DsWebCrawlerEvents::DS_WEB_CRAWLER_VALID_RESOURCE_DOWNLOADED => 'notifyPersisted',
        ];
    }

    /**
     * @param GenericEvent $event
     */
    public function notifyPersisted(GenericEvent $event)
    {
        /** @var \SplFileObject $resource */
        $resource = $event->getArgument('resource');

        if (!file_exists($resource->getRealPath())) {
            return;
        }

        $resourceContent = file_get_contents($resource->getRealPath());

        /** @var \VDB\Spider\Resource $rawContent */
        $rawContent = unserialize($resourceContent);

        if(!$rawContent instanceof Resource) {
            return;
        }

        $newDataEvent = new NewDataEvent($this->contextName, $rawContent);

        $this->eventDispatcher->dispatch(DynamicSearchEvents::NEW_DATA_AVAILABLE, $newDataEvent);

    }
}