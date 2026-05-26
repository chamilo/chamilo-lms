<?php

declare(strict_types=1);

/**
 * This file contains the functions used by the Chamidoc.
 *
 * @version 18/05/2024
 */
header('Content-type: application/javascript');

error_reporting(0);

use Chamilo\CoreBundle\Framework\Container;

require_once __DIR__.'/../../0_dal/dal.global_lib.php';

require_once __DIR__.'/../../0_dal/dal.vdatabase.php';
$VDB = new VirtualDatabase();

require_once __DIR__.'/../../ajax/inc/functions.php';

require_once __DIR__.'/../../0_dal/dal.save.php';

if (api_is_anonymous()) {
    echo '{"files":[];';

    exit;
}

if (isset($_POST['idteach']) || isset($_GET['idteach'])) {
    echo '{"files" :[';

    $idPage = get_int_from('idteach');

    $localFolder = get_local_folder($idPage);

    $imgCacheRel = 'CStudio/editor/img_cache/'.$localFolder;

    $pluginFileSystem = Container::getPluginsFileSystem();

    $useFilesRel = $imgCacheRel.'/ltf.txt';

    $colUseFiles = '';
    if ($pluginFileSystem->fileExists($useFilesRel)) {
        $colUseFiles = $pluginFileSystem->read($useFilesRel);
    }

    $ind = 0;

    if ($pluginFileSystem->directoryExists($imgCacheRel)) {
        foreach ($pluginFileSystem->listContents($imgCacheRel, false) as $item) {
            if ($item->isFile()) {
                $file = basename($item->path());
                if (
                    false != strpos($file, '.pdf')
                    || false != strpos($file, '.doc')
                    || false != strpos($file, '.jpg')
                    || false != strpos($file, '.jpeg')
                    || (false != strpos($file, '.svg') && !str_contains($file, 'schema-'))
                    || (false != strpos($file, '.png') && !str_contains($file, 'humbnail-studio-'))
                    || false != strpos($file, '.gif')
                    || false != strpos($file, '.jpeg')
                    || false != strpos($file, '.zip')
                    || false != strpos($file, '.mp4')
                    || false != strpos($file, '.mp3')
                    || false != strpos($file, '.docx')
                    || false != strpos($file, '.xlsx')
                    || false != strpos($file, '.ods')
                    || false != strpos($file, '.odt')
                    || false != strpos($file, '.odp')
                    || false != strpos($file, '.otp')
                    || false != strpos($file, '.pptx')
                ) {
                    if (0 != $ind) {
                        echo ',';
                    }
                    $nam = $file;
                    echo '{';
                    echo '"folder":"",';
                    echo '"src":"'.$VDB->w_get_path(WEB_PLUGIN_PATH).'CStudio/img-cache.php?path='.rawurlencode($localFolder.'/'.$nam).'",';
                    echo '"nameonly":"'.$nam.'",';

                    if (!str_contains($colUseFiles, $file)) {
                        echo '"usefile":0';
                    } else {
                        echo '"usefile":1';
                    }
                    echo '}';
                    $ind++;
                }
            }
        }
    }

    echo ']}';
}
