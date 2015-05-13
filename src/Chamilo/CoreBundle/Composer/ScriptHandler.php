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
        $fs = new Filesystem();
        $fs->remove($path);
    }
}
