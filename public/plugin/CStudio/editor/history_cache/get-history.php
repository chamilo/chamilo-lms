<?php

declare(strict_types=1);

/**
 * This file contains the functions used by the Chamidoc.
 *
 * @version 18/05/2024
 */
header('Content-type: application/javascript');

require_once __DIR__.'/../../0_dal/dal.global_lib.php';

use Chamilo\CoreBundle\Framework\Container;

require_once __DIR__.'/../../0_dal/dal.vdatabase.php';
$VDB = new VirtualDatabase();

require_once __DIR__.'/../../ajax/inc/functions.php';

require_once __DIR__.'/../../0_dal/dal.save.php';

if ($VDB->w_api_is_anonymous()) {
    echo '{"history":[];';

    exit;
}

if (isset($_POST['idteach']) || isset($_GET['idteach'])) {
    echo '{"history" :[';

    $idPage = get_int_from('idteach');

    $localFolder = get_local_folder($idPage).'-'.$idPage;
    $historyCache = 'CStudio/editor/history_cache/'.$localFolder;

    $pluginFileSystem = Container::getPluginsFileSystem();
    $ind = 0;

    if ($pluginFileSystem->directoryExists($historyCache)) {
        foreach ($pluginFileSystem->listContents($historyCache, false) as $item) {
            if (!$item->isFile()) {
                continue;
            }

            $nam = basename($item->path());

            if (!str_contains($nam, '.html')) {
                continue;
            }

            if ($ind < 11) {
                if (0 != $ind) {
                    echo ',';
                }
                echo '{';
                echo '"folder":"'.strtolower($localFolder).'",';
                echo '"data":"'.$nam.'"';
                echo '}';
            }
            $ind++;
        }
    }

    echo ']}';
}
