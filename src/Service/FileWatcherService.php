<?php

namespace DsWebCrawlerBundle\Service;

use DsWebCrawlerBundle\Configuration\Configuration;
use Symfony\Component\Filesystem\Filesystem;

class FileWatcherService implements FileWatcherServiceInterface
{
    protected Configuration $configuration;
    protected Filesystem $fileSystem;

    public function __construct(Configuration $configuration)
    {
        $this->configuration = $configuration;
        $this->fileSystem = new FileSystem();
    }

    public function resetPersistenceStore(): void
    {
        if ($this->fileSystem->exists(Configuration::CRAWLER_PERSISTENCE_STORE_DIR_PATH)) {
            $this->removeFolder(Configuration::CRAWLER_PERSISTENCE_STORE_DIR_PATH);
        }

        $this->fileSystem->mkdir(Configuration::CRAWLER_PERSISTENCE_STORE_DIR_PATH, 0755);
    }

    public function resetUriFilterPersistenceStore(): void
    {
        if ($this->fileSystem->exists(Configuration::CRAWLER_URI_FILTER_FILE_PATH)) {
            $this->fileSystem->remove(Configuration::CRAWLER_URI_FILTER_FILE_PATH);
        }
    }

    private function removeFolder(string $path, string $pattern = '*'): void
    {
        $files = glob($path . "/$pattern");

        foreach ($files as $file) {
            if (is_dir($file) && !in_array($file, ['..', '.'])) {
                $this->removeFolder($file, $pattern);
                rmdir($file);
            } elseif (is_file($file) && ($file !== __FILE__)) {
                unlink($file);
            }
        }
    }
}
