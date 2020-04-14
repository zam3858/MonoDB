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

use Symfony\Component\VarExporter\VarExporter;

class Functions
{
    public static function hasWith(string $haystack, $needles): bool
    {
        foreach ((array) $needles as $needle) {
            if (false !== strpos($haystack, (string) $needle)) {
                return true;
            }
        }

        return false;
    }

    /**
     * endWith().
     *
     * @param mixed $needles
     */
    public static function endWith(string $haystack, $needles): bool
    {
        foreach ((array) $needles as $needle) {
            if (substr($haystack, -\strlen($needle)) === (string) $needle) {
                return true;
            }
        }

        return false;
    }

    /**
     * startWith().
     *
     * @param mixed $needles
     */
    public static function startWith(string $haystack, $needles): bool
    {
        foreach ((array) $needles as $needle) {
            if ('' !== $needle && 0 === strpos($haystack, $needle)) {
                return true;
            }
        }

        return false;
    }

    /**
     * isFileReadable().
     */
    public static function isFileReadable(string $file): bool
    {
        if (is_file($file) && is_readable($file)) {
            clearstatcache(true, $file);

            return true;
        }

        return false;
    }

    /**
     * isFileWritable().
     */
    public static function isFileWritable(string $file): bool
    {
        if (is_file($file) && is_writable($file)) {
            clearstatcache(true, $file);

            return true;
        }

        return false;
    }

    /**
     * isBinary().
     *
     * @param mixed $blob
     */
    public static function isBinary($blob): bool
    {
        if (null === $blob || \is_int($blob)) {
            return false;
        }

        return !ctype_print($blob);
    }

    /**
     * isJson().
     */
    public static function isJson(string $string): bool
    {
        return \is_array(json_decode($string, true))
            && (JSON_ERROR_NONE === json_last_error()) ? true : false
        ;
    }

    /**
     * isNum().
     *
     * @param mixed $num
     */
    public static function isNum($num): bool
    {
        return  1 === preg_match('@^\d+$@', (string) $num) ? true : false;
    }

    /**
     * isInt().
     *
     * @param mixed $num
     */
    public static function isInt($num): bool
    {
        return  1 === preg_match('@^(\-)?\d+$@', (string) $num) ? true : false;
    }

    /**
     * isOctacl().
     *
     * @param mixed $num
     */
    public static function isOctal($num)
    {
        return decoct(octdec($num)) === $num;
    }

    /**
     * isTime().
     *
     * @param mixed $num
     */
    public static function isTime($num): bool
    {
        if (self::isNum($num) && $num > 0 && $num < PHP_INT_MAX) {
            if (false !== date('Y-m-d H:i:s', (int) $num)) {
                return true;
            }
        }

        return false;
    }

    /**
     * isStdclass().
     *
     * @param mixed $object
     */
    public static function isStdclass($object): bool
    {
        if ($object instanceof stdClass) {
            return true;
        }

        if (preg_match('@^stdClass\:\:__set_state\(.*@', var_export($object, 1))) {
            return true;
        }

        return false;
    }

    /**
     * isClosure().
     *
     * @param mixed $object
     */
    public static function isClosure($object): bool
    {
        if ($object instanceof Closure) {
            return true;
        }

        if (preg_match('@^Closure\:\:__set_state\(.*@', var_export($object, 1))) {
            return true;
        }

        return false;
    }

    /**
     * encrypt().
     */
    public static function encrypt(string $string, string $epad = '!!$$@#%^&!!')
    {
        $mykey = '!!$'.$epad.'!!';
        $pad = base64_decode($mykey, true);
        $encrypted = '';

        for ($i = 0; $i < \strlen($string); ++$i) {
            $encrypted .= @\chr(@\ord($string[$i]) ^ @\ord($pad[$i]));
        }

        return strtr(base64_encode($encrypted), '=/', '$@');
    }

    /**
     * decrypt().
     */
    public static function decrypt(string $string, string $epad = '!!$$@#%^&!!')
    {
        $mykey = '!!$'.$epad.'!!';
        $pad = base64_decode($mykey, true);
        $encrypted = base64_decode(strtr($string, '$@', '=/'), true);
        $decrypted = '';

        for ($i = 0; $i < \strlen($encrypted); ++$i) {
            $decrypted .= @\chr(@\ord($encrypted[$i]) ^ @\ord($pad[$i]));
        }

        return $decrypted;
    }

    /**
     * stripScheme().
     */
    public static function stripScheme(string $string)
    {
        return @preg_replace('@^(file://|https?://|//)@', '', trim($string));
    }

    /**
     * matchWildcard(().
     *
     * @param mixed $matches
     */
    public static function matchWildcard(string $string, $matches): bool
    {
        foreach ((array) $matches as $match) {
            if (self::hasWith($match, ['*', '?'])) {
                $wildcardChars = ['\*', '\?'];
                $regexpChars = ['.*', '.'];
                $regex = str_replace($wildcardChars, $regexpChars, preg_quote($match, '@'));

                if (preg_match('@^'.$regex.'$@is', $string)) {
                    return true;
                }
            } elseif ($string === $match) {
                return true;
            }
        }

        return false;
    }

    /**
     * normalizePath().
     */
    public static function normalizePath(string $path)
    {
        $path = str_replace('\\', '/', $path);

        return preg_replace('@[/]+@', '/', $path.'/');
    }

    /**
     * resolvePath().
     */
    public static function resolvePath(string $path)
    {
        $path = self::normalizePath($path);

        if (self::startWith($path, '.') || !self::startWith($path, '/')) {
            $path = getcwd().'/'.$path;
        }

        $path = str_replace('/./', '/', $path);
        do {
            $path = preg_replace('@/[^/]+/\\.\\./@', '/', $path, 1, $cnt);
        } while ($cnt);

        return $path;
    }

    /**
     * getType().
     *
     * @param mixed $data
     */
    public static function getType($data): string
    {
        $type = \gettype($data);

        switch ($type) {
            case 'object':
                if (self::isStdclass($data)) {
                    $type = 'stdClass';
                } elseif (self::isClosure($data)) {
                    $type = 'closure';
                }
            break;
            case 'string':
                if (self::isJson($data)) {
                    $type = 'json';
                } elseif (self::isBinary($data)) {
                    $type = 'binary';
                }
            break;
        }

        return $type;
    }

    /**
     * getSize().
     *
     * @param mixed $data
     */
    public static function getSize($data)
    {
        return \is_array($data) || \is_object($data) ? \count((array) $data) : \strlen($data);
    }

    /**
     * exportVar().
     *
     * @param mixed $data
     */
    public static function exportVar($data)
    {
        return VarExporter::export($data);
    }

    /**
     * cutStr().
     *
     * @param mixed $length
     */
    public static function cutStr(string $text, $length = 50)
    {
        if (\strlen($text) > $length) {
            $textr = substr($text, 0, $length);
            if ($textr !== $text) {
                $text = trim($textr).'...';
            }
        }

        return $text;
    }
}
