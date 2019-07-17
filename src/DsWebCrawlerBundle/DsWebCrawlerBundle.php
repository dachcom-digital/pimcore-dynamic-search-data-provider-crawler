<?php

namespace DsWebCrawlerBundle;

use DsWebCrawlerBundle\DependencyInjection\Compiler\EventSubscriberPass;
use DynamicSearchBundle\Provider\Extension\ProviderBundleInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class DsWebCrawlerBundle extends Bundle implements ProviderBundleInterface
{
    const PROVIDER_NAME = 'web_crawler';

    /**
     * @param ContainerBuilder $container
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new EventSubscriberPass());
    }

    /**
     * {@inheritdoc}
     */
    public function getProviderName(): string
    {
        return self::PROVIDER_NAME;
    }
}
