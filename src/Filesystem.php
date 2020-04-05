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

use Symfony\Component\Filesystem\Filesystem as BaseFilesystem;

class Filesystem extends BaseFilesystem
{
    public function put_contents(string $file, string $content, int $mode = 0644, int $mkdirmode = 0755, bool $append = false)
    {
        $dirpath = \dirname($file);
        if (!@is_dir($dirpath)) {
            $this->mkdir($dirpath, $mkdirmode);
        }

        if (!is_writable($dirpath)) {
            throw new \Exception(sprintf('Failed to write to: %s', $dirpath));

            return false;
        }

        $flags = ($append ? FILE_APPEND | LOCK_EX : LOCK_EX);
        if (!@file_put_contents($file, $content, $flags)) {
            throw new \Exception(sprintf('Failed to put contents: %s', $file));

            return false;
        }

        $this->chmod($file, $mode);

        return true;
    }
}
