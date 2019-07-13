<?php

namespace DsWebCrawlerBundle\DependencyInjection\Compiler;

use DsWebCrawlerBundle\Registry\EventSubscriberRegistry;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\PriorityTaggedServiceTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

final class EventSubscriberPass implements CompilerPassInterface
{
    use PriorityTaggedServiceTrait;

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        foreach ($container->findTaggedServiceIds('ds_web_crawler.event_subscriber', true) as $id => $tags) {
            $definition = $container->getDefinition(EventSubscriberRegistry::class);
            foreach ($tags as $attributes) {
                $definition->addMethodCall('register', [new Reference($id), $attributes['dispatcher']]);
            }
        }
    }
}
