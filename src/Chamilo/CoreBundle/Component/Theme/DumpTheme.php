<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Component\Theme;

use Symfony\Component\Filesystem\Filesystem;

/**
 * Class DumpTheme
 */
class DumpTheme
{
    /**
     * Dump files
     */
    public static function dumpCssFiles()
    {
        $fs = new Filesystem();
        $appCss = __DIR__.'/../../../../../app/Resources/public/css';
        $newPath = __DIR__.'/../../../../../web/css';
        $fs->mirror($appCss, $newPath);
    }
}
