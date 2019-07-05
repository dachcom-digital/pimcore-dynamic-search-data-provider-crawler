<?php

namespace DsWebCrawlerBundle\Transformer;

use DOMDocument;
use DynamicSearchBundle\Context\ContextDataInterface;
use DynamicSearchBundle\Logger\LoggerInterface;
use DynamicSearchBundle\Transformer\Container\DocumentContainer;
use DynamicSearchBundle\Transformer\Container\DocumentContainerInterface;
use DynamicSearchBundle\Transformer\DocumentTransformerInterface;
use Symfony\Component\DomCrawler\Crawler;
use VDB\Spider\Resource as DataResource;

class HttpResponseHtmlDataTransformer implements DocumentTransformerInterface
{
    /**
     * @var ContextDataInterface
     */
    protected $contextData;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * {@inheritDoc}
     */
    public function isApplicable($resource): bool
    {
        if (!$resource instanceof DataResource) {
            return false;
        }

        $contentTypeInfo = $resource->getResponse()->getHeaderLine('Content-Type');
        $parts = explode(';', $contentTypeInfo);
        $mimeType = trim($parts[0]);

        if ($mimeType === 'text/html') {
            return true;
        }

        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    /**
     * {@inheritDoc}
     */
    public function transformData(ContextDataInterface $contextData, $resource): ?DocumentContainerInterface
    {
        $this->contextData = $contextData;

        if (!$resource instanceof DataResource) {
            return null;
        }

        $host = $resource->getUri()->getHost();
        $uri = $resource->getUri()->toString();

        $statusCode = $resource->getResponse()->getStatusCode();

        if ($statusCode !== 200) {
            $this->log('debug', sprintf('skip transform [ %s ] because of wrong status code [ %s ]', $uri, $statusCode));
            return null;
        }

        /** @var Crawler $crawler */
        $crawler = $resource->getCrawler();
        $stream = $resource->getResponse()->getBody();
        $stream->rewind();
        $html = $stream->getContents();

        //page has canonical link: do not track if this is not the canonical document
        $hasCanonicalLink = $crawler->filterXpath('//link[@rel="canonical"]')->count() > 0;

        if ($hasCanonicalLink === true) {
            if ($uri != $crawler->filterXpath('//link[@rel="canonical"]')->attr('href')) {
                $this->log('debug', sprintf(
                        'skip transform [ %s ] because it has canonical link %s',
                        $uri,
                        (string) $crawler->filterXpath('//link[@rel="canonical"]')->attr('href')
                    )
                );
                return null;
            }
        }

        //page has no follow: do not track!
        $hasNoIndex = $crawler->filterXpath('//meta[contains(@content, "noindex")]')->count() > 0;

        if ($hasNoIndex === true) {
            $this->log('debug', sprintf('skip transform [ %s ] because it has a noindex tag', $uri));
            return null;
        }

        $doc = $this->generateDomDocument($html);
        $html = $this->extractHtml($doc);

        return new DocumentContainer($resource, [
            'uri'      => $uri,
            'host'     => $host,
            'doc'      => $doc,
            'html'     => $html
        ]);
    }

    /**
     * @param string $html
     *
     * @return DOMDocument
     */
    protected function generateDomDocument($html)
    {
        libxml_use_internal_errors(true);

        $doc = new DOMDocument();
        $doc->substituteEntities = true;

        $doc->loadHTML('<meta http-equiv="Content-Type" content="text/html; charset=utf-8">' . $html);

        libxml_use_internal_errors(false);

        return $doc;
    }

    /**
     * @param DOMDocument $doc
     *
     * @return string
     */
    protected function extractHtml(DOMDocument $doc)
    {
        libxml_use_internal_errors(true);

        $html = $doc->saveHTML();

        libxml_use_internal_errors(false);

        return $html;
    }

    /**
     * @param string $level
     * @param string $message
     */
    protected function log($level, $message)
    {
        $this->logger->log($level, $message, 'http_response_html', $this->contextData->getName());
    }
}