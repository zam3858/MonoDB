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
    private $_saveDir = '';

    /**
     * @var string
     */
    public $dbindex = '';

    /**
     * @var int
     */
    public $keylength = 150;

    /**
     * @var int
     */
    public $keyexpiry = 0;

    /**
     * @var int
     */
    public $blobsize = 5000000;

    /**
     * @var int
     */
    public $dirmode = 0755;

    /**
     * @var int
     */
    public $filemode = 0644;

    /**
     * @return void
     */
    public function __construct(array $options)
    {
        $this->dir = sys_get_temp_dir().'/_monodb_/';
        $this->_saveDir = $this->dir.$this->dbname.'/';

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

            $this->checkPathLength($this->dir);
            $this->_saveDir = $this->dir.$this->dbname.'/';
        }

        if (!empty($options['dbname']) && \is_string($options['dbname'])) {
            if (!$this->isValidname($options['dbname'])) {
                throw new \Exception(sprintf('Invalid database name: %s', $options['dbname']));
            }
            $this->_saveDir = $this->dir.'/'.$options['dbname'].'/';
        }

        if (!empty($options['keylength']) && Func::isNum($options['keylength'])) {
            $keylength = (int) $options['keylength'];
            if ($keylength > 0) {
                $options['keylength'] = $keylength;
            }
            $options['keylength'] = (int) $options['keylength'];
        }

        if (!empty($options['blobsize']) && Func::isNum($options['blobsize'])) {
            $blobsize = (int) $options['blobsize'];
            if ($blobsize > 0) {
                $options['blobsize'] = $blobsize;
            }
        }

        if (!empty($options['keyexpiry']) && Func::isNum($options['keyexpiry'])) {
            $keyexpiry = (int) $options['keyexpiry'];
            if ($keyexpiry > 0) {
                $options['keyexpiry'] = $keyexpiry;
            }
        }

        if (!empty($options['dirmode']) && Func::isOctal($options['dirmode'])) {
            $options['dirmode'] = $options['dirmode'];
        }

        if (!empty($options['filemode']) && Func::isOctal($options['filemode'])) {
            $options['filemode'] = $options['filemode'];
        }

        foreach ($options as $key => $value) {
            if ('_' !== $key[0]) {
                $this->{$key} = $value;
            }
        }

        $this->_saveDir = Func::normalizePath($this->_saveDir);
        $this->dbindex = $this->_saveDir.'index.php';

        return $options;
    }

    public function getIndexPath(string $file): string
    {
        return ltrim(str_replace(ltrim($this->_saveDir, './'), '', trim($file, '.php')), '/');
    }

    public function getOptions($key = '')
    {
        $options = get_object_vars($this);
        foreach ($options as $k => $v) {
            if ('_' === $k[0]) {
                unset($options[$k]);
                continue;
            }
            if (Func::endWith($k, 'mode') && \is_int($v)) {
                $options[$k] = '0'.decoct($v);
            }
        }

        return !empty($options[$key]) ? $options[$key] : $options;
    }

    /**
     * @return mixed
     */
    private function readConfigFile()
    {
        if (false !== $file = getenv('MONODB_CONFIG')) {
            $config = $this->parseConfig($file);

            if (!empty($config) && \is_array($config)) {
                return $config;
            }
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
                        if (!empty($key) && property_exists($this, $key) && !empty($value) && $this->isValidValue($value)) {
                            $config[$key] = $value;
                        }
                    }
                }
                if (!empty($config)) {
                    $config['MONODB_CONFIG'] = $file;
                }
            }
        }

        return $config;
    }

    public function getSaveDir()
    {
        return $this->_saveDir;
    }

    private function isValidname($name)
    {
        $namer = preg_replace('@[^A-Za-z0-9]@', '', $name);

        return $namer !== $name ? false : true;
    }

    private function isValidValue($arg)
    {
        $argr = preg_replace('@[^A-Za-z0-9/_.:]@', '', $arg);

        return $argr !== $arg ? false : true;
    }

    private function checkPathLength($path)
    {
        $maxPathLength = PHP_MAXPATHLEN - 2;
        if (\strlen($path) > $maxPathLength) {
            throw new \Exception(sprintf('Path length exceed %s characters.', $maxPathLength));
        }
    }
}
