<?php

namespace DsWebCrawlerBundle\Service;

interface FileWatcherServiceInterface
{
    public function resetPersistenceStore(): void;

    public function resetUriFilterPersistenceStore(): void;
}
