<?php
/*
 * This file is part of the MonoDB package.
 *
 * (c) Nawawi Jamili <nawawi@rutweb.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Monodb;

use Monodb\Arrays as Arr;
use Monodb\Functions as Func;

/**
 * Class Config.
 */
class Config
{
    /**
     * @var string
     */
    public $dir = '';

    /**
     * @var string
     */
    public $dbname = 'db0';

    /**
     * @var string
     */
    public $dbdir = '';

    /**
     * @var string
     */
    public $dbindex = '';

    /**
     * @var int
     */
    public $key_length = 150;

    /**
     * @var int
     */
    public $key_expiry = 0;

    /**
     * @var int
     */
    public $blob_size = 5000000;

    /**
     * @var int
     */
    public $perm_dir = 0755;

    /**
     * @var int
     */
    public $perm_file = 0644;

    /**
     * @return void
     */
    public function __construct(array $options)
    {
        $this->dir = sys_get_temp_dir().'/_monodb_/';
        $this->dbdir = $this->dir.$this->dbname.'/';

        $config = $this->readConfigFile();
        if (!empty($config) && \is_array($config)) {
            $config = Arr::map(
                $config,
                function ($key, $value) {
                    return strtolower($key);
                }
            );
            $options = array_merge($options, $config);
        }

        return $this->setOptions($options);
    }

    public function setOptions(array $options): array
    {
        if (!empty($options['dir']) && \is_string($options['dir'])) {
            $this->dir = Func::resolvePath($options['dir'].'/_monodb_/');
            $options['dbdir'] = $this->dir.$this->dbname.'/';
        }

        if (!empty($options['dbname']) && \is_string($options['dbname'])) {
            $options['dbdir'] = $this->dir.'/'.$options['dbname'].'/';
        }

        if (!empty($options['key_length']) && Func::isNum($options['key_length'])) {
            $key_length = (int) $options['key_length'];
            if ($key_length > 0) {
                $options['key_length'] = $key_length;
            }
            $options['key_length'] = (int) $options['key_length'];
        }

        if (!empty($options['blob_size']) && Func::isNum($options['blob_size'])) {
            $blob_size = (int) $options['blob_size'];
            if ($blob_size > 0) {
                $options['blob_size'] = $blob_size;
            }
        }

        if (!empty($options['key_expiry']) && Func::isNum($options['key_expiry'])) {
            $key_expiry = (int) $options['key_expiry'];
            if ($key_expiry > 0) {
                $options['key_expiry'] = $key_expiry;
            }
        }

        if (!empty($options['perm_dir']) && Func::isNum($options['perm_dir'])) {
            $options['perm_dir'] = $options['perm_dir'];
        }

        if (!empty($options['perm_file']) && Func::isNum($options['perm_file'])) {
            $options['perm_file'] = $options['perm_file'];
        }

        foreach ($options as $key => $value) {
            if (0 === strpos($key, '_')) {
                continue;
            }
            $this->{$key} = $value;
        }

        $this->dbdir = Func::normalizePath($this->dbdir);
        $this->dbindex = $this->dbdir.'index.php';

        return $options;
    }

    /**
     * @return mixed
     */
    private function readConfigFile()
    {
        $config = [];
        if ('cli' === \PHP_SAPI && !empty($_SERVER['HOME'])) {
            $config = $this->parseConfig($_SERVER['HOME'].'/.monodb.env');
            if (!empty($config) && \is_array($config)) {
                return $config;
            }
        } elseif (!empty($_SERVER['DOCUMENT_ROOT'])) {
            $config = $this->parseConfig($_SERVER['DOCUMENT_ROOT'].'/.monodb.env');
            if (!empty($config) && \is_array($config)) {
                return $config;
            }
        }

        $file = getenv('MONODB_ENV', true);
        $config = $this->parseConfig($file);
        if (!empty($config) && \is_array($config)) {
            return $config;
        }

        return false;
    }

    private function parseConfig(string $file): array
    {
        $config = [];
        if (!empty($file) && Func::isFileReadable($file)) {
            $buff = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            if (!empty($buff) && \is_array($buff)) {
                foreach ($buff as $line) {
                    if ('#' === $line[0]) {
                        continue;
                    }

                    $line = str_replace(['"', "'"], '', $line);
                    if (Func::hasWith($line, '=')) {
                        list($key, $value) = explode('=', trim($line));
                        $key = strtolower(trim($key));
                        $value = trim($value);
                        if (!empty($key) && property_exists($this, $key) && !empty($value)) {
                            $config[$key] = $value;
                        }
                    }
                }
                if (!empty($config)) {
                    $config['env'] = $file;
                }
            }
        }

        return $config;
    }
}
