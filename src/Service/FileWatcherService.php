<?php

namespace DsWebCrawlerBundle\Service;

use DsWebCrawlerBundle\Configuration\Configuration;
use Symfony\Component\Filesystem\Filesystem;

class FileWatcherService implements FileWatcherServiceInterface
{
    /**
     * @var Configuration
     */
    protected $configuration;

    /**
     * @var Filesystem
     */
    protected $fileSystem;

    /**
     * @param Configuration $configuration
     */
    public function __construct(Configuration $configuration)
    {
        $this->configuration = $configuration;
        $this->fileSystem = new FileSystem();
    }

    public function resetPersistenceStore()
    {
        if ($this->fileSystem->exists(Configuration::CRAWLER_PERSISTENCE_STORE_DIR_PATH)) {
            $this->removeFolder(Configuration::CRAWLER_PERSISTENCE_STORE_DIR_PATH);
        }

        $this->fileSystem->mkdir(Configuration::CRAWLER_PERSISTENCE_STORE_DIR_PATH, 0755);

    }

    /**
     * Rest Uri Filter Store
     */
    public function resetUriFilterPersistenceStore()
    {
        if ($this->fileSystem->exists(Configuration::CRAWLER_URI_FILTER_FILE_PATH)) {
            $this->fileSystem->remove(Configuration::CRAWLER_URI_FILTER_FILE_PATH);
        }
    }

    /**
     * @param        $path
     * @param string $pattern
     */
    private function removeFolder($path, $pattern = '*')
    {
        $files = glob($path . "/$pattern");

        foreach ($files as $file) {
            if (is_dir($file) and !in_array($file, ['..', '.'])) {
                $this->removeFolder($file, $pattern);
                rmdir($file);
            } elseif (is_file($file) and ($file != __FILE__)) {
                unlink($file);
            }
        }
    }
}