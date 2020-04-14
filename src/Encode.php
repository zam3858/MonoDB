<?php
/*
 * This file is part of the MonoDB package.
 *
 * (c) Nawawi Jamili <nawawi@rutweb.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/*
 * Functions for converting between notations and short MD5 generation.
 * Public domain By Proger_XP. http://proger.i-forge.net/Short_MD5/OMF
 */

namespace Monodb;

class Encode
{
    private const HASH_CHARS = '0123456789abcdefghijklmnopqrstuvwxyzABCDE';
    private const ENCODE_CHARS = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ~!@#$%^&*()"-;:?\/\'[]<>';
    private const MAX_SIGNED32 = 2147483647;

    /**
     * 24 hash of md5.
     *
     * @param string $string tring
     *
     * @return string Returns the hash as a 24-character alphabet number
     */
    public static function hash(string $string)
    {
        return self::toShort(self::HASH_CHARS, md5($string, true));
    }

    public static function hashFile(string $file)
    {
        if (!file_exists($file)) {
            throw new \Exception(sprintf('File not exists: %s', $file));
        }

        return self::toShort(self::HASH_CHARS, md5_file($file, true));
    }

    public static function toMD5($hash)
    {
        return bin2hex(self::toRaw(self::HASH_CHARS, $hash));
    }

    private static function toShort($chars, $raw)
    {
        $result = '';
        $length = \strlen(self::decToBase($chars, self::MAX_SIGNED32));

        foreach (str_split($raw, 4) as $dword) {
            $dword = @\ord($dword[0]) + @\ord($dword[1]) * 256 + @\ord($dword[2]) * 65536 + @\ord($dword[3]) * 16777216;
            $result .= str_pad(self::decToBase($chars, $dword), $length, $chars[0], STR_PAD_LEFT);
        }

        return $result;
    }

    private static function toRaw($chars, $short)
    {
        $result = '';
        $length = \strlen(self::decToBase($chars, self::MAX_SIGNED32));

        foreach (str_split($short, $length) as $chunk) {
            $dword = self::baseToDec($chars, $chunk);
            $result .= @\chr($dword & 0xFF).@\chr($dword >> 8 & 0xFF).@\chr($dword >> 16 & 0xFF).@\chr($dword >> 24);
        }

        return $result;
    }

    private static function decToBase($chars, $dword)
    {
        $rem = (int) fmod($dword, \strlen($chars));
        if ($dword < \strlen($chars)) {
            return $chars[$rem];
        }

        return self::{__FUNCTION__}($chars, ($dword - $rem) / \strlen($chars)).$chars[$rem];
    }

    private static function baseToDec($chars, $str)
    {
        $result = 0;
        $prod = 1;

        for ($i = \strlen($str) - 1; $i >= 0; --$i) {
            $weight = strpos($chars, $str[$i]);
            if (false === $weight) {
                throw new \Exception('BaseToDec failed - encountered a character outside of given alphabet.');
            }

            $result += $weight * $prod;
            $prod *= \strlen($chars);
        }

        return $result;
    }

    public static function encodeBinary($str, $padChar = '_', $endChar = '=')
    {
        $chars = self::ENCODE_CHARS;
        $length = \strlen(self::decToBase($chars, self::MAX_SIGNED32));

        $lastChunkLen = fmod(\strlen($str), 4);
        $lastPad = str_repeat($endChar, $lastChunkLen);
        $lastChunkLen and $str .= str_repeat("\0", 4 - $lastChunkLen);

        $result = '';
        for ($pos = 0; $pos < \strlen($str); $pos += 4) {
            $dword = substr($str, $pos, 4);
            $dword = @\ord($dword[0]) + @\ord($dword[1]) * 256 + @\ord($dword[2]) * 65536 + @\ord($dword[3]) * 16777216;
            $chunk = 0 === $dword ? '' : self::decToBase($chars, $dword);
            $result .= $chunk;

            \strlen($chunk) < $length && $result .= $padChar;
        }

        return $result.$lastPad;
    }

    public static function decodeBinary($str, $padChar = '_', $endChar = '=')
    {
        $chars = self::ENCODE_CHARS;
        $length = \strlen(self::decToBase($chars, self::MAX_SIGNED32));

        $result = '';
        $prev = 0;
        do {
            $pos = strpos($str, $padChar, $prev);

            if (false === $pos) {
                if (!isset($str[$prev])) {
                    break;
                } elseif ($str[$prev] === $endChar) {
                    $result = substr($result, 0, -1 * (4 - \strlen($str) + $prev));
                    break;
                }
                $fullChunk = true;
            } else {
                $fullChunk = $pos >= $prev + $length;
            }

            $fullChunk && $pos = $prev + $length;
            $chunk = substr($str, $prev, $pos - $prev);
            $prev = $pos + ((int) !$fullChunk);

            $dword = self::baseToDec($chars, $chunk);
            $chunk = @\chr($dword & 0xFF).@\chr($dword >> 8 & 0xFF).@\chr($dword >> 16 & 0xFF).@\chr($dword >> 24);
            $result .= $chunk;
        } while (false !== $pos);

        return $result;
    }

    public static function toBase64($str)
    {
        $str = self::decodeBinary($str);

        return base64_encode($str);
    }
}
