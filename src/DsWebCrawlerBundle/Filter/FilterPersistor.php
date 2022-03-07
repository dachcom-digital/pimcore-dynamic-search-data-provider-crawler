<?php

namespace DsWebCrawlerBundle\Filter;

use DsWebCrawlerBundle\Configuration\ConfigurationInterface;

class FilterPersistor
{
    private string $db;
    private array $data = [];
    public array $options = [
        'ext'               => '.tmp',
        'cache'             => true,
        'swap_memory_limit' => 1048576
    ];

    public function __construct(array $options = [])
    {
        // Set current database
        $this->db = ConfigurationInterface::CRAWLER_URI_FILTER_FILE_PATH;

        // Set options
        if (!empty($options)) {
            $this->setOptions($options);
        }
    }

    public function setOptions(array $options): void
    {
        foreach ($options as $key => $value) {
            $this->options[$key] = $value;
        }
    }

    public function setupDatabase(): void
    {
        if (empty($this->data)) {
            $this->data['file'] = $this->db;
            $this->data['file_tmp'] = str_replace($this->options['ext'], '_tmp' . $this->options['ext'], $this->db);
            $this->data['cache'] = [];

            // Create database
            if (!file_exists($this->data['file'])) {
                if (($fp = $this->openFile($this->data['file'], 'wb')) !== false) {
                    @fclose($fp);
                    @chmod($this->data['file'], 0777);
                    clearstatcache();
                } else {
                    throw new \Exception('Could not create database ' . $this->db);
                }
            }

            // Check file is readable
            if (!is_readable($this->data['file'])) {
                throw new \Exception('Could not read database ' . $this->db);
            }

            // Check file is writable
            if (!is_writable($this->data['file'])) {
                throw new \Exception('Could not write to database ' . $this->db);
            }
        }
    }

    private function openFile(string $file, string $mode): mixed
    {
        return @fopen($file, $mode);
    }

    /**
     * @throws \Exception
     */
    private function getKey(string $key): mixed
    {
        $data = false;

        if ($this->options['cache'] === true && array_key_exists($key, $this->data['cache'])) {
            return $this->data['cache'][$key];
        }

        if (($fp = $this->openFile($this->data['file'], 'rb')) !== false) {
            @flock($fp, LOCK_SH);

            while (($line = fgets($fp)) !== false) {
                $line = rtrim($line);
                $pieces = explode('=', $line);

                if ($pieces[0] == $key) {
                    if (count($pieces) > 2) {
                        array_shift($pieces);
                        $data = implode('=', $pieces);
                    } else {
                        $data = $pieces[1];
                    }

                    $data = unserialize($data);

                    $data = $this->preserveLines($data, true);

                    if ($this->options['cache'] === true) {
                        $this->data['cache'][$key] = $data;
                    }

                    break;
                }
            }

            @flock($fp, LOCK_UN);
            @fclose($fp);
        } else {
            throw new \Exception('Could not open database ' . $this->db);
        }

        return $data;
    }

    /**
     * @throws \Exception
     */
    private function replaceKey(string $key, mixed $data): bool
    {
        $swap = true;
        $contents = '';
        $origData = null;
        $tp = null;

        if ($this->options['swap_memory_limit'] > 0) {
            clearstatcache();
            if (filesize($this->data['file']) <= $this->options['swap_memory_limit']) {
                $swap = false;
            }
        }

        if ($data !== false) {
            if ($this->options['cache'] === true) {
                $origData = $data;
            }

            $data = $this->preserveLines($data, false);
            $data = serialize($data);
        }

        if ($swap) {
            if (($tp = $this->openFile($this->data['file_tmp'], 'ab')) !== false) {
                @flock($tp, LOCK_EX);
            } else {
                throw new \Exception('Could not create temporary database for ' . $this->db);
            }
        }

        if (($fp = $this->openFile($this->data['file'], 'rb')) !== false) {
            @flock($fp, LOCK_SH);

            while (($line = fgets($fp)) !== false) {
                $pieces = explode('=', $line);
                if ($pieces[0] === $key) {
                    if ($data === false) {
                        continue;
                    }

                    $line = $key . '=' . $data . "\n";

                    if ($this->options['cache'] === true) {
                        $this->data['cache'][$key] = $origData;
                    }
                }

                if ($swap) {
                    $fwrite = @fwrite($tp, $line);
                    if ($fwrite === false) {
                        throw new \Exception('Could not write to temporary database ' . $this->db);
                    }
                } else {
                    $contents .= $line;
                }
            }

            @flock($fp, LOCK_UN);
            @fclose($fp);

            if ($swap) {
                @flock($tp, LOCK_UN);
                @fclose($tp);

                if (!@unlink($this->data['file'])) {
                    throw new \Exception('Could not remove old database ' . $this->db);
                }

                if (!@rename($this->data['file_tmp'], $this->data['file'])) {
                    throw new \Exception('Could not rename temporary database ' . $this->db);
                }

                @chmod($this->data['file'], 0777);
            } else {
                if (($fp = $this->openFile($this->data['file'], 'wb')) !== false) {
                    @flock($fp, LOCK_EX);
                    $fwrite = @fwrite($fp, $contents);
                    @flock($fp, LOCK_UN);
                    @fclose($fp);

                    unset($contents);

                    if ($fwrite === false) {
                        throw new \Exception('Could not write to database ' . $this->db);
                    }
                } else {
                    throw new \Exception('Could not open database ' . $this->db);
                }
            }
        } else {
            throw new \Exception('Could not open database ' . $this->db);
        }

        return true;
    }

    /**
     * @throws \Exception
     */
    private function setKey(string $key, mixed $data): bool
    {
        if ($this->getKey($key) !== false) {
            return $this->replaceKey($key, $data);
        }

        $origData = null;

        if ($this->options['cache'] === true) {
            $origData = $data;
        }

        $data = $this->preserveLines($data, false);
        $data = serialize($data);

        if (($fp = $this->openFile($this->data['file'], 'ab')) !== false) {
            @flock($fp, LOCK_EX);

            // Set line, we don't use PHP_EOL to keep it cross-platform compatible
            $line = $key . '=' . $data . "\n";
            $fwrite = @fwrite($fp, $line);
            @flock($fp, LOCK_UN);
            @fclose($fp);

            if ($fwrite === false) {
                throw new \Exception('Could not write to database ' . $this->db);
            }

            if ($this->options['cache'] === true) {
                $this->data['cache'][$key] = $origData;
            }
        } else {
            throw new \Exception('Could not open database ' . $this->db);
        }

        return true;
    }

    /**
     * @throws \Exception
     */
    private function deleteKey(string $key): bool
    {
        if ($this->getKey($key) !== false) {
            if ($this->replaceKey($key, false)) {
                if ($this->options['cache'] === true && array_key_exists($key, $this->data['cache'])) {
                    unset($this->data['cache'][$key]);
                }

                return true;
            }
        }

        return false;
    }

    /**
     * @throws \Exception
     */
    private function flushDatabase(): bool
    {
        if (($fp = $this->openFile($this->data['file'], 'wb')) !== false) {
            @fclose($fp);

            if ($this->options['cache'] === true) {
                $this->data['cache'] = [];
            }
        } else {
            throw new \Exception('Could not open database ' . $this->db);
        }

        return true;
    }

    private function preserveLines(mixed $data, bool $reverse): mixed
    {
        if ($reverse) {
            $from = ['\\n', '\\r'];
            $to = ["\n", "\r"];
        } else {
            $from = ["\n", "\r"];
            $to = ['\\n', '\\r'];
        }

        if (is_string($data)) {
            $data = str_replace($from, $to, $data);
        } elseif (is_array($data)) {
            foreach ($data as $key => $value) {
                $data[$key] = $this->preserveLines($value, $reverse);
            }
        }

        return $data;
    }

    /**
     * @throws \Exception
     */
    private function isValidKey(string $key): bool
    {
        $len = strlen($key);

        if ($len < 1) {
            throw new \Exception('No key has been set');
        }

        if ($len > 50) {
            throw new \Exception('Maximum key length is 50 characters');
        }

        if (!preg_match('/^([A-Za-z0-9_]+)$/', $key)) {
            throw new \Exception('Invalid characters in key');
        }

        return true;
    }

    /**
     * @throws \Exception
     */
    private function isValidData(mixed $data): bool
    {
        if (!is_string($data) && !is_int($data) && !is_float($data) && !is_array($data)) {
            throw new \Exception('Invalid data type');
        }

        return true;
    }

    /**
     * @throws \Exception
     */
    public function get(string $key): mixed
    {
        $this->setupDatabase();

        if ($this->isValidKey($key)) {
            return $this->getKey($key);
        }

        return false;
    }

    /**
     * @throws \Exception
     */
    public function set(string $key, mixed $data): bool
    {
        $this->setupDatabase();

        if ($this->isValidKey($key) && $this->isValidData($data)) {
            return $this->setKey($key, $data);
        }

        return false;
    }

    /**
     * @throws \Exception
     */
    public function delete(string $key): bool
    {
        $this->setupDatabase();

        if ($this->isValidKey($key)) {
            return $this->deleteKey($key);
        }

        return false;
    }

    /**
     * @throws \Exception
     */
    public function flush(): bool
    {
        $this->setupDatabase();

        return $this->flushDatabase();
    }
}
