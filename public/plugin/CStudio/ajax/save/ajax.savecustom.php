<?php

declare(strict_types=1);

/**
 * This file contains the functions used by the Chamidoc plugin.
 *
 * @version 18/05/2024
 */

use Chamilo\CoreBundle\Framework\Container;

if (isset($_GET['id']) || isset($_GET['act'])) {
    require_once '../../0_dal/dal.global_lib.php';

    require_once '../../0_dal/dal.vdatabase.php';
    $VDB = new VirtualDatabase();

    require_once '../inc/functions.php';

    require_once '../../0_dal/dal.save.php';

    $idTopPage = get_int_from('id');
    $customcode = get_string_from('customcode');
    $act = get_string_from('act');

    $localFolder = get_local_folder($idTopPage);
    $cPathCache = 'CStudio/editor/img_cache/';
    $cPathCache .= strtolower($localFolder).'/customcode.css';

    $pluginFileSystem = Container::getPluginsFileSystem();

    if ('save' == $act) {
        if ('' == $customcode) {
            $customcode = '/* code css */';
        }
        $pluginFileSystem->write($cPathCache, $customcode);
    }
    if ('read' == $act) {
        if ($pluginFileSystem->fileExists($cPathCache)) {
            echo $pluginFileSystem->read($cPathCache);
        } else {
            echo '';
        }
    }
}
