<?php

namespace DsWebCrawlerBundle\Twig\Extension;

use DsWebCrawlerBundle\Service\CrawlerStateServiceInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class CrawlerExtension extends AbstractExtension
{
    /**
     * @var CrawlerStateServiceInterface
     */
    protected $crawlerStateService;

    /**
     * @param CrawlerStateServiceInterface $crawlerStateService
     */
    public function __construct(CrawlerStateServiceInterface $crawlerStateService)
    {
        $this->crawlerStateService = $crawlerStateService;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new TwigFunction('ds_web_crawler_active', [$this, 'checkCrawlerState'])
        ];
    }

    public function checkCrawlerState()
    {
        return $this->crawlerStateService->isDsWebCrawlerCrawler();
    }
}