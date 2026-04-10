<?php

declare(strict_types=1);

/**
 * chamidoc plugin\CStudio\ajax\export\prepare-sco.php
 * Virtual class for Software.
 *
 * @author Damien Renou <rxxxx.dxxxxx@gmail.com>
 *
 * @version 18/05/2024
 */

use Chamilo\CoreBundle\Framework\Container;

require_once __DIR__.'/../../0_dal/dal.global_lib.php';

require_once __DIR__.'/../inc/functions.php';

require_once __DIR__.'/../../0_dal/dal.vdatabase.php';
$VDB = new VirtualDatabase();

require_once __DIR__.'/../../0_dal/dal.save.php';

require_once __DIR__.'/../../0_dal/dal.insert.php';

require_once __DIR__.'/../../0_dal/dal.chamidoc_object.php';

require_once __DIR__.'/../../0_dal/dal.getpaths.php';

/*
ini_set('display_errors',1);
ini_set('display_startup_errors',1);
error_reporting(E_ALL);
*/

if (!isset($_GET['id'])) {
    echo 'KO';

    exit;
}

$step = isset($_GET['step']) ? $VDB->remove_XSS($_GET['step']) : '0';
$idPageTop = isset($_GET['id']) ? $VDB->remove_XSS($_GET['id']) : '0';
$process = isset($_GET['p']) ? $VDB->remove_XSS($_GET['p']) : '0';
/*
0 = scorm
1 = project
2 = xapi
3 = totheweb
*/

if (false == oel_ctr_rights($idPageTop)) {
    echo 'KO';

    exit;
}

$user = $VDB->w_api_get_user_info();
$userId = $user['id'];
$idurl = $VDB->w_get_current_access_url_id();
$UrlWhere = '';
$local_folder = '';
$title = 'error';
$lp_id = 0;

$lp_id = get_lp_Id($idPageTop);
$local_folder = get_local_folder($idPageTop);

if ('' == $local_folder) {
    echo 'KOCS - no folder';

    exit;
}
if ($lp_id < 1) {
    echo 'KOCS - lp_id';

    exit;
}

$pluginFileSystem = Container::getPluginsFileSystem();

$scormSysPage = 'CStudio/editor/sco_cache/'.strtolower($local_folder);

$scormWebPage = $VDB->w_get_path(WEB_PLUGIN_PATH).'CStudio/sco-cache.php?path='.strtolower($local_folder);

if (3 == $process) {
    $scormSysPage = 'CStudio/public_folder/'.strtolower($local_folder);

    $scormWebPage = $VDB->w_get_path(WEB_PATH).'plugin/CStudio/public_folder/'.strtolower($local_folder);
}

if (1 == $step) {
    if (!$pluginFileSystem->directoryExists($scormSysPage)) {
        $pluginFileSystem->createDirectory($scormSysPage);
    }
}

$scormfolder = $scormSysPage.'/'.$idPageTop;

if (1 == $step) {
    sleep(2);
    if ($pluginFileSystem->directoryExists($scormfolder)) {
        // $pluginFileSystem->deleteDirectory($scormfolder);
    }
    if ($pluginFileSystem->directoryExists($scormfolder)) {
        // $pluginFileSystem->deleteDirectory($scormfolder);
    }
    if (!$pluginFileSystem->directoryExists($scormfolder)) {
        $pluginFileSystem->createDirectory($scormfolder);
    }
}

$assetFileSystem = Container::getAssetRepository()->getFileSystem();

$course_dir = get_directory($lp_id);
$courseSysPage = "/scorm/$local_folder.zip/$local_folder/";

if (2000 == $step) {
    $listing = $assetFileSystem->listContents($courseSysPage);
    foreach ($listing as $item) {
        if ($item->isFile() && str_ends_with($item->path(), '.mp4')) {
            // $assetFileSystem->delete($item->path());
        }
    }
}

if (1 == $step) {
    if ($pluginFileSystem->directoryExists($scormfolder)) {
        echo ' OK 1';
    } else {
        echo 'KO';
    }
}

if (2 == $step) {
    if (1 == $process) {
        if (!$pluginFileSystem->directoryExists("$scormfolder/exportdata")) {
            $pluginFileSystem->createDirectory("$scormfolder/exportdata");
        }

        $CollectionContents = getCollectionContents($idPageTop);
        foreach ($CollectionContents as &$row) {
            $base_index = (int) $row['index'];
            $path_p = "$scormfolder/exportdata/$base_index";

            // echo $path_p."-base_html.txt<br>";

            $base_html = $row['base_html'];
            $pluginFileSystem->write("$path_p-base_html.txt", $base_html);
            $base_css = $row['base_css'];
            $pluginFileSystem->write("$path_p-base_css.txt", $base_css);
            $GpsComps = $row['gpscomps'];
            $pluginFileSystem->write("$path_p-GpsComps.txt", $GpsComps);
            $GpsStyle = $row['gpsstyle'];
            $pluginFileSystem->write("$path_p-GpsStyle.txt", $GpsStyle);
            $GpsStyle = $row['options'];
            $pluginFileSystem->write("$path_p-options.txt", $GpsStyle);

            $pluginFileSystem->write("$path_p-localfolder.txt", $local_folder);

            $title = $row['title'];
            $pluginFileSystem->write("$path_p-title.txt", $title);

            $behavior = $row['behavior'];
            $pluginFileSystem->write("$path_p-behavior.txt", (string) $behavior);

            $colors = $row['colors'];
            $pluginFileSystem->write("$path_p-colors.txt", $colors);

            $type_node = $row['type_node'];
            $pluginFileSystem->write("$path_p-type_node.txt", (string) $type_node);
        }
    }

    if (!$assetFileSystem->fileExists($courseSysPage.'/index.html')) {
        echo 'KO no source on scorm folder';
    } else {
        /* xApi */
        if (2 == $process) {
            recurseCopyFolderScoOufs($courseSysPage, $scormfolder);

            if (!$pluginFileSystem->directoryExists("$scormfolder/tincanxjs")) {
                $pluginFileSystem->createDirectory("$scormfolder/tincanxjs");
            }

            $xapiSysPage = $VDB->w_get_path(SYS_PATH).'plugin/';
            $xapiSysPage .= 'CStudio/resources/interfaces/xapi/';

            if ($pluginFileSystem->fileExists("$scormfolder/interfaces/xapi/api.js")) {
                $pluginFileSystem->delete("$scormfolder/interfaces/xapi/api.js");

                $stream = fopen($xapiSysPage.'api.js', 'r');
                $pluginFileSystem->writeStream("$scormfolder/interfaces/xapi/api.js", $stream);
                fclose($stream);

                $stream = fopen($xapiSysPage.'tincan.xml', 'r');
                $pluginFileSystem->writeStream("$scormfolder/tincan.xml", $stream);
                fclose($stream);
            }

            recurseCopyFolderSco($xapiSysPage, $scormfolder.'/tincanxjs');

            $pluginFileSystem->delete("$scormfolder/interfaces/sco/api.js");

            foreach ($pluginFileSystem->listContents($scormSysPage.'/'.$idPageTop, false) as $item) {
                if (!$item->isFile() || !str_contains($item->path(), '.html')) {
                    continue;
                }

                $dataIndex = $pluginFileSystem->read($item->path());
                $xlib = '<script type="text/javascript" src="tincanxjs/tincanjs/build/tincan.js"></script>';
                $xlib .= '<script type="text/javascript" src="tincanxjs/base64.js"></script>';
                $xlib .= '</head>';
                $dataIndex = str_replace('</head>', $xlib, $dataIndex);
                $dataIndex = str_replace('interfaces/sco/', 'interfaces/xapi/', $dataIndex);
                $pluginFileSystem->write($item->path(), $dataIndex);
            }
        } else {
            /* Scorm  ontheweb */
            if (1 == $process || 3 == $process) {
                recurseCopyFolderScoOufs($courseSysPage, $scormfolder);

                foreach ($pluginFileSystem->listContents($scormfolder, false) as $item) {
                    if ($item->isFile() && str_contains($item->path(), '.mp4')) {
                        $pluginFileSystem->delete($item->path());
                    }
                }

                writeLogsTxt('recurseCopyFolderSco('.$courseSysPage.','.$scormfolder.')');

                if (3 == $process) {
                    $pluginFileSystem->delete($scormfolder.'/interfaces/xapi/api.js');
                    $pluginFileSystem->delete($scormfolder.'/interfaces/sco/api.js');
                    $ontheWebSysPage = $VDB->w_get_path(SYS_PATH).'plugin/CStudio/resources/interfaces/ontheweb/api.js';
                    $stream = fopen($ontheWebSysPage, 'rb');
                    $pluginFileSystem->writeStream($scormfolder.'/interfaces/xapi/api.js', $stream);
                    fclose($stream);
                    $stream = fopen($ontheWebSysPage, 'rb');
                    $pluginFileSystem->writeStream($scormfolder.'/interfaces/sco/api.js', $stream);
                    fclose($stream);

                    insertOelToolsLog('interacted', $idPageTop, $idPageTop, 'export_totheweb', 2);
                }
            } else {
                recurseCopyFolderScoOufs($courseSysPage, $scormfolder);
                writeLogsTxt('recurseCopyFolderSco('.$courseSysPage.','.$scormfolder.')');
            }
        }
    }

    if ($pluginFileSystem->fileExists("$scormfolder/index.html")) {
        echo ' OK 2';
    } else {
        echo 'KO';
    }
}

$scormZip = $scormSysPage.'/sco'.$idPageTop.'cs'.$lp_id.'.zip';
$scormWeb = $scormWebPage.'/sco'.$idPageTop.'cs'.$lp_id.'.zip';

if (1 == $process) {
    $scormZip = $scormSysPage.'/project-'.strtolower($local_folder).'.zip';
    $scormWeb = $scormWebPage.'/project-'.strtolower($local_folder).'.zip';
}

if (2 == $process) {
    $scormZip = $scormSysPage.'/xapi-'.strtolower($local_folder).'.zip';
    $scormWeb = $scormWebPage.'/xapi-'.strtolower($local_folder).'.zip';
}

if (3 == $step) {
    sleep(1);
    if ($pluginFileSystem->fileExists($scormZip)) {
        $pluginFileSystem->delete($scormZip);
    }

    if (!$pluginFileSystem->fileExists($scormZip)) {
        echo ' OK 3';
    } else {
        echo 'KO';
    }
}

if (4 == $step && 3 == $process) {
    $scormWebPage = $VDB->w_get_path(WEB_PATH).'plugin/';
    $scormWebPage .= 'CStudio/public_folder/p.php?ontw='.$idPageTop;
    echo $scormWebPage;
}

if (4 == $step && 3 != $process) {
    if (class_exists('ZipArchive')) {
        $zip = new ZipArchive();
        $tmpZip = tempnam(sys_get_temp_dir(), 'scorm');
        $zip->open($tmpZip, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        foreach ($pluginFileSystem->listContents($scormfolder, true) as $item) {
            if ($item->isFile()) {
                $relativePath = substr($item->path(), strlen($scormfolder) + 1);
                $relativePath = str_replace('\\', '/', $relativePath);
                $zip->addFromString($relativePath, $pluginFileSystem->read($item->path()));
            }
        }
        $zip->close();

        $stream = fopen($tmpZip, 'rb');
        $pluginFileSystem->writeStream($scormZip, $stream);
        fclose($stream);
        unlink($tmpZip);

        if ($pluginFileSystem->fileExists($scormZip)) {
            echo $scormWeb;
        } else {
            echo 'KO';
        }

        if (oel_ctr_options('ALC')) {
            // oel_add_dev_logs('inserOelToolsLog("save",'.$id_project.'",'.$idPage.'","'.$logsactions.'",1);');
            if (0 == $process) {
                insertOelToolsLog('interacted', $idPageTop, $idPageTop, 'export_scorm', 2);
            }
            if (1 == $process) {
                insertOelToolsLog('interacted', $idPageTop, $idPageTop, 'export_project', 2);
            }
            if (2 == $process) {
                insertOelToolsLog('interacted', $idPageTop, $idPageTop, 'export_xapi', 2);
            }
        }
    } else {
        echo 'KO ZipArchive is install';
    }
}

if (10 == $step && 3 != $process) {
    $dirsToClean = [
        $scormSysPage.'/'.$idPageTop.'/img_cache',
        $scormSysPage.'/'.$idPageTop.'/img_cache/'.$local_folder,
        $scormSysPage.'/'.$idPageTop.'/exportdata',
        $scormSysPage.'/'.$idPageTop.'/oel-plug',
        $scormSysPage.'/'.$idPageTop.'/audio',
        $scormSysPage.'/'.$idPageTop.'/css',
        $scormSysPage.'/'.$lp_id,
    ];

    foreach ($dirsToClean as $dir) {
        if ($pluginFileSystem->directoryExists($dir)) {
            foreach ($pluginFileSystem->listContents($dir, false) as $item) {
                if ($item->isFile()) {
                    $pluginFileSystem->delete($item->path());
                }
            }
        }
    }

    $pluginFileSystem->write($scormSysPage.'/'.$lp_id.'/index.html', '');
}

function writeLogsTxt($msg): void
{
    // if (file_exists('logs.html')) {
    // $fp = fopen('logs.html','a');
    // fwrite($fp,$msg.'<br>');
    // fclose($fp);
    // }
}
