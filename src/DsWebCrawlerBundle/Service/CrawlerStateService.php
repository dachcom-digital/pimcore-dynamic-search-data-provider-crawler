<?php

namespace DsWebCrawlerBundle\Service;

class CrawlerStateService implements CrawlerStateServiceInterface
{
    /**
     * @return bool
     */
    public function isDsWebCrawlerCrawler()
    {
        $headers = $this->getHeaders();

        if (empty($headers)) {
            return false;
        }

        foreach ($headers as $name => $value) {
            if ($name === 'DynamicSearchWebCrawler') {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array|false
     */
    protected function getHeaders()
    {
        if (!function_exists('getallheaders')) {
            $headers = [];
            foreach ($_SERVER as $name => $value) {
                if (substr($name, 0, 5) == 'HTTP_') {
                    $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
                }
            }

            return $headers;
        } else {
            return getallheaders();
        }
    }
}