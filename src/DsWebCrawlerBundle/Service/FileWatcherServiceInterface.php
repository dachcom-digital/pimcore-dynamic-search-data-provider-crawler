<?php

namespace DsWebCrawlerBundle\Service;

interface FileWatcherServiceInterface
{
    public function resetPersistenceStore();

    public function resetUriFilterPersistenceStore();
}