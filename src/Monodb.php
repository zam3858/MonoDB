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
use Monodb\Encode as Enc;
use Monodb\Functions as Func;

/**
 * Class Monodb.
 */
class Monodb
{
    /**
     * @ignore
     */
    private $version = '1.0.0';

    /**
     * @ignore
     */
    private $name = 'MonoDB';

    /**
     * @ignore
     */
    private $desc = 'A flat-file key-value data structure';

    /**
     * @ignore
     */
    private $url = 'https://monodb.io';

    /**
     * @var array
     */
    private $config = [];

    /**
     * @var bool
     */
    private $chainBlob = false;

    /**
     * @var bool
     */
    private $chainMeta = false;

    /**
     * @var bool
     */
    private $chainEncrypt = false;

    /**
     * @var bool
     */
    private $chainDecrypt = false;

    /**
     * @var array
     */
    private $errors = [];

    /**
     * @var object
     */
    private $filesystem;

    /**
     * Initialize the class and set its properties.
     *
     * @param array $options Database options
     *
     * @return void
     */
    public function __construct(array $options = [])
    {
        $this->checkDependencies();
        $this->config = new Config($options);
        $this->filesystem = new Filesystem();
        $this->errors = [];
    }

    /**
     * Destructor: Will run when object is destroyed.
     *
     * @return void
     */
    public function __destruct()
    {
        $this->errors = [];
    }

    /**
     * Dependecnises check for non composer installation.
     *
     * @return mixed Throw error when failed, true otherwise
     */
    private function checkDependencies()
    {
        $php_version = '7.1';
        if (version_compare(PHP_VERSION, $php_version, '<')) {
            throw new \Exception(sprintf('%s v%s requires PHP Version "%s" and above.', $this->name, $this->version, $php_version));
        }

        if (!\extension_loaded('json')) {
            throw new \Exception(sprintf('%s v%s requires json extension.', $this->name, $this->version));
        }

        return true;
    }

    /**
     * Set class options.
     *
     * @param array $options Database options
     *
     * @return self Returns this Inheritance
     */
    public function options($options = []): self
    {
        $this->chainBlob = false;
        $this->chainMeta = false;
        $this->chainEncrypt = false;
        $this->chainDecrypt = false;

        $this->config->setOptions($options);

        return $this;
    }

    /**
     * Collect debug message.
     *
     * @param mixed $caller Collect from callable
     * @param mixed $status Message status
     *
     * @return void Returns nothing
     */
    private function catchDebug($caller, $status): void
    {
        $log = [
            'timestamp' => gmdate('Y-m-d H:i:s').' UTC',
            'caller' => $caller,
            'status' => $status,
        ];

        if (\is_array($status)) {
            $log = array_merge($log, $status);
        }

        $this->errors[] = $log;
    }

    /**
     * Return errors log.
     *
     * @return array returns list of error message
     */
    public function lastError(): array
    {
        return $this->errors;
    }

    /**
     * Check and replace invalid key.
     *
     * @param string $key Data key
     *
     * @return string Returns Sanitized key
     */
    private function sanitizeKey(string $key): string
    {
        $keyr = preg_replace('@[^A-Za-z0-9.-:]@', '', $key);
        if ($keyr !== $key) {
            $key = substr($keyr.md5($key.$keyr.mt_srand()), 0, 12);
        }

        return substr($key, 0, $this->config->keylength);
    }

    /**
     * Set and create data file.
     *
     * @param string $key Data key
     *
     * @return string Returns full path of data file
     */
    private function keyPath(string $key): string
    {
        $key = md5($key);
        $prefix = substr($key, 0, 2);
        $path = $this->config->getSaveDir().$prefix.'/';
        $key = substr($key, 2);

        return $path.$key.'.php';
    }

    /**
     * Convert data to php script.
     *
     * @param mixed $data Data to save
     *
     * @return string Returns generated PHP code
     */
    private function dataCode($data): string
    {
        $code = '<?php'.PHP_EOL;
        $code .= 'return '.Func::exportVar($data).';'.PHP_EOL;

        return $code;
    }

    /**
     * Save data.
     *
     * @param string $file File to save
     * @param string $data Data to save
     *
     * @return bool Returns true if successful, false otherwise
     */
    private function dataSave($file, $data): bool
    {
        try {
            $this->filesystem->put_contents($file, $data, $this->config->filemode, $this->config->dirmode);
        } catch (\Exception $e) {
            $this->catchDebug(__METHOD__, $e->getMessage());

            return false;
        }

        return true;
    }

    /**
     * Update data.
     *
     * @param string $key  Data key
     * @param array  $data Data to update
     *
     * @return mixed Returns key string if successful, false otherwise
     */
    private function dataUpdate(string $key, array $data)
    {
        if (!empty($data) && \is_array($data) && !empty($data['timestamp'])) {
            if ($this->exists($key)) {
                $file = $this->keyPath($key);

                if (Func::isFileWritable($file)) {
                    $data['timestamp'] = gmdate('Y-m-d H:i:s').' UTC';

                    $code = $this->dataCode($data);
                    if ($this->dataSave($file, $code)) {
                        $this->setIndex($key, $file, $data);

                        return $key;
                    }
                }
            }
        }

        return false;
    }

    /**
     * Read data.
     *
     * @param string $file File to read
     *
     * @return array Returns array if successful
     */
    private function dataRead($file): array
    {
        $data = include $file;

        return $data;
    }

    /**
     * Save index data.
     *
     * @param string $key  Data key
     * @param string $path Data file
     * @param array  $item Data item
     *
     * @return bool Returns true if successful, false otherwise
     */
    private function setIndex(string $key, string $path, array $item): bool
    {
        $file = $this->config->dbindex;
        $index = [];
        if (Func::isFileReadable($file)) {
            $index = $this->dataRead($file);
            if (empty($index) || !\is_array($index)) {
                $index = [];
            }
        }

        $index[$key]['key'] = $key;
        $index[$key]['index'] = $this->config->getIndexPath($path);
        $index[$key]['timestamp'] = $item['timestamp'];
        $index[$key]['expiry'] = (!empty($item['expiry']) ? $item['expiry'] : 0);
        $index[$key]['type'] = $item['type'];
        $index[$key]['size'] = $item['size'];
        $index[$key]['encoded'] = (!empty($item['encoded']) ? $item['encoded'] : 0);

        $code = $this->dataCode($index);

        return $this->dataSave($file, $code);
    }

    /**
     * Remove index data.
     *
     * @param string $key Data key
     *
     * @return bool Returns true if successful, false otherwise
     */
    private function unsetIndex(string $key): bool
    {
        $file = $this->config->dbindex;
        $index = [];
        if (file_exists($file)) {
            $index = $this->dataRead($file);
            if (!empty($index) && \is_array($index)) {
                if (!empty($index[$key])) {
                    unset($index[$key]);
                }

                $code = $this->dataCode($index);

                return $this->dataSave($file, $code);
            }
        }

        return false;
    }

    /**
     * Get file content.
     *
     * @param string $data       File fullpath
     * @param array  $extra_meta (Optional) Meta data to add
     *
     * @return string Returns content of file if successful, fullpath of file otherwise
     */
    private function fetchFile($data, &$extra_meta = [])
    {
        if (\is_string($data) && Func::startWith($data, 'file://')) {
            $src = $data;
            $fi = Func::stripScheme($src);
            if (!Func::startWith($fi, ['.', '/'])) {
                $fi = getcwd().'/'.$fi;
            }
            if (Func::isFileReadable($fi)) {
                if (empty($extra_meta['mime'])) {
                    $mime = mime_content_type($fi);
                    if (!empty($mime)) {
                        $extra_meta['mime'] = $mime;
                    }
                }
                $extra_meta['source'] = $src;
                try {
                    $data = file_get_contents($fi);
                } catch (\Exception $e) {
                    $this->catchDebug(__METHOD__, $e->getMessage());
                }
            }
        }

        return $data;
    }

    /**
     * Set the string value of key.
     *
     * @param mixed $data       Data content
     * @param array $extra_meta (Optional) Meta data to add
     *
     * @return mixed Returns key string if successful, false otherwise
     */
    public function set(string $key, $data, $expiry = 0, $extra_meta = [])
    {
        $key = $this->sanitizeKey($key);
        $data = $this->fetchFile($data, $extra_meta);

        $meta = [
            'timestamp' => gmdate('Y-m-d H:i:s').' UTC',
            'key' => $key,
            'type' => Func::getType($data),
            'size' => Func::getSize($data),
        ];

        if ('closure' === $meta['type'] || 'resource' === $meta['type']) {
            $this->catchDebug(__METHOD__, 'Data type not supported: '.$meta['type']);

            return false;
        }

        if ('binary' === $meta['type']) {
            $blobsize = (int) $meta['size'];
            if ($blobsize >= $this->config->blobsize) {
                $this->catchDebug(__METHOD__, 'Maximum binary size exceeded: '.$blobsize);

                return false;
            }

            $data = Enc::encodeBinary($data);
            $meta['size'] = \strlen($data);
            $meta['encoded'] = 1;
        }

        if (!empty($expiry) && Func::isNum($expiry)) {
            $expiry = (int) $expiry;
            if ($expiry > 0) {
                $meta['expiry'] = time() + $expiry;
            }
        } elseif (!empty($this->config->keyexpiry)) {
            $meta['expiry'] = (int) $this->keyexpiry;
        }

        if (!empty($extra_meta) && \is_array($extra_meta)) {
            $meta = array_merge($meta, $extra_meta);
        }

        if (false !== $this->chainEncrypt && \is_string($this->chainEncrypt)) {
            $data = Func::encrypt($data, $this->chainEncrypt);
            $meta['size'] = \strlen($data);
            $meta['encoded'] = (!empty($meta['encoded']) ? 3 : 2);
        }
        $this->chainEncrypt = false;

        $meta['value'] = $data;
        $code = $this->dataCode($meta);

        $file = $this->keyPath($key);
        if ($this->dataSave($file, $code)) {
            $this->setIndex($key, $file, $meta);

            return $key;
        }

        $this->catchDebug(__METHOD__, 'Failed to set '.$key);

        return false;
    }

    /**
     * Get data.
     *
     * @param string $key   Data key
     * @param array  $debug (Optional) Debug message
     *
     * @return mixed Returns string or array if successful, false otherwise
     */
    public function get(string $key, &$debug = [])
    {
        $key = $this->sanitizeKey($key);

        if (!$this->exists($key)) {
            $debug[] = 'Key '.$key.' not exists';

            return false;
        }

        $file = $this->keyPath($key);

        $chainMeta = $this->chainMeta;
        $this->chainMeta = false;

        $chainBlob = $this->chainBlob;
        $this->chainBlob = false;

        if (Func::isFileReadable($file)) {
            $meta = $this->dataRead($file);
            if (!\is_array($meta) || empty($meta) || (empty($meta['value']) && 0 !== (int) $meta['value'])) {
                $this->delete($key);
                $debug[] = 'Delete Invalid data';

                return false;
            }

            if (!empty($meta['expiry']) && Func::isNum($meta['expiry'])) {
                if (time() >= (int) $meta['expiry']) {
                    $this->delete($key);
                    $debug = [
                        'status' => 'expired',
                        'Key' => $key,
                        'Expiry' => gmdate(
                            'Y-m-d H:i:s',
                            $meta['expiry']
                        ),
                    ];
                    $this->catchDebug(__METHOD__, $debug);

                    return false;
                }
            }

            $data = $meta['value'];
            $dataPlain = true;
            if (false !== $this->chainDecrypt && \is_string($this->chainDecrypt) && !empty($meta['encoded']) && (2 === (int) $meta['encoded'] || 3 === (int) $meta['encoded'])) {
                $data_r = Func::decrypt($data, $this->chainDecrypt);
                if (empty($data_r) || !ctype_print($data_r)) {
                    $dataPlain = false;
                } else {
                    $data = $data_r;
                    $meta['encoded'] = (3 === (int) $meta['encoded'] ? 1 : 0);
                }
            }
            $this->chainDecrypt = false;

            if ($dataPlain && $chainBlob && 'binary' === $meta['type'] && !empty($meta['encoded']) && (1 === (int) $meta['encoded'] || 3 === (int) $meta['encoded'])) {
                $data = Enc::decodeBinary($data);
                $meta['encoded'] = (3 === (int) $meta['encoded'] ? 2 : 0);
            }

            $meta['value'] = $data;

            return !$chainMeta ? $meta['value'] : $meta;
        }

        return false;
    }

    /**
     * Get multiple data.
     *
     * @param string $keys Data keys
     *
     * @return array Returns array, always successful
     */
    public function mget(...$keys): array
    {
        $results = [];
        foreach ($keys as $key) {
            $results[$key] = $this->get($key);
        }

        return $results;
    }

    /**
     * Delete data from database.
     *
     * @param string $key Data key
     *
     * @return mixed Returns data key if successful, false otherwise
     */
    public function delete(string $key)
    {
        $key = $this->sanitizeKey($key);
        $file = $this->keyPath($key);

        if (Func::isFileWritable($file) && unlink($file)) {
            $this->unsetIndex($key);

            return $key;
        }

        return false;
    }

    /**
     * Delete multiple data from database.
     *
     * @param string $keys Data keys
     *
     * @return array Returns array list of deleted keys, always successful
     */
    public function mdelete(...$keys): array
    {
        $results = [];
        foreach ($keys as $key) {
            $key = $this->delete($key);
            if (false !== $key) {
                $results[] = $key;
            }
        }

        return $results;
    }

    /**
     * Remove all keys from current database.
     *
     * @return bool Returns true if successful, false otherwise
     */
    public function flushDb(): bool
    {
        $dir = $this->config->getSaveDir();
        if (is_dir($dir)) {
            try {
                $this->filesystem->remove($dir);
            } catch (\Exception $e) {
                $this->catchDebug(__METHOD__, $e->getMessage());

                return false;
            }
        }

        return true;
    }

    /**
     * Find data.
     *
     * @param string $key   Data key
     * @param mixed  $match Data match
     *
     * @return mixed Returns array if successful, false otherwise
     */
    private function findData(string $key, $match)
    {
        $meta = $this->meta()->get($key);
        if (!empty($meta) && \is_array($meta)) {
            $isInvalid = function ($match, $type) {
                if (!\is_string($match) && !\is_array($match)) {
                    return true;
                }

                if (\is_array($match) && (empty($match) || 2 !== \count($match))) {
                    return true;
                }

                if ('resource' === $type || 'object' === $type || 'binary' === $type) {
                    return true;
                }

                return false;
            };

            $isArray = function ($type) {
                return 'array' === $type || 'stdClass' === $type || 'json' === $type;
            };

            $type = $meta['type'];
            $data = $meta['value'];

            if ($isInvalid($match, $type)) {
                return false;
            }

            if ($isArray($type)) {
                if ('json' === $type && Func::isJson($data)) {
                    $data = json_decode($data, true);
                } else {
                    $data = Arr::convertObject($data);
                }
                if (\is_array($match)) {
                    $found = Arr::search($data, $match[1], $match[0]);

                    return !empty($found) ? $found : false;
                }

                // single
                $found = Arr::search($data, $match);

                return !empty($found) ? $found : false;
            }

            // not array
            if (\is_string($match) && Func::matchWildcard($data, $match)) {
                return $data;
            }
        }

        return false;
    }

    /**
     * Find data in all keys.
     *
     * @param mixed $match Data match
     *
     * @return array Returns array, always succesful
     */
    public function findAll($match): array
    {
        $results = [];
        $keys = $this->keys();
        if (!empty($keys) && \is_array($keys)) {
            foreach ($keys as $key) {
                $found = $this->findData($key, $match);
                if (!empty($found)) {
                    $results[$key] = $found;
                }
            }
        }

        return $results;
    }

    /**
     * @param mixed $match
     *
     * @return mixed
     */
    public function find(string $key, $match)
    {
        if ('*' === $key) {
            return $this->findAll($match);
        }

        return $this->findData($key, $match);
    }

    /**
     * @param mixed $array_key
     *
     * @return mixed
     */
    public function findArrayKey(string $key, $array_key)
    {
        if ('*' === $key) {
            return $this->findAll([$array_key, '*']);
        }

        return $this->findData($key, [$array_key, '*']);
    }

    public function exists(string $key): bool
    {
        $key = $this->sanitizeKey($key);
        $file = $this->keyPath($key);

        return Func::isFileReadable($file);
    }

    /**
     * @return mixed
     */
    public function keys(string $key = '')
    {
        $file = $this->config->dbindex;
        $chainMeta = $this->chainMeta;
        $this->chainMeta = false;

        if (Func::isFileReadable($file)) {
            $index = $this->dataRead($file);
            if (!empty($index) && \is_array($index)) {
                if (!empty($key)) {
                    $rindex = [];
                    foreach ($index as $k => $v) {
                        if (Func::matchWildcard($k, $key)) {
                            if ($chainMeta) {
                                $rindex[$k] = $v;
                            } else {
                                $rindex[] = $k;
                            }
                        }
                    }

                    if (!empty($rindex)) {
                        $index = $rindex;
                    }
                } else {
                    if (!$chainMeta) {
                        $index = array_keys($index);
                    }
                }
            }

            $index = Arr::sortBy($index, 'timestamp');

            return $index;
        }

        return false;
    }

    public function select(string $dbname): self
    {
        $chain = $this;
        try {
            $chain = $this->options(['dbname' => $dbname]);
        } catch (\Exception $e) {
            $this->catchDebug(__METHOD__, $e->getMessage());
        }

        return $chain;
    }

    public function select_dir(string $dir): self
    {
        $chain = $this;
        try {
            $chain = $this->options(['dir' => $dir]);
        } catch (\Exception $e) {
            $this->catchDebug(__METHOD__, $e->getMessage());
        }

        return $chain;
    }

    /**
     * @return mixed
     */
    public function info(string $name = '')
    {
        $info['name'] = $this->name();
        $info['version'] = $this->version();
        $info['config'] = $this->config->getOptions();

        if (Func::hasWith($name, 'config:')) {
            $info = $info['config'];
            $name = str_replace('config:', '', $name);
        }

        return !empty($info[$name]) ? $info[$name] : $info;
    }

    /**
     * @param string $num
     *
     * @return mixed
     */
    public function incr(string $key, $num = '')
    {
        $num = (!empty($num) ? $num : 1);
        if ($this->exists($key)) {
            $data = $this->get($key);
            if (!empty($data) && 0 !== (int) $data && Func::isInt($data) && Func::isInt($num)) {
                if ($num > PHP_INT_MAX) {
                    return 1;
                }

                $data = ($data + $num);

                if ($data > PHP_INT_MAX) {
                    return PHP_INT_MAX;
                }

                if ($this->set($key, $data)) {
                    return $data;
                }
            }
        }

        if (false !== $this->set($key, 1)) {
            return 1;
        }

        return false;
    }

    /**
     * @param string $num
     *
     * @return mixed
     */
    public function decr(string $key, $num = '')
    {
        $num = (!empty($num) ? $num : 1);
        if ($this->exists($key)) {
            $data = $this->get($key);
            if (!empty($data) && Func::isInt($data) && Func::isInt($num)) {
                if ($num > PHP_INT_MAX) {
                    return 1;
                }

                $data = ($data - $num);
                if ($data < -PHP_INT_MAX) {
                    $data = -PHP_INT_MAX;
                }
                if ($this->set($key, $data)) {
                    return $data;
                }
            }
        }

        if (false !== $this->set($key, 0)) {
            return 0;
        }

        return false;
    }

    /**
     * @return mixed
     */
    public function expire(string $key, int $expiry = 0)
    {
        $data = $this->meta()->get($key);
        if (!empty($data) && \is_array($data) && !empty($data['key'])) {
            if (!empty($expiry) && Func::isNum($expiry)) {
                $expiry = (int) $expiry;
                if ($expiry > 0) {
                    $data['expiry'] = (time() + $expiry);
                    if (false !== $this->dataUpdate($key, $data)) {
                        return [
                            'key' => $key,
                            'expiry' => gmdate('Y-m-d H:i:s', $data['expiry']).' UTC',
                        ];
                    }
                }
            }

            // reset
            $data['expiry'] = 0;
            if (false !== $this->dataUpdate($key, $data)) {
                return [
                    'key' => $key,
                    'expiry' => $data['expiry'],
                ];
            }
        }

        return false;
    }

    /**
     * @param mixed $data
     *
     * @return mixed
     */
    public function append(string $key, $data)
    {
        if (Arr::isEmpty($data) && !\is_string($data)) {
            return false;
        }

        $meta = $this->meta()->get($key);
        if (\is_array($meta) && !empty($meta['value'])) {
            $buff = $meta['value'];
            if (\is_array($buff) && \is_array($data)) {
                if (!Arr::isNumeric($buff)) {
                    $buff[] = $buff;
                }
                if (!Arr::isNumeric($data)) {
                    $buff[] = $data;
                } else {
                    $buff = array_merge($buff, $data);
                }
            } elseif (\is_string($buff) && \is_string($data)) {
                $buff = $buff.' '.$data;
            } else {
                return false;
            }
            $meta['value'] = $buff;

            return $this->dataUpdate($key, $meta);
        }

        return false;
    }

    /**
     * @param mixed $enable
     */
    public function meta($enable = null): self
    {
        $this->chainMeta = (\is_bool($enable) ? $enable : true);

        return $this;
    }

    /**
     * @param mixed $enable
     */
    public function blob($enable = null): self
    {
        $this->chainBlob = (\is_bool($enable) ? $enable : true);

        return $this;
    }

    public function encrypt(string $secret = ''): self
    {
        $this->chainEncrypt = $secret;

        return $this;
    }

    public function decrypt(string $secret = ''): self
    {
        $this->chainDecrypt = $secret;

        return $this;
    }

    public function version(): string
    {
        return $this->version;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function desc(): string
    {
        return $this->desc;
    }

    public function url(): string
    {
        return $this->url;
    }
}
