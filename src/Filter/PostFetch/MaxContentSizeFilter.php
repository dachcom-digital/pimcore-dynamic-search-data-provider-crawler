<?php

namespace DsWebCrawlerBundle\Filter\PostFetch;

use VDB\Spider\Filter\PostFetchFilterInterface;
use VDB\Spider\Resource as SpiderResource;

class MaxContentSizeFilter implements PostFetchFilterInterface
{
    protected float|int $maxFileSize;

    public function __construct(float|int $maxFileSize = 0)
    {
        $this->maxFileSize = (float) $maxFileSize;
    }

    public function match(SpiderResource $resource): bool
    {
        $size = $resource->getResponse()->getBody()->getSize();
        $sizeMb = $size / 1024 / 1024;

        if ($this->maxFileSize === 0 || $sizeMb <= $this->maxFileSize) {
            return false;
        }

        return true;
    }
}
