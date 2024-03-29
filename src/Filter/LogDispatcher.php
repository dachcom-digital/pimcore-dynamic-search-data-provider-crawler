<?php

namespace DsWebCrawlerBundle\Filter;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use VDB\Spider\Event\SpiderEvents;
use Symfony\Component\EventDispatcher\GenericEvent;
use VDB\Uri\UriInterface;

trait LogDispatcher
{
    protected array $filtered = [];
    protected EventDispatcherInterface $dispatcher;
    protected FilterPersistor $filterPersistor;

    public function setDispatcher(EventDispatcherInterface $dispatcher)
    {
        $this->filterPersistor = new FilterPersistor();
        $this->dispatcher = $dispatcher;
    }

    /**
     * @throws \Exception
     */
    public function notifyDispatcher(UriInterface $uri, string $filterType): void
    {
        $stringUri = $uri->toString();
        $saveUri = md5($stringUri);

        if ($this->filterPersistor->get($saveUri) !== false) {
            return;
        }

        $this->filtered[] = $saveUri;
        $this->filterPersistor->set($saveUri, time());
        $event = new GenericEvent($this, ['uri' => $uri, 'filterType' => $filterType]);
        $this->dispatcher->dispatch($event, SpiderEvents::SPIDER_CRAWL_FILTER_PREFETCH);
    }
}
