<?php

namespace DsWebCrawlerBundle;

use DsWebCrawlerBundle\DependencyInjection\Compiler\EventSubscriberPass;
use Pimcore\Extension\Bundle\AbstractPimcoreBundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class DsWebCrawlerBundle extends AbstractPimcoreBundle
{
    const PROVIDER_NAME = 'webCrawler';

    /**
     * @param ContainerBuilder $container
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new EventSubscriberPass());
    }

}
