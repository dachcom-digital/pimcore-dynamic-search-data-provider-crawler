<?php

namespace DsWebCrawlerBundle\Guard;

use DsWebCrawlerBundle\DsWebCrawlerBundle;
use DynamicSearchBundle\Guard\ContextGuardInterface;
use DynamicSearchBundle\Normalizer\Resource\ResourceMetaInterface;

class DefaultDataResourceGuard implements ContextGuardInterface
{
    /**
     * {@inheritdoc}
     */
    public function isValidateDataResource(string $contextName, string $dataProviderName, array $dataProviderOptions, ResourceMetaInterface $resourceMeta, $resource)
    {
        if ($dataProviderName !== DsWebCrawlerBundle::PROVIDER_NAME) {
            return true;
        }

        return true;
    }
}
