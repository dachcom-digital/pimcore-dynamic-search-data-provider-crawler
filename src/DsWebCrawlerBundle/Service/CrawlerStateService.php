<?php

namespace DsWebCrawlerBundle\Service;

use Symfony\Component\HttpFoundation\RequestStack;

class CrawlerStateService implements CrawlerStateServiceInterface
{
    /**
     * @var RequestStack
     */
    protected $requestStack;

    /**
     * @param RequestStack $requestStack
     */
    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    /**
     * @return bool
     */
    public function isDsWebCrawlerCrawler()
    {
        return $this->requestStack->getMasterRequest()->headers->has('dynamicsearchwebcrawler');
    }
}