<?php

declare(strict_types=1);

/**
 * This file contains the functions used by the Chamidoc.
 *
 * @version 18/05/2024
 */

use Chamilo\CoreBundle\Framework\Container;
use League\Flysystem\FilesystemOperator;

require_once __DIR__.'/../0_dal/dal.global_lib.php';

require_once '../0_dal/dal.vdatabase.php';
$VDB = new VirtualDatabase();

require_once '../0_dal/dal.save.php';

require_once '../0_dal/dal.chamidoc_object.php';

require_once 'inc/functions.php';

$pluginFileSystem = Container::getPluginsFileSystem();

if (isset($_GET['step'])) {
    $idPage = get_int_from('id');
    $namefile = get_string_from('name');

    $localFolder = get_local_folder($idPage);

    $tmpFileRel = 'CStudio/editor/img_cache/tmp/'.$namefile;

    $courseFinalCacheRel = 'CStudio/editor/img_cache/'.strtolower($localFolder).'/'.$namefile;
    $courseFinalFolderRel = 'CStudio/editor/img_cache/'.strtolower($localFolder);

    if (!$pluginFileSystem->directoryExists($courseFinalFolderRel)) {
        $pluginFileSystem->createDirectory($courseFinalFolderRel);
    }

    if ($pluginFileSystem->fileExists($tmpFileRel)) {
        $pluginFileSystem->move($tmpFileRel, $courseFinalCacheRel);
    }

    if (!$pluginFileSystem->fileExists($courseFinalCacheRel) && $pluginFileSystem->fileExists($tmpFileRel)) {
        $pluginFileSystem->copy($tmpFileRel, $courseFinalCacheRel);
    }

    if ($pluginFileSystem->fileExists($courseFinalCacheRel)) {
        echo 'img_cache/'.strtolower($localFolder).'/'.$namefile;
    } else {
        echo 'KO';
    }
} else {
    $idPage = get_int_from('id');
    $modexport = get_string_from('modexport');
    $namefile = 'imgblob_'.uuid(6).'.png';
    $outputFileRel = 'CStudio/editor/img_cache/tmp/'.$namefile;
    $pluginFileSystem->createDirectory('CStudio/editor/img_cache/tmp');

    if (isset($_GET['mypath'])) {
        $safeName = basename((string) $_GET['mypath']);
        if ('' !== $safeName && preg_match('/^[a-zA-Z0-9_\-]+$/', $safeName)) {
            $outputFileRel = 'CStudio/editor/img_cache/tmp/'.$safeName.'.png';
        }
    }

    if ('page' == $modexport) {
        $localFolder = get_local_folder($idPage);
        $namefile = 'thumbnail-studio-'.$idPage.'.png';
        $outputFileRel = 'CStudio/editor/img_cache/'.strtolower($localFolder).'/'.$namefile;
    }

    if (isset($_POST['file64'])) {
        $base64_string = (string) $_POST['file64'];
        base64_to_jpeg_flysystem($base64_string, $outputFileRel, $pluginFileSystem);
    }

    if (isset($_FILES['file'])) {
        if ($_FILES['file']['error'] > 0) {
            echo 'Error: '.$_FILES['file']['error'].'<br>';
        } else {
            $stream = fopen($_FILES['file']['tmp_name'], 'rb');
            $pluginFileSystem->writeStream($outputFileRel, $stream);
            fclose($stream);
        }
    }

    if ($pluginFileSystem->fileExists($outputFileRel)) {
        echo $namefile;
    } else {
        echo 'KO '.$outputFileRel.'  ('.($_FILES['file']['tmp_name'] ?? '').')';
    }
}

function base64_to_jpeg_flysystem(string $base64_string, string $output_file_rel, FilesystemOperator $fs): void
{
    $data = explode(',', $base64_string);
    $decoded = base64_decode($data[1]);
    if (false === $decoded || '' === $decoded) {
        echo 'Error writing to output file.';

        return;
    }
    $fs->write($output_file_rel, $decoded);
}
