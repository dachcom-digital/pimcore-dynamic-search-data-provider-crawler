<?php

namespace DsWebCrawlerBundle\Registry;

use DsWebCrawlerBundle\EventSubscriber\EventSubscriberInterface;

interface EventSubscriberRegistryInterface
{
    /**
     * @return EventSubscriberInterface[]
     */
    public function all();
}
