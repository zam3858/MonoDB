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

use Monodb\Functions as Func;

/**
 * Class Arrays.
 */
class Arrays
{
    public static function keys(array $array, bool $recursive = false): array
    {
        if ($recursive) {
            if (preg_match_all('@(\s*?Array\n*\(\n+)?\s*\[(.*?)\]\s*=\>\s*@m', print_r($array, 1), $mm)) {
                $keys = $mm[2];
            }
        } else {
            $keys = array_keys($array);
        }

        return $keys;
    }

    /**
     * @param string $parent_key
     */
    public static function keysFlatten(array $array, $parent_key = ''): array
    {
        $keys = array_keys($array);
        foreach ($array as $parent_key => $cnt) {
            if (\is_array($cnt)) {
                $nestedKeys = self::{__FUNCTION__}($cnt, $parent_key);
                foreach ($nestedKeys as $index => $key) {
                    $nestedKeys[$index] = $parent_key.'∫'.$key;
                }
                $keys = array_merge($keys, $nestedKeys);
            }
        }

        return $keys;
    }

    /**
     * @param mixed $callback
     *
     * @return mixed
     */
    public static function map(array $array, $callback = [])
    {
        $results = [];

        foreach ($array as $arrKey => $arrValue) {
            if (\is_callable($callback)) {
                $key = \call_user_func_array($callback, [$arrKey, $arrValue]);
            } else {
                $key = ($callback[$arrKey] ?? $arrKey);
            }

            $results[$key] = (\is_array($arrValue) ? self::{__FUNCTION__}($arrValue, $callback) : $arrValue);
        }

        return $results;
    }

    /**
     * Searches the array for a given value and returns the first corresponding array/string if successful.
     *
     * @param mixed $arrayData Array data to search
     * @param mixed $findValue Array value to find
     * @param mixed $findKey   (Optional) Array key to find
     *
     * @return mixed Returns array or string if found, false otherwise
     */
    public static function search($arrayData, $findValue, $findKey = '')
    {
        if (\is_array($arrayData)) {
            foreach ($arrayData as $arrKey => $arrValue) {
                $currentKey = $arrKey;

                if ((!\is_array($arrValue) && Func::matchWildcard($arrValue, $findValue))
                    || (\is_array($arrValue) && false !== self::{__FUNCTION__}($arrValue, $findValue, $findKey))) {
                    // found value
                    $found = $arrayData[$currentKey];

                    if (\is_array($found) && !empty($findKey)) {
                        $keys = self::keys($found, true);
                        if (!self::isEmpty($keys)) {
                            foreach ($keys as $k) {
                                if (Func::matchWildcard($k, $findKey)) {
                                    return $found;
                                }
                            }
                        }

                        // null to skip
                        return;
                    }

                    return \is_array($found) ? $found : [$currentKey => $found];
                }
            }
        }

        return false;
    }

    /**
     * Convert object to array.
     *
     * @param mixed $object
     *
     * @return mixed Returns array if successful, false oterwise
     */
    public static function convertObject($object)
    {
        $array = json_decode(json_encode($object), true);

        return \is_array($array) ? $array : false;
    }

    /**
     * Count maximum depth or array.
     */
    public static function maxDepth(array $array): int
    {
        $iterator = new \RecursiveIteratorIterator(
                    new \RecursiveArrayIterator($array),
                    \RecursiveIteratorIterator::CHILD_FIRST
                );
        $iterator->rewind();
        $maxDepth = 0;
        foreach ($iterator as $k => $v) {
            $depth = $iterator->getDepth();
            if ($depth > $maxDepth) {
                $maxDepth = $depth;
            }
        }

        return $maxDepth;
    }

    /**
     * Count all elements in an array.
     */
    public static function length(array $array): int
    {
        return \count($array);
    }

    /**
     * Equalize array keys.
     *
     * @param string $emptyValue
     * @param string $keys
     */
    public static function keysEqualize(array $array, $emptyValue = '', &$keys = ''): array
    {
        if (self::isNumeric($array) && self::isSequential($array) && self::isMulti($array) && !self::isAssoc($array)) {
            $keys = [];
            foreach ($array as $n => $v) {
                $keys = array_merge($keys, array_keys($v));
            }
            $keys = array_unique($keys);
            $dd = [];
            foreach ($array as $n => $v) {
                if (\is_array($v)) {
                    foreach ($keys as $a) {
                        if (!isset($v[$a])) {
                            $dd[$n][$a] = $emptyValue;
                        } else {
                            $dd[$n][$a] = $v[$a];
                        }
                    }
                }
            }

            $array = $dd;
            asort($array);
        }

        return $array;
    }

    public static function sortBy(array $array, string $key, bool $desc = false): array
    {
        usort($array, function ($item1, $item2) use ($key, $desc) {
            if (!empty($item1[$key]) && !empty($item2[$key])) {
                if ($desc) {
                    return $item2[$key] <=> $item1[$key];
                }

                return $item1[$key] <=> $item2[$key];
            }
        });

        return $array;
    }

    /**
     * @param mixed $array
     * @param null  $key
     */
    public static function isEmpty($array, $key = null): bool
    {
        if (!\is_array($array)) {
            return true;
        }

        return null !== $key ? empty($array[$key]) : empty($array);
    }

    public static function isSequential(array $array): bool
    {
        return self::keys($array) === range(0, \count($array) - 1);
    }

    public static function isMulti(array $array): bool
    {
        return !(\count($array) === \count($array, true));
    }

    public static function isNumeric(array $array): bool
    {
        if (empty($array)) {
            return false;
        }

        foreach (self::keys($array) as &$key) {
            if ((int) $key !== $key) {
                return false;
            }
        }

        return true;
    }

    public static function isAssoc(array $array, bool $recursive = false): bool
    {
        if (empty($array)) {
            return false;
        }

        foreach (self::keys($array, $recursive) as &$key) {
            if ((string) $key !== $key) {
                return false;
            }
        }

        return true;
    }

    /**
     * stdClassObject().
     *
     * @param array $arr
     */
    public static function stdClassObject($arr)
    {
        return (object) $arr;
    }
}
