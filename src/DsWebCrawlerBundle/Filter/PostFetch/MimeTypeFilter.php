<?php

namespace DsWebCrawlerBundle\Filter\PostFetch;

use VDB\Spider\Filter\PostFetchFilterInterface;
use VDB\Spider\Resource as SpiderResource;

class MimeTypeFilter implements PostFetchFilterInterface
{
    protected array $allowedMimeType;

    public function __construct(array $allowedMimeType)
    {
        $this->allowedMimeType = $allowedMimeType;
    }

    public function match(SpiderResource $resource): bool
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
