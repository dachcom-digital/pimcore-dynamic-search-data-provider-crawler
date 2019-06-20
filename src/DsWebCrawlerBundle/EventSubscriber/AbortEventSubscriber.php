<?php

namespace DsWebCrawlerBundle\EventSubscriber;

use DsWebCrawlerBundle\DsWebCrawlerBundle;
use DsWebCrawlerBundle\DsWebCrawlerEvents;
use DynamicSearchBundle\DynamicSearchEvents;
use DynamicSearchBundle\Event\ErrorEvent;
use DynamicSearchBundle\EventDispatcher\DynamicSearchEventDispatcherInterface;
use DynamicSearchBundle\Logger\LoggerInterface;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\GenericEvent;
use VDB\Spider\Event\SpiderEvents;

class AbortEventSubscriber implements EventSubscriberInterface
{
    /**
     * @var bool
     */
    protected $dispatched;

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
        $this->dispatched = false;
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
            SpiderEvents::SPIDER_CRAWL_USER_STOPPED  => 'stoppedCrawler',
            DsWebCrawlerEvents::DS_WEB_CRAWLER_ERROR => 'errorCrawler',
        ];
    }

    /**
     * @param Event $event
     */
    public function stoppedCrawler(Event $event)
    {
        // only trigger once.
        if ($this->dispatched === true) {
            return;
        }

        $this->dispatched = true;
        $newDataEvent = new ErrorEvent($this->contextName, 'crawler has been stopped by user', DsWebCrawlerBundle::PROVIDER_NAME);
        $this->eventDispatcher->dispatch(DynamicSearchEvents::ERROR_DISPATCH_ABORT, $newDataEvent);
    }

    /**
     * @param GenericEvent $event
     */
    public function errorCrawler(GenericEvent $event)
    {
        // only trigger once.
        if ($this->dispatched === true) {
            return;
        }

        $this->dispatched = true;
        $errorEvent = new ErrorEvent($this->contextName, $event->getArgument('message'), DsWebCrawlerBundle::PROVIDER_NAME, $event->getArgument('exception'));
        $this->eventDispatcher->dispatch(DynamicSearchEvents::ERROR_DISPATCH_CRITICAL, $errorEvent);
    }
}