<?php

declare(strict_types=1);

/**
 * This file contains the functions used by the Chamidoc.
 *
 * @version 09/04/2026
 */

use Chamilo\CoreBundle\Framework\Container;

if (isset($_POST['id']) || isset($_GET['id'])) {
    if (
        isset($_POST['ur']) || isset($_GET['ur'])
    ) {
        require_once __DIR__.'/../0_dal/dal.global_lib.php';

        require_once '../0_dal/dal.vdatabase.php';
        $VDB = new VirtualDatabase();

        require_once '../0_dal/dal.save.php';

        require_once '../0_dal/dal.chamidoc_object.php';

        require_once 'inc/functions.php';

        if ($VDB->w_api_is_anonymous()) {
            echo 'KO';

            exit;
        }

        $fileSystem = Container::getAssetRepository()->getFileSystem();

        $idPage = get_int_from('id');
        $rawUr = get_string_direct_from('ur');
        $localFolder = get_local_folder($idPage);

        // If the URL points to the proxy img-cache.php, extract the 'path' param directly
        // before any sanitation (get_string_from removes '?' and corrupts the URL)
        if (str_contains($rawUr, 'img-cache.php')) {
            $parsed = parse_url($rawUr);
            parse_str($parsed['query'] ?? '', $queryParams);
            $imgCachePath = rawurldecode($queryParams['path'] ?? '');

            if ('' === $imgCachePath || str_contains($imgCachePath, '..') || str_contains($imgCachePath, "\0")) {
                echo 'error invalid path';

                exit;
            }

            $ur = basename($imgCachePath);
            $courseOrigineRel = 'CStudio/editor/img_cache/'.$imgCachePath;
        } else {
            $ur = basename(get_string_from('ur'));
            $courseOrigineRel = 'CStudio/editor/'.strtolower($ur);
        }

        $nameFile = get_clean_idstring(basename($ur));

        $pluginFileSystem = Container::getPluginsFileSystem();

        $lp_id = get_lp_Id($idPage);

        $course_dir = get_directory($lp_id);

        /*if ('' == $course_dir) {
         * echo 'Course_dir is empty ! Error ';
         * exit;
         * } */
        $courseDestination = '/scorm/'."$localFolder.zip/$localFolder";

        if (!$fileSystem->directoryExists("$courseDestination/img_cache")) {
            $fileSystem->createDirectory("$courseDestination/img_cache");
        }

        if (!$pluginFileSystem->fileExists($courseOrigineRel)) {
            $courseOrigineRel = 'CStudio/editor/img_cache/'.$localFolder.'/'.strtolower($ur);
            $courseDestination = "/scorm/$localFolder.zip/$localFolder/img_cache/".$localFolder;
        }

        $courseDestinationUrl = $courseDestination.'/'.$ur;

        echo $courseOrigineRel.' copy '.$courseDestinationUrl.'<br>';

        $stream = $pluginFileSystem->readStream($courseOrigineRel);
        $fileSystem->writeStream($courseDestinationUrl, $stream);

        if (!$fileSystem->fileExists($courseDestinationUrl)) {
            $stream = $pluginFileSystem->readStream($courseOrigineRel);
            $fileSystem->writeStream($courseDestinationUrl, $stream);
        }

        if ($fileSystem->fileExists($courseDestinationUrl)) {
            echo 'OK';
        } else {
            echo 'error ';
        }
    } else {
        echo 'error no data';
    }
} else {
    echo 'error no id';
}
