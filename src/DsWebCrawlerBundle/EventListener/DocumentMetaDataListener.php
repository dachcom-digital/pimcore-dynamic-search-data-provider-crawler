<?php

namespace DsWebCrawlerBundle\EventListener;

use DsWebCrawlerBundle\Service\CrawlerStateServiceInterface;
use Pimcore\Http\Request\Resolver\DocumentResolver;
use Pimcore\Model\Document\Page;
use Pimcore\Twig\Extension\Templating\HeadMeta;
use Symfony\Component\HttpKernel\Event\RequestEvent;

class DocumentMetaDataListener
{
    protected CrawlerStateServiceInterface $crawlerState;
    protected DocumentResolver $documentResolver;
    protected HeadMeta $headMeta;

    public function __construct(
        CrawlerStateServiceInterface $crawlerState,
        DocumentResolver $documentResolver,
        HeadMeta $headMeta
    ) {
        $this->crawlerState = $crawlerState;
        $this->documentResolver = $documentResolver;
        $this->headMeta = $headMeta;
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$this->crawlerState->isDsWebCrawlerCrawler()) {
            return;
        }

        if (!$event->isMasterRequest()) {
            return;
        }

        $request = $event->getRequest();
        if (!$request->attributes->has('_route')) {
            return;
        }

        $str = 'document_';
        if (!str_starts_with($request->attributes->get('_route'), $str)) {
            return;
        }

        $document = $this->documentResolver->getDocument($request);

        if ($document instanceof Page) {
            $this->headMeta->addRaw('<meta name="dynamic-search:page-id" content="' . $document->getId() . '" />');
        }
    }
}
