<?php

namespace DsWebCrawlerBundle\EventListener;

use DsWebCrawlerBundle\Service\CrawlerStateServiceInterface;
use Pimcore\Http\Request\Resolver\DocumentResolver;
use Pimcore\Model\Document\Page;
use Pimcore\Templating\Helper\HeadMeta;
use Symfony\Cmf\Bundle\RoutingBundle\Routing\DynamicRouter;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

class DocumentMetaDataListener
{
    /**
     * @var CrawlerStateServiceInterface
     */
    protected $crawlerState;

    /**
     * @var DocumentResolver
     */
    protected $documentResolver;

    /**
     * @var HeadMeta
     */
    protected $headMeta;

    /**
     * @param CrawlerStateServiceInterface $crawlerState
     * @param DocumentResolver             $documentResolver
     * @param HeadMeta                     $headMeta
     */
    public function __construct(
        CrawlerStateServiceInterface $crawlerState,
        DocumentResolver $documentResolver,
        HeadMeta $headMeta
    ) {
        $this->crawlerState = $crawlerState;
        $this->documentResolver = $documentResolver;
        $this->headMeta = $headMeta;
    }

    /**
     * @param GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event)
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
        if (substr($request->attributes->get('_route'), 0, strlen($str)) !== $str) {
            return;
        }

        $document = $this->documentResolver->getDocument($request);

        if ($document instanceof Page) {
            $this->headMeta->addRaw('<meta name="dynamic-search:page-id" content="' . $document->getId() . '" />');
        }
    }
}