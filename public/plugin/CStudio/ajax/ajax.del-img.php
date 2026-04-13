<?php

declare(strict_types=1);

/**
 * This file contains the functions used by the Chamidoc.
 *
 * @version 18/05/2024
 */

use Chamilo\CoreBundle\Framework\Container;

ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);

if (isset($_POST['id']) || isset($_GET['id'])) {
    $fileSystem = Container::getAssetRepository()->getFileSystem();

    if (
        isset($_POST['ur']) || isset($_GET['ur'])
    ) {
        require_once __DIR__.'/../0_dal/dal.global_lib.php';

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

        $ur = get_string_direct_from('ur');

        $localFolder = strtolower(get_local_folder($idPage));
        $pluginFileSystem = Container::getPluginsFileSystem();
        $folderPageCacheRel = "CStudio/editor/img_cache/$localFolder";

        $lp_id = get_lp_Id($idPage);
        $course_dir = get_directory($lp_id);
        $courseDestinShort = "/scorm/$localFolder.zip/$localFolder";
        $courseDestination = "/scorm/$localFolder.zip/$localFolder/img_cache/$localFolder";

        $nameFile = basename($ur);

        echo 'nameFile='.$nameFile.'  | ';

        if (isFileDirectUpload($nameFile)) {
            $pluginFileRel = $folderPageCacheRel.'/'.$nameFile;
            if ($pluginFileSystem->fileExists($pluginFileRel)) {
                $pluginFileSystem->delete($pluginFileRel);
            }

            if ($fileSystem->fileExists($courseDestinShort.'/'.$nameFile)) {
                $fileSystem->deleteFile($courseDestinShort.'/'.$nameFile);
            }
            if ($fileSystem->fileExists($courseDestination.'/'.$nameFile)) {
                $fileSystem->deleteFile($courseDestination.'/'.$nameFile);
            }

            echo $pluginFileRel.'  | ';

            if (!$pluginFileSystem->fileExists($pluginFileRel)) {
                echo 'OK';
            } else {
                echo 'KO ';
            }
        } else {
            echo 'KO ';
        }
    }
} else {
    echo 'KO';
}
