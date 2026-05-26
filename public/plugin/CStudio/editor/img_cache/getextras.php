<?php

declare(strict_types=1);

use Chamilo\CoreBundle\Framework\Container;

/*
 * This file contains the functions used by the Chamidoc.
 *
 * @version 18/05/2024
 */

require_once __DIR__.'/../../0_dal/dal.global_lib.php';

header('Content-type: text/javascript');

error_reporting(0);

$idPage = -1;
if (isset($_POST['id'])) {
    $idPage = (int) $_POST['id'];
}
if (isset($_GET['id'])) {
    $idPage = (int) $_GET['id'];
}

$pluginFileSystem = Container::getPluginsFileSystem();
$cacheFileRel = 'CStudio/editor/img_cache/tmp/imgextras_'.$idPage.'.json';
if ($pluginFileSystem->fileExists($cacheFileRel)) {
    $cacheContent = $pluginFileSystem->read($cacheFileRel);
    $lastModified = $pluginFileSystem->lastModified($cacheFileRel);
    if ((time() - $lastModified) < 300) {
        echo $cacheContent;

        exit;
    }
}

require_once __DIR__.'/../../0_dal/dal.vdatabase.php';
$VDB = new VirtualDatabase();

require_once __DIR__.'/../../ajax/inc/functions.php';

require_once __DIR__.'/../../0_dal/dal.save.php';

if ($VDB->w_api_is_anonymous()) {
    echo 'var baseMyCollImgs = [];';

    exit;
}

if (isset($_POST['id']) || isset($_GET['id'])) {
    $finalJson = 'var baseMyCollImgs = [';

    $localFolder = get_local_folder($idPage);
    $courseCacheRel = 'CStudio/editor/img_cache/'.$localFolder;

    $useFilesRel = $courseCacheRel.'/ltf.txt';
    $colUseFiles = '';
    if ($pluginFileSystem->fileExists($useFilesRel)) {
        $colUseFiles = $pluginFileSystem->read($useFilesRel);
    }

    $ind = 0;

    if ($pluginFileSystem->directoryExists($courseCacheRel)) {
        foreach ($pluginFileSystem->listContents($courseCacheRel, false) as $item) {
            if ($item->isFile()) {
                $file = basename($item->path());
                if (
                    false != strpos($file, '.jpg')
                    || (false != strpos($file, '.png') && !str_contains($file, 'humbnail-studio-'))
                    || false != strpos($file, '.gif') || false != strpos($file, '.jpeg')
                    || (false != strpos($file, '.svg') && !str_contains($file, 'schema-'))
                ) {
                    $nam = $file;
                    if (0 != $ind) {
                        $finalJson .= ',';
                    }
                    $namF = $VDB->w_get_path(WEB_PLUGIN_PATH).'CStudio/img-cache.php?path='.rawurlencode(strtolower($localFolder).'/'.$nam);
                    $finalJson .= '{';
                    $finalJson .= '"type":"image",';
                    $finalJson .= '"src":"'.$namF.'",';
                    if (!str_contains($colUseFiles, $file)) {
                        $finalJson .= '"usefile":0,';
                    } else {
                        $finalJson .= '"usefile":1,';
                    }
                    $finalJson .= '"unitDim":"%","height":0,"width":"100%"';
                    $finalJson .= '}';
                    $ind++;
                }
            }
        }
    }

    $finalJson .= '];';
    echo $finalJson;

    $pluginFileSystem->createDirectory('CStudio/editor/img_cache/tmp');
    $pluginFileSystem->write($cacheFileRel, $finalJson);
}
