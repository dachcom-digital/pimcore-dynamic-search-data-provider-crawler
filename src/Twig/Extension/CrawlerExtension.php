<?php

namespace DsWebCrawlerBundle\Twig\Extension;

use DsWebCrawlerBundle\Service\CrawlerStateServiceInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class CrawlerExtension extends AbstractExtension
{
    public function __construct(protected CrawlerStateServiceInterface $crawlerStateService)
    {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('ds_web_crawler_active', [$this, 'checkCrawlerState'])
        ];
    }

    public function checkCrawlerState(): bool
    {
        return $this->crawlerStateService->isDsWebCrawlerCrawler();
    }
}
