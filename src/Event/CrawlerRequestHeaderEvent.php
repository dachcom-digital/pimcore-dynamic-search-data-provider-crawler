<?php

namespace DsWebCrawlerBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

class CrawlerRequestHeaderEvent extends Event
{
    protected array $headers = [];

    /**
     * @throws \Exception
     */
    public function addHeader(array $header)
    {
        if (!isset($header['name'])) {
            throw new \Exception('ds-web-crawler header property "name" missing');
        } elseif (!isset($header['value'])) {
            throw new \Exception('ds-web-crawler header property "value" missing');
        } elseif (!isset($header['identifier'])) {
            throw new \Exception('ds-web-crawler header property "identifier" missing');
        }

        $this->headers[] = $header;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }
}
