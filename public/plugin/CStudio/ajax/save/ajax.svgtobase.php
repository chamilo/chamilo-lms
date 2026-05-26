<?php

declare(strict_types=1);
/**
 * This file contains the functions used by the Chamidoc plugin.
 *
 * @version 18/05/2024
 */

require_once '../../0_dal/dal.global_lib.php';

require_once '../../0_dal/dal.vdatabase.php';
$VDB = new VirtualDatabase();

require_once '../../0_dal/dal.save.php';

require_once '../../0_dal/dal.chamidoc_object.php';

require_once '../inc/functions.php';

use Chamilo\CoreBundle\Framework\Container;

$idPage = get_int_from('id');
$namefile = get_string_from('urlfile');
$localFolder = get_local_folder($idPage);
$svgSource = get_string_direct_from('src');
$htmSource = get_string_direct_from('htm');

// Validate path: no traversal segments
$namefile = str_replace('\\', '/', $namefile);
foreach (explode('/', $namefile) as $segment) {
    if ('' === $segment || '.' === $segment || '..' === $segment) {
        echo 'error';

        exit;
    }
}

$fsPath = 'CStudio/editor/'.$namefile;
$fsPathH = 'CStudio/editor/'.str_replace('.svg', '.html', $namefile);

$pluginFileSystem = Container::getPluginsFileSystem();

if ($pluginFileSystem->fileExists($fsPath)) {
    $pluginFileSystem->write($fsPath, $svgSource);
    $pluginFileSystem->write($fsPathH, $htmSource);
} else {
    echo 'error';
}
