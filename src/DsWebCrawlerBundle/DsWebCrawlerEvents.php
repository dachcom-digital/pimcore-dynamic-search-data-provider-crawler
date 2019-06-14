<?php

namespace DsWebCrawlerBundle;

final class DsWebCrawlerEvents
{
    /**
     * Triggers before a request starts
     *
     * Use it to add some custom header information like authentication for example
     */
    const DS_WEB_CRAWLER_REQUEST_HEADER = 'ds_web_crawler.request_header';

    /**
     * Triggers after crawler has started
     */
    const DS_WEB_CRAWLER_START = 'ds_web_crawler.start';

    /**
     * Triggers after crawler is finished
     */
    const DS_WEB_CRAWLER_FINISH = 'ds_web_crawler.finish';

    /**
     * Triggers if a exception has been thrown
     */
    const DS_WEB_CRAWLER_ERROR = 'ds_web_crawler.exception';

    /**
     * Triggers after a crawled uri has been downloaded and stored as resource
     */
    const DS_WEB_CRAWLER_VALID_RESOURCE_DOWNLOADED = 'ds_web_crawler.valid_resource_downloaded';

}