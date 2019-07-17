<?php

namespace DsWebCrawlerBundle\Filter\PostFetch;

use VDB\Spider\Filter\PostFetchFilterInterface;
use VDB\Spider\Resource as SpiderResource;

class MimeTypeFilter implements PostFetchFilterInterface
{
    /**
     * @var array
     */
    protected $allowedMimeType = [];

    /**
     * @param array $allowedMimeType
     */
    public function __construct($allowedMimeType)
    {
        $this->allowedMimeType = $allowedMimeType;
    }

    /**
     * @param SpiderResource $resource
     *
     * @return bool
     */
    public function match(SpiderResource $resource)
    {
        $hasContentType = count(
            array_intersect(
                array_map(
                    function ($allowed) use ($resource) {
                        $contentTypeInfo = $resource->getResponse()->getHeaderLine('Content-Type');
                        $contentType = explode(';', $contentTypeInfo); //only get content type, ignore charset.
                        return $allowed === $contentType[0];
                    },
                    $this->allowedMimeType
                ),
                [true]
            )
        ) > 0;

        return !$hasContentType;
    }
}
