<?php

namespace DsWebCrawlerBundle;

use DsWebCrawlerBundle\DependencyInjection\Compiler\EventSubscriberPass;
use DynamicSearchBundle\Provider\Extension\ProviderBundleInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class DsWebCrawlerBundle extends Bundle implements ProviderBundleInterface
{
    public const PROVIDER_NAME = 'web_crawler';

    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new EventSubscriberPass());
    }

    public function getPath(): string
    {
        return \dirname(__DIR__);
    }

    public function getProviderName(): string
    {
        return self::PROVIDER_NAME;
    }
}
