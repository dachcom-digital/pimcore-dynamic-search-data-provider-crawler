<?php

namespace DsWebCrawlerBundle\Event;

use Symfony\Component\EventDispatcher\Event;

class CrawlerRequestHeaderEvent extends Event
{
    /**
     * @var resource
     */
    protected $headers = [];

    /**
     * @param array $header
     *
     * @throws \Exception
     */
    public function addHeader($header)
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

    /**
     * @return resource
     */
    public function getHeaders()
    {
        return $this->headers;
    }
}
