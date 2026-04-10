<?php

declare(strict_types=1);

/**
 * This file contains the functions used by the Chamidoc.
 *
 * @version 18/05/2024
 */

use Chamilo\CoreBundle\Framework\Container;

require_once __DIR__.'/../0_dal/dal.global_lib.php';

require_once __DIR__.'/../0_dal/dal.vdatabase.php';
$VDB = new VirtualDatabase();

require_once __DIR__.'/inc/teachdoc-render-prepare.php';

require_once __DIR__.'/inc/teachdoc-render-file.php';

require_once __DIR__.'/inc/functions.php';

require_once __DIR__.'../../inc/tranformSource.php';

require_once __DIR__.'/../0_dal/dal.save.php';

require_once __DIR__.'/../0_dal/dal.chamidoc_object.php';

if ($VDB->w_api_is_anonymous()) {
    echo 'KOERRORPRINT';

    exit;
}

if (!isset($_GET['id'])) {
    exit;
}

if (isset($_GET['lg'])) {
    $lg = $_GET['lg'];
} else {
    $lg = 'en';
}

/*
    ini_set('display_errors',1);
    ini_set('display_startup_errors',1);
    error_reporting(E_ALL);
*/

$idPageT = get_int_from('id');
$havePdf = get_int_from('generepdf');
$idPageT = get_int_from('id');
$eppn = get_string_from('eppn');

$CollectionPages = getCollectionPages($idPageT);
$ToolObj = get_oel_tools_infos($idPageT);

$baseHtmlAll = '';

$cssPath = $VDB->w_get_path(SYS_PLUGIN_PATH).'CStudio/editor/templates/';
/*
    $cssA = $cssPath.'styles/classic.css';
    $baseCssAll = file_get_contents($cssA);
*/

$baseCssAll = 'h1 { page-break-before:always; }';

$cssB = $cssPath.'styles/print.css';
$baseCssAll .= file_get_contents($cssB);

$cssC = $cssPath.'styles/plug.css';
$baseCssAll .= file_get_contents($cssC);

$baseCssAll = str_replace(':hover', 'a-hover', $baseCssAll);
$baseCssAll = str_replace('min-width: 640px', 'min-width: 300px', $baseCssAll);
$baseCssAll = str_replace('(img/classique', '(../../img/classique', $baseCssAll);

$baseCssAll .= ' .btnroundbluecheck , .btnroundblue { display:none; }';

$baseCssAll .= ' .avatar-minidia {
    background-image: url(../../img/classique/woman-conducting-survey.png);
}';

$baseResumeAll = '<br><br>';

if ('' != $ToolObj['optionsProjectImg']) {
    $imgSrc = $ToolObj['optionsProjectImg'];
    $fileSysimgdeco = $VDB->w_get_path(SYS_PLUGIN_PATH).'CStudio/editor/'.$imgSrc;
    $fileimgdeco = $VDB->w_get_path(WEB_PLUGIN_PATH).'CStudio/editor/'.$imgSrc;

    if (file_exists($fileSysimgdeco)) {
        $baseResumeAll .= "<img class=bigtitlelogo src='".$fileimgdeco."' /><br>";
    }
}

$baseResumeAll .= '<p class=bigtitleprint >'.$ToolObj['title'].'</p>';

$baseResumeAll .= '<br><br>';
if ('fr' == $lg) {
    $baseResumeAll .= '<p><u>Sommaire</u></p>';
}
if ('en' == $lg) {
    $baseResumeAll .= '<p><u>Index</u></p>';
}
if ('es' == $lg) {
    $baseResumeAll .= '<p><u>Resumen</u></p>';
}
if ('de' == $lg) {
    $baseResumeAll .= '<p><u>Resumen</u></p>';
}
$baseResumeAll .= '<ul>';

$pageNumIndex = 2;

foreach ($CollectionPages as &$row) {
    $idPageG = (int) $row['id'];
    $typeNode = (int) $row['type_node'];

    if (3 != $typeNode) {
        $baseResumeAll .= '<li>&nbsp;'.$row['title'].'</li>';

        $baseHtml = '';
        $baseCss = '';

        $sql = 'SELECT base_html,base_css ';
        $sql .= ' FROM plugin_oel_tools_teachdoc ';
        $sql .= " WHERE id = $idPageG";

        $resultOne = $VDB->query_to_array($sql);

        foreach ($resultOne as $row) {
            $baseHtml = $row['base_html'];
            $baseCss = $row['base_css'];
        }
        $baseHtml = getSrcForPrint($baseHtml);
        $baseHtml = str_replace('(img/classique', '(../../img/classique', $baseHtml);
        if (1 == $eppn) {
            $baseHtml = $baseHtml.'<p style="text-align:right;" >'.$pageNumIndex.'</p>';
        }
        $baseHtmlAll .= $baseHtml;
        $pageNumIndex++;
    } else {
        $baseResumeAll .= '<li style="font-weight:bold;" >'.$row['title'].'</li>';
    }
}

$baseResumeAll .= '</ul>';
if (1 == $eppn) {
    $baseResumeAll .= '<p style="text-align:right;" >1</p>';
}
$baseHtmlAll = $baseResumeAll.$baseHtmlAll;

$baseHtmlAll = '<style>'.$baseCssAll.'</style>'.$baseHtmlAll;

$folderlocal = get_local_folder($idPageT);
$pluginFileSystem = Container::getPluginsFileSystem();
$printPath = 'CStudio/editor/history_cache/'.$folderlocal.'-print';

if (!$pluginFileSystem->directoryExists($printPath)) {
    $pluginFileSystem->createDirectory($printPath);
}

$imgCacheProxy = $VDB->w_get_path(WEB_PLUGIN_PATH).'CStudio/img-cache.php?path=';
$baseHtmlAll = str_replace('="img_cache/', '="'.$imgCacheProxy, $baseHtmlAll);

$absoluteFolder = $VDB->w_get_path(WEB_PLUGIN_PATH).'CStudio/editor/';
$baseHtmlAll = str_replace('{abspath}', $absoluteFolder, $baseHtmlAll);
$baseHtmlAll = str_replace('="img/qcm/', '="'.$absoluteFolder.'img/qcm/', $baseHtmlAll);
$baseHtmlAll = str_replace("='img/qcm/", "='".$absoluteFolder.'img/qcm/', $baseHtmlAll);
// src='img/qcm/
$baseHtmlAll = str_replace('="img/classique/', '="'.$absoluteFolder.'img/classique/', $baseHtmlAll);
$baseHtmlAll = str_replace("='img/classique/", "='".$absoluteFolder.'img/classique/', $baseHtmlAll);

$baseHtmlAll = str_replace('matgreen1.png', 'matgreen0.png', $baseHtmlAll);

$renderName = 'index.html';
$renderName2 = 'index2.html';

$printPathFile = $printPath.'/'.$renderName;

$pluginFileSystem->write($printPathFile, $baseHtmlAll);

if (0 == $havePdf) {
    if ($pluginFileSystem->fileExists($printPathFile)) {
        echo $VDB->w_get_path(WEB_PLUGIN_PATH).'CStudio/history-cache.php?path='.rawurlencode($folderlocal.'-print/'.$renderName);
    } else {
        echo 'errorwrite';
    }
}

// $pathPdf = $VDB->w_get_path(SYS_PLUGIN_PATH).'/CStudio/editor/history_cache/'.$folderlocal.'-print/';

if (1 == $havePdf) {
    $title = 'export pdf';
    $params = [
        'tempDir' => $pathPdf,
        'mode' => 'utf-8',
        'margin_left' => 15,
        'margin_right' => 15,
        'margin_top' => 25,
        'margin_bottom' => 20,
        'margin_header' => 8,
        'margin_footer' => 8,
    ];

    $tplA = new Template(removeQuotesPdf($title), false, false, false, false, false, false);
    $mpdf = new PDF('A4', 'P', $params, $tplA);

    $mpdf->set_custom_header('<p></p>');

    $CtrPathPdf = @$mpdf->exportFromHtmlToFile(
        $baseHtmlAll,
        'export',
        $pathPdf
    );

    echo $VDB->w_get_path(WEB_PLUGIN_PATH).'CStudio/history-cache.php?path='.rawurlencode($folderlocal.'-print/export.pdf');
}
