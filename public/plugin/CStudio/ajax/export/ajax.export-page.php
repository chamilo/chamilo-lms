<?php

declare(strict_types=1);

/**
 * chamidoc plugin\CStudio\ajax\export\ajax.export-page.php
 * Virtual class for Software.
 *
 * @author Damien Renou <rxxxx.dxxxxx@gmail.com>
 *
 * @version 18/05/2024
 */

use Chamilo\CoreBundle\Framework\Container;

$version = '1.11.16-38';
$idPage = 0;
$action = '';

require_once __DIR__.'/../../0_dal/dal.global_lib.php';

require_once __DIR__.'/../inc/functions.php';

require_once __DIR__.'/../../0_dal/dal.vdatabase.php';
$VDB = new VirtualDatabase();

require_once __DIR__.'/../../0_dal/dal.save.php';

require_once __DIR__.'/../../0_dal/dal.insert.php';

require_once __DIR__.'/../../0_dal/dal.chamidoc_object.php';

require_once __DIR__.'/../../0_dal/dal.getpaths.php';

require_once __DIR__.'/../inc/teachdoc-render-prepare.php';

$idPage = 0;

if (isset($_GET['id'])) {
    $idPage = (int) $_GET['id'];
} else {
    echo 'Error 1';

    exit;
}

$pluginFileSystem = Container::getPluginsFileSystem();

$urFileDi = 'CStudio/editor/img_cache/tmp/page'.$idPage.'.png';

if (!$pluginFileSystem->fileExists($urFileDi)) {
    echo 'Error 2';

    exit;
}

if ('' != $idPage && 0 != $idPage) {
    if (isset($_GET['title'])) {
        $Part = get_oel_tools_editor($idPage);

        $base_html = $Part['base_html'];
        $base_css = $Part['base_css'];
        $colors_data = $Part['colors'];
        $quizztheme_data = $Part['quizztheme'];

        $user_id = $VDB->w_api_get_user_id();

        $namefolder = $user_id.'-'.$idPage;

        if (!$pluginFileSystem->directoryExists('CStudio/custom_code')) {
            $pluginFileSystem->createDirectory('CStudio/custom_code');
        }
        if (!$pluginFileSystem->directoryExists('CStudio/custom_code/page-templates')) {
            $pluginFileSystem->createDirectory('CStudio/custom_code/page-templates');
        }

        $templateFinalFolder = 'CStudio/custom_code/page-templates/'.$namefolder.'/';

        if (!$pluginFileSystem->directoryExists($templateFinalFolder)) {
            $pluginFileSystem->createDirectory($templateFinalFolder);
        }

        $templateFinalFolderData = 'CStudio/custom_code/page-templates/'.$namefolder.'/data/';

        if (!$pluginFileSystem->directoryExists($templateFinalFolderData)) {
            $pluginFileSystem->createDirectory($templateFinalFolderData);
        }

        $pluginFileSystem->copy($urFileDi, "$templateFinalFolder/overview.png");

        pagePrepareFileProcess($idPage, $templateFinalFolderData, $base_html, '', true);

        $folderlocal = get_local_folder($idPage);
        $base_html = str_replace($folderlocal.'/', '{folderlocal}/', $base_html);

        $pluginFileSystem->copy(
            "$templateFinalFolder/data.html",
            $base_html.'<style>'.$base_css.'</style>'
        );

        $pluginFileSystem->copy(
            "$templateFinalFolder/title.txt",
            substr(strip_tags((string) $_GET['title']), 0, 255)
        );

        if ($pluginFileSystem->fileExists("$templateFinalFolder/data.html")) {
            echo 'OK';
        } else {
            echo 'KO';
        }
    } else {
        echo 'Error 3';
    }
}
