<?php

namespace DsWebCrawlerBundle\Service;

use Symfony\Component\HttpFoundation\RequestStack;

class CrawlerStateService implements CrawlerStateServiceInterface
{
    protected RequestStack $requestStack;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    public function isDsWebCrawlerCrawler(): bool
    {
        return $this->requestStack->getMainRequest()->headers->has('dynamicsearchwebcrawler');
    }
}
