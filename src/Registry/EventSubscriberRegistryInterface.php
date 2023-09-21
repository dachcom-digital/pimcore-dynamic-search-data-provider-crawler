<?php

namespace DsWebCrawlerBundle\Registry;

use DsWebCrawlerBundle\EventSubscriber\EventSubscriberInterface;

interface EventSubscriberRegistryInterface
{
    /**
     * @return array<string, array<int, EventSubscriberInterface>>
     */
    public function all(): array;
}
