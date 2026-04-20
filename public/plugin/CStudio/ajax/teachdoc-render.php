<?php

declare(strict_types=1);

/**
 * This file contains the functions used by the Chamidoc.
 *
 * @version 09/04/2026
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

ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);

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
$ProjectQuizzTheme = $oel_tools_infos['quizztheme'];
$optionsProjectLang = $oel_tools_infos['optionsProjectLang'];

if ('' == $local_folder) {
    echo 'KOCS - no folder';
}

$date = new DateTime();
$year = $date->format('Y');
$month = $date->format('m');
$day = $date->format('j');
if ($day < 10) {
    $day = '0'.$day;
}
$date_update = $day.'/'.$month.'/'.$year;

$infosfulltxt = "<p><span class='scolabelinfos' >Title : </span>".htmlspecialchars((string) $titleMod, \ENT_QUOTES, 'UTF-8').'</p>';
$infosfulltxt .= "<p><span class='scolabelinfos' >Create : </span>".htmlspecialchars((string) $date_create, \ENT_QUOTES, 'UTF-8').'</p>';
$infosfulltxt .= "<p><span class='scolabelinfos' >Update : </span>".htmlspecialchars((string) $date_update, \ENT_QUOTES, 'UTF-8').'</p>';
$infosfulltxt .= "<p id='idscounik' style='display:none;' >".htmlspecialchars((string) $local_folder, \ENT_QUOTES, 'UTF-8').'</p>';

$cFinlCh = '';

update_lp_infos($lp_id, $titleMod, $local_folder);

$xapiActivityId = filter_filename(clean_term_split(strtolower($titleMod)));

$course_dir = get_directory($lp_id);

$courseSysPage = "/scorm/$local_folder.zip/$local_folder";

// prepareFoldersSco($courseSysPage,$course_dir,$local_folder,$idPageTop,$optionsProjectCheck,$lp_id);  // obselete

include __DIR__.'/inc/teachdoc-render-menu.php';

// scormPrepareFilesProcess($courseSysPage,$optionsProjectLang); // obselete

// Project image

/*
 * if ($oel_tools_infos['optionsProjectImg']!='') {
 *
 * $imgSrc = $oel_tools_infos['optionsProjectImg'];
 * $fileimgdeco = $VDB->w_get_path(SYS_PLUGIN_PATH).'CStudio/editor/'. $imgSrc;
 * $backimgdeco = $courseSysPage."/img/classique/oel_back.jpg";
 *
 * if ( fileIndexOf($imgSrc,'.jpg') || fileIndexOf($imgSrc,'.jpeg') ) {
 * @copy($fileimgdeco,$backimgdeco);
 * }
 * if ( fileIndexOf($imgSrc,'.png') ) {
 * preparepng2jpg($fileimgdeco,$backimgdeco,90);
 * }
 * }
 * //gameover img
 * $sourceGo = $VDB->w_get_path(SYS_PLUGIN_PATH).'CStudio/editor/img_cache/'.$local_folder."/gameoverscreen.svg";
 * $targetGo = $VDB->w_get_path(SYS_COURSE_PATH).$course_dir.'/scorm/'.$local_folder."/img/gameoverscreen.svg";
 * @copy($sourceGo,$targetGo);
 * //Create a low image
 * $backimgdecoOri = $courseSysPage."/img/classique/oel_back.jpg";
 * $backimgdecoLow = $courseSysPage."/img/classique/oel_back-low.jpg";
 * preparelowjpg($backimgdecoOri,$backimgdecoLow,90);
 */

if (!str_contains($optionsProjectCheck, 'M') && !str_contains($optionsProjectCheck, 'L')) {
    prepareMenuStartSco($courseSysPage, $idPageTop, $optionsProjectCheck, $renderM, $titleModInit, $lp_id, $basePages, $xapiActivityId, $optionsProjectLang, $optionsProjectCheck);
}

$iPR = 1;

$_SESSION['all-files-studio'] = '';

foreach ($CollectionPages as &$rowPage) {
    $typeNodeR = (int) $rowPage['type_node'];
    $idProcess = (int) $rowPage['id'];
    processPage($rowPage, $optionsProjectCheck, $courseSysPage, $iPR, $renderM, $renderCss, $renderJS, $idPageTop, $course_dir, $xapiActivityId, $optionsProjectLang, false);
    pagethumbFileProcess($idProcess, $courseSysPage, strtolower($local_folder), $course_dir);
    if (3 != $typeNodeR) {
        $iPR++;
    }
}

foreach ($CollectionAlone as &$rowAlone) {
    processPage($rowAlone, $optionsProjectCheck, $courseSysPage, $iPR, $renderM, $renderCss, $renderJS, $idPageTop, $course_dir, $xapiActivityId, $optionsProjectLang, true);
}

if (isset($_SESSION['all-files-studio'])) {
    $pluginFileSystem = Container::getPluginsFileSystem();
    $ltfRel = 'CStudio/editor/img_cache/'.strtolower($local_folder).'/ltf.txt';
    $pluginFileSystem->write($ltfRel, (string) $_SESSION['all-files-studio'].';');
}

function processPage($row, $optionsProjectCheck, $courseSysPage, $iPR, $renderM, $renderCss, $renderJS, $idPageTop, $course_dir, $xapiActivityId, $optionsProjectLang, $alone): void
{
    $idPageG = (int) $row['id'];
    $ind = (int) $row['index'];

    if (false == $alone) {
        $prevId = (int) $row['prev_id'];
        $nextId = (int) $row['next_id'];
    } else {
        $prevId = -1;
        $nextId = -1;
    }

    $behavi = (int) $row['behavior'];

    $titlePage = $row['title'];

    $colors = $row['colors'];
    $quizztheme = $row['quizztheme'];

    if ('' == $colors) {
        $colors = 'white-chami.css';
    }
    if ('' == $quizztheme) {
        $quizztheme = 'white-quizz.css';
    }

    $typeNodeR = (int) $row['type_node'];

    $sql = 'SELECT base_html,base_css  ';
    $sql .= ' FROM plugin_oel_tools_teachdoc ';
    $sql .= " WHERE id = $idPageG";

    $VDB = new VirtualDatabase();
    $resultOne = $VDB->query_to_array($sql);
    foreach ($resultOne as $row) {
        $baseHtml = $row['base_html'];
        $baseCss = $row['base_css'];
    }

    $cssPath = $VDB->w_get_path(SYS_PLUGIN_PATH).'CStudio/editor/templates/';

    $cssA = $cssPath.'styles/classic.css';
    $dataA = file_get_contents($cssA);

    $cssB = $cssPath.'colors/'.$colors;
    $dataB = file_get_contents($cssB);

    $cssC = $cssPath.'quizztheme/'.$quizztheme;
    $dataC = file_get_contents($cssC);
    $dataC = str_replace('quizzcontentplug{', 'quizzcontentpluginactiv{', $dataC);
    $dataC = str_replace('quizzcontentplug {', 'quizzcontentpluginactiv {', $dataC);

    $sendBaseCss = $baseCss.$dataA.$dataB.$dataC;

    if (4 == $typeNodeR) {
        $optfiledata = get_oel_tools_options($idPageG);
        $pluginFs = Container::getPluginsFileSystem();
        $fileSystem = Container::getAssetRepository()->getFileSystem();

        // $optfiledata puede ser una URL del proxy (guardada sin '?' por sanitización) o una ruta relativa
        if (str_contains($optfiledata, 'img-cache.php')) {
            // Restaurar '?' si fue eliminado entre .php y path=
            $optfiledata = preg_replace('/img-cache\.php([^?])/', 'img-cache.php?$1', $optfiledata);
            $parsed = parse_url($optfiledata);
            parse_str($parsed['query'] ?? '', $queryParams);
            $imgCachePath = rawurldecode($queryParams['path'] ?? '');
            $srcFsPath = 'CStudio/editor/img_cache/'.$imgCachePath;
            $fileName = basename($imgCachePath);
        } else {
            $srcFsPath = 'CStudio/editor/'.ltrim($optfiledata, '/');
            $fileName = basename($optfiledata);
        }

        if ($pluginFs->fileExists($srcFsPath)) {
            $destFimg = $courseSysPage.'/img_cache/'.$fileName;
            $stream = $pluginFs->readStream($srcFsPath);
            $fileSystem->writeStream($destFimg, $stream);

            if ($fileSystem->fileExists($destFimg)) {
                $baseHtml = '<div class="panel"><div id="linkdatafile" >'.$fileName.'</div></div>';
            } else {
                $baseHtml = '<div class="panel"><p>Error '.$destFimg.'</p></div>';
            }
        } else {
            $baseHtml = '<div class="panel"><p>Error '.$srcFsPath.'</p></div>';
        }
        $baseCss = '.baseCss{}';
    }

    if ('' != $baseHtml && '' != $baseCss) {
        if (false == $alone) {
            if (!str_contains($optionsProjectCheck, 'L')) {
                $baseHtml = str_replace('class="panel"', 'class="panel-teachdoc"', $baseHtml);
            } else {
                $baseHtml = str_replace('class="panel"', 'class="panel-teachdoc-large"', $baseHtml);
            }
        } else {
            $baseHtml = str_replace('class="panel"', 'class="panel-teachdoc-box"', $baseHtml);
            $renderM = '';
            $renderJS = '';
            $renderCss = '';
        }

        // Retro compatibility
        $baseHtml = str_replace('>minidia</span>', '></span>', $baseHtml);

        if (strpos($optionsProjectCheck, 'F')) {
            $baseHtml = str_replace('nofullscreen', '', $baseHtml);
        }

        pageGenerationProcess($iPR, $idPageG, $courseSysPage, $renderM, $renderCss, $renderJS, $ind, $idPageTop, $baseHtml, $sendBaseCss, $prevId, $nextId, $behavi, $xapiActivityId, $optionsProjectLang, $optionsProjectCheck, $alone, $titlePage);
        pagePrepareFileProcess($idPageG, $courseSysPage, $baseHtml, strtolower($course_dir));
        pagePrepareFileProcess($idPageG, $courseSysPage, $baseCss, strtolower($course_dir));
    }
}
