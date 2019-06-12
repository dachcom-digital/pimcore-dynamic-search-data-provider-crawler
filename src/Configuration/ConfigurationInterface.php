<?php

namespace DsWebCrawlerBundle\Configuration;

interface ConfigurationInterface
{
    const CRAWLER_URI_FILTER_FILE_PATH = PIMCORE_PRIVATE_VAR . '/bundles/DsWebCrawlerBundle/uri-filter.tmp';

    const CRAWLER_PERSISTENCE_STORE_DIR_PATH = PIMCORE_PRIVATE_VAR . '/bundles/DsWebCrawlerBundle/persistence-store';

    const CRAWLER_PROCESS_FILE_PATH = PIMCORE_PRIVATE_VAR . '/bundles/DsWebCrawlerBundle/processing.tmp';

    /**
     * @param string $slot
     *
     * @return mixed
     */
    public function get($slot);
}
