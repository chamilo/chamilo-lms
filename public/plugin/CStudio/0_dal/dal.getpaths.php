<?php

declare(strict_types=1);

function api_get_folder_importmp(): string
{
    return 'CStudio/editor/import-project/files/tmp/';
}

function api_get_folder_imporfiles(): string
{
    return 'CStudio/editor/import-project/files/';
}

function api_get_folder_options()
{
    $VDB = new VirtualDatabase();

    return $VDB->w_get_path(SYS_PLUGIN_PATH).'CStudio/options/';
}

function deleteDir($dirPath): void
{
    if (!is_dir($dirPath)) {
        throw new InvalidArgumentException("$dirPath must be a directory");
    }
    if ('/' != substr($dirPath, strlen($dirPath) - 1, 1)) {
        $dirPath .= '/';
    }
    $files = glob($dirPath.'*', \GLOB_MARK);
    foreach ($files as $file) {
        if (is_dir($file)) {
            deleteDir($file);
        } else {
            unlink($file);
            echo ' - unlink '.$file.'<br>';
        }
    }
    rmdir($dirPath);
}
