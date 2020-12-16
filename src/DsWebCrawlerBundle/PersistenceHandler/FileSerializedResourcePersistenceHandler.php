<?php

namespace DsWebCrawlerBundle\PersistenceHandler;

use DsWebCrawlerBundle\DsWebCrawlerEvents;
use Symfony\Component\EventDispatcher\GenericEvent;
use VDB\Spider\Downloader\Downloader;
use VDB\Spider\PersistenceHandler\PersistenceHandlerInterface;
use VDB\Spider\PersistenceHandler\FilePersistenceHandler;
use VDB\Spider\Resource as SpiderResource;

class FileSerializedResourcePersistenceHandler extends FilePersistenceHandler implements PersistenceHandlerInterface
{
    /**
     * @var Downloader
     */
    protected $downloader;

    /**
     * @param Downloader $downloader
     */
    public function setSpiderDownloader(Downloader $downloader)
    {
        $this->downloader = $downloader;
    }

    /**
     * The path that was provided with a default filename appended if it is
     * a path ending in a / or if it's not a file. This is because we don't want to persist
     * the directories as files. This is similar to wget behaviour.
     *
     * @param string $path
     *
     * @return string
     */
    protected function completePath($path)
    {
        if (substr($path, -1, 1) === '/') {
            $path .= $this->defaultFilename;
        } else {
            $pathFragments = explode('/', $path);
            if (strpos(end($pathFragments), '.') === false) {
                $path .= '/' . $this->defaultFilename;
            }
        }

        return $path;
    }

    /**
     * @param SpiderResource $resource
     */
    public function persist(SpiderResource $resource)
    {
        $path = rtrim($this->getResultPath() . $this->getFileSystemPath($resource), '/');
        if (!is_dir($path)) {
            mkdir($path, 0777, true);
        }

        $file = new \SplFileObject($path . DIRECTORY_SEPARATOR . $this->getFileSystemFilename($resource), 'w');
        $this->totalSizePersisted += $file->fwrite(serialize($resource));

        $event = new GenericEvent(null, ['resource' => $file, 'uri' => $resource->getUri()->toString()]);
        $this->downloader->getDispatcher()->dispatch($event, DsWebCrawlerEvents::DS_WEB_CRAWLER_VALID_RESOURCE_DOWNLOADED);
    }

    /**
     * @return SpiderResource
     */
    public function current()
    {
        return unserialize($this->getIterator()->current()->getContents());
    }
}
