<?php

namespace DsWebCrawlerBundle\Service;

use Symfony\Component\HttpFoundation\RequestStack;

class CrawlerStateService implements CrawlerStateServiceInterface
{
    public function __construct(protected RequestStack $requestStack)
    {
    }

    public function isDsWebCrawlerCrawler(): bool
    {
        return $this->requestStack->getMainRequest()->headers->has('dynamicsearchwebcrawler');
    }
}
