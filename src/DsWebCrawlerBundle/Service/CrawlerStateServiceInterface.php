<?php

namespace DsWebCrawlerBundle\Service;

interface CrawlerStateServiceInterface
{
    public function isDsWebCrawlerCrawler(): bool;
}
