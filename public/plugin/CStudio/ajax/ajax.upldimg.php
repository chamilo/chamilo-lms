<?php

declare(strict_types=1);

/**
 * This file contains the functions used by the Chamidoc.
 *
 * @version 18/05/2024
 */

use Chamilo\CoreBundle\Framework\Container;

if (isset($_POST['id']) || isset($_GET['id'])) {
    if (
        isset($_POST['ur']) || isset($_GET['ur'])
    ) {
        require_once '../0_dal/dal.global_lib.php';

        require_once '../0_dal/dal.vdatabase.php';
        $VDB = new VirtualDatabase();

        require_once '../0_dal/dal.save.php';

        require_once '../0_dal/dal.chamidoc_object.php';

        require_once 'inc/functions.php';

        $idPage = get_int_from('id');

        if (!oel_ctr_rights($idPage)) {
            echo 'KO';

            exit;
        }

        $ur = get_string_from('ur');
        $urSys = get_string_from('ur');

        // /courses/TESTLP/document/3787-tolerancement-gps.pdf

        $urSys = str_replace('%7C', '|', $urSys);
        $urSys = str_replace('web_plugin|', '/plugin/', $urSys);

        $posCtr = strrpos($urSys, 'http');
        if (false === $posCtr) {
            $posCtr = strrpos($urSys, 'courses/');
            if (false != $posCtr) {
                $haveCorrection = true;
                $urSys = $VDB->w_get_path(SYS_PATH).'app/'.$urSys;
            }
        }

        $isShort = get_int_from('short');

        $localFolder = get_local_folder($idPage);
        $coursePageCache = 'CStudio/editor/img_cache/'.strtolower($localFolder);
        $fileSystem = Container::getPluginsFileSystem();

        if (isset($_GET['nooverwrite'])) {
        }

        $nameFile = get_clean_idstring(basename($ur));
        if (isset($_POST['neoname'])) {
            $neoname = get_clean_idstring(basename(get_string_from('neoname')));
            if ('' !== $neoname) {
                $nameFile = $neoname;
            }
        }

        if (!$fileSystem->directoryExists($coursePageCache)) {
            $fileSystem->createDirectory($coursePageCache);
        }

        if (!$fileSystem->fileExists($coursePageCache.'/'.$nameFile)) {
            $stream = fopen(api_get_path(SYS_PUBLIC_PATH).$urSys, 'r');
            $fileSystem->writeStream($coursePageCache.'/'.$nameFile, $stream);
            fclose($stream);
        }
        if (!$fileSystem->fileExists($coursePageCache.'/'.$nameFile)) {
            $urSysDir = str_replace($VDB->w_get_path(WEB_PATH), $VDB->w_get_path(SYS_PATH), $urSys);
            $stream = fopen($urSysDir, 'r');
            $fileSystem->writeStream($coursePageCache.'/'.$nameFile, $stream);
            fclose($stream);
        }
        if (!$fileSystem->fileExists($coursePageCache.'/'.$nameFile)) {
            $stream = fopen($ur, 'r');
            $fileSystem->writeStream($coursePageCache.'/'.$nameFile, $stream);
            fclose($stream);
        }

        if ($fileSystem->fileExists($coursePageCache.'/'.$nameFile)) {
            $imgCacheProxy = $VDB->w_get_path(WEB_PLUGIN_PATH).'CStudio/img-cache.php?path=';

            if (1 == $isShort) {
                echo $imgCacheProxy.'img_cache/'.$nameFile;
            } else {
                echo $imgCacheProxy.'img_cache/'.strtolower($localFolder).'/'.$nameFile;
            }
        }
    }
} else {
    echo 'no id';
}
