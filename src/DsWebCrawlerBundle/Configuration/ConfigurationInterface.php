<?php

namespace DsWebCrawlerBundle\Configuration;

interface ConfigurationInterface
{
    public const CRAWLER_URI_FILTER_FILE_PATH = PIMCORE_PRIVATE_VAR . '/bundles/DsWebCrawlerBundle/uri-filter.tmp';
    public const CRAWLER_PERSISTENCE_STORE_DIR_PATH = PIMCORE_PRIVATE_VAR . '/bundles/DsWebCrawlerBundle/persistence-store';
    public const CRAWLER_PROCESS_FILE_PATH = PIMCORE_PRIVATE_VAR . '/bundles/DsWebCrawlerBundle/processing.tmp';

    public function get(string $slot): mixed;
}
