<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Composer;

use Symfony\Component\Filesystem\Filesystem;

/**
 * Class DumpTheme
 */
class ScriptHandler
{
    /**
     * Dump files to the web/css folder
     */
    public static function dumpCssFiles()
    {
        $appCss = __DIR__.'/../../../../app/Resources/public/css';
        $newPath = __DIR__.'/../../../../web/css';
        $fs = new Filesystem();
        $fs->mirror($appCss, $newPath);
    }

    /**
     * Delete old symfony folder before update (generates conflicts with composer)
     */
    public static function deleteOldFilesFrom19x()
    {
        $path = __DIR__.'/../../../../main/inc/lib/symfony/';
        if (is_dir($path) && is_writable($path)) {
            self::rmdirr($path);
        }
    }

    private static function rmdirr($dirname, $delete_only_content_in_folder = false, $strict = false)
    {
        $res = true;

        // A sanity check.
        if (!file_exists($dirname)) {
            return false;
        }
        // Simple delete for a file.
        if (is_file($dirname) || is_link($dirname)) {
            $res = unlink($dirname);

            return $res;
        }

        // Loop through the folder.
        $dir = dir($dirname);
        // A sanity check.
        $is_object_dir = is_object($dir);
        if ($is_object_dir) {
            while (false !== $entry = $dir->read()) {
                // Skip pointers.
                if ($entry == '.' || $entry == '..') {
                    continue;
                }

                // Recurse.
                if ($strict) {
                    $result = self::rmdirr("$dirname/$entry");
                    if ($result == false) {
                        $res = false;
                        break;
                    }
                } else {
                    self::rmdirr("$dirname/$entry");
                }
            }
        }

        // Clean up.
        if ($is_object_dir) {
            $dir->close();
        }

        if ($delete_only_content_in_folder == false) {
            $res = rmdir($dirname);
        }

        return $res;
    }
}
