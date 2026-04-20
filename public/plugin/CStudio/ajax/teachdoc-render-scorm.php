<?php

declare(strict_types=1);

/**
 * This file contains the functions used by the Chamidoc.
 *
 * @version 20/06/2025
 */

use Chamilo\CoreBundle\Framework\Container;

require_once __DIR__.'/../0_dal/dal.global_lib.php';

require_once __DIR__.'/../0_dal/dal.vdatabase.php';
$VDB = new VirtualDatabase();

require_once __DIR__.'/../inc/tranformSource.php';

require_once __DIR__.'/inc/teachdoc-render-prepare.php';

require_once __DIR__.'/inc/teachdoc-render-file.php';

require_once __DIR__.'/inc/functions.php';

require_once __DIR__.'/../0_dal/dal.save.php';

require_once __DIR__.'/../0_dal/dal.chamidoc_object.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(\E_ALL);

if ($VDB->w_api_is_anonymous()) {
    echo 'KO';

    exit;
}

if (!isset($_GET['id'])) {
    exit;
}

$idPageTop = get_int_from('id');

$user = $VDB->w_api_get_user_info();
$userId = $user['id'];
$lp_id = 0;
$local_folder = '';
$titleMod = 'error';
$optionsProject = '';
$optionsProjectCheck = '';

$oel_tools_infos = get_oel_tools_infos($idPageTop);
$lp_id = $oel_tools_infos['lp_id'];

$titleMod = $oel_tools_infos['title'];
$titleModInit = $titleMod;
$date_create = $oel_tools_infos['date_create'];
$local_folder = $oel_tools_infos['local_folder'];
$optionsProject = $oel_tools_infos['optionsProject'];
$optionsProjectCheck = $oel_tools_infos['optionsProjectCheck'];
$optionsProjectMessKo = $oel_tools_infos['optionsProjectMessKo'];
$optionsProjectLang = $oel_tools_infos['optionsProjectLang'];

if ('' == $local_folder) {
    echo 'KOCS - no folder';
}

$course_dir = get_directory($lp_id);

$courseSysPage = "/scorm/$local_folder.zip/$local_folder";

$fileSystem = Container::getAssetRepository()->getFileSystem();

prepareFoldersSco($courseSysPage, $course_dir, $local_folder, $idPageTop, $optionsProjectCheck, $lp_id);

scormPrepareFilesProcess($courseSysPage, $optionsProjectLang);

// Project image
if ('' != $oel_tools_infos['optionsProjectImg']) {
    $imgSrc = $oel_tools_infos['optionsProjectImg'];
    $fileimgdeco = $VDB->w_get_path(SYS_PLUGIN_PATH).'CStudio/editor/'.$imgSrc;
    $backimgdeco = $courseSysPage.'/img/classique/oel_back.jpg';

    if (fileIndexOf($imgSrc, '.jpg') || fileIndexOf($imgSrc, '.jpeg')) {
        $stream = fopen($fileimgdeco, 'r');
        $fileSystem->writeStream($backimgdeco, $stream);
        fclose($stream);
    }

    if (fileIndexOf($imgSrc, '.png')) {
        preparepng2jpg($fileimgdeco, $backimgdeco, 90);
    }
}

// gameover img
$pluginFileSystem = Container::getPluginsFileSystem();
$sourceGoRel = 'CStudio/editor/img_cache/'.$local_folder.'/gameoverscreen.svg';
$targetGo = "/scorm/$local_folder.zip/$local_folder/img/gameoverscreen.svg";
if ($pluginFileSystem->fileExists($sourceGoRel)) {
    $stream = $pluginFileSystem->readStream($sourceGoRel);
    $fileSystem->writeStream($targetGo, $stream);
}

// Create a low image
$backimgdecoOri = $courseSysPage.'/img/classique/oel_back.jpg';
$backimgdecoLow = $courseSysPage.'/img/classique/oel_back-low.jpg';

preparelowjpg($backimgdecoOri, $backimgdecoLow, 90);
