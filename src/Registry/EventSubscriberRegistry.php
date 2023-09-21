<?php

namespace DsWebCrawlerBundle\Registry;

use DsWebCrawlerBundle\EventSubscriber\EventSubscriberInterface;

class EventSubscriberRegistry implements EventSubscriberRegistryInterface
{
    protected array $subscriber;

    public function register(EventSubscriberInterface $service, string $dispatcher): void
    {
        if (!is_string($dispatcher)) {
            throw new \InvalidArgumentException(
                sprintf('%s needs to define a valid dispatcher.', get_class($service))
            );
        }

        if (!in_array(EventSubscriberInterface::class, class_implements($service), true)) {
            throw new \InvalidArgumentException(
                sprintf('%s needs to implement "%s", "%s" given.', get_class($service), EventSubscriberInterface::class, implode(', ', class_implements($service)))
            );
        }

        if (!isset($this->subscriber[$dispatcher])) {
            $this->subscriber[$dispatcher] = [];
        }

        $this->subscriber[$dispatcher][] = $service;
    }

    public function all(): array
    {
        return $this->subscriber;
    }
}
