<?php

declare(strict_types=1);

use Chamilo\CoreBundle\Framework\Container;
use League\Flysystem\FilesystemException;

require_once __DIR__.'/../../0_dal/dal.global_lib.php';

error_reporting(\E_ERROR | \E_PARSE);

ini_set('max_execution_time', 700);

require_once __DIR__.'/../../inc/tranformSource.php';

require_once __DIR__.'/../../ajax/inc/functions.php';

require_once __DIR__.'/../../0_dal/dal.vdatabase.php';

$VDB = new VirtualDatabase();

require_once __DIR__.'/../../0_dal/dal.save.php';

require_once __DIR__.'/../../0_dal/dal.insert.php';

require_once __DIR__.'/../../0_dal/dal.getpaths.php';

require_once __DIR__.'/../../inc/csrf_token.php';

$version = '3209';
$idPage = 0;
$action = isset($_GET['action']) ? $VDB->remove_XSS($_GET['action']) : 'step1';
$log = isset($_GET['log']) ? $VDB->remove_XSS($_GET['log']) : 0;

$oel_token = isset($_GET['cotk']) ? $_GET['cotk'] : '';
$cotk = $oel_token;
$iduser = $VDB->w_api_get_user_id();

if ('copyimages' == $action || 'copydocuments' == $action || 'copyvideos' == $action) {
    $tmpImgCache = isset($_GET['tmpImgCache']) ? $_GET['tmpImgCache'] : '';
    $oldFolder = isset($_GET['oldFolder']) ? $_GET['oldFolder'] : '';
    $filePathNg = isset($_GET['filePathNg']) ? $_GET['filePathNg'] : '';

    if ($tmpImgCache && $oldFolder && $filePathNg) {
        $sourceDir = $tmpImgCache.'/';
        $result = recurseCopyFolderTmp($sourceDir, $filePathNg, $action);

        echo $sourceDir.'<br>';
        echo $filePathNg.'<br>';

        $message = '';

        switch ($action) {
            case 'copyimages':
                $message = 'Images copied successfully';

                break;

            case 'copydocuments':
                $message = 'PDF and MP3 files copied successfully';

                break;

            case 'copyvideos':
                $message = 'MP4 videos copied successfully';

                break;
        }

        echo json_encode(['status' => 'success', 'message' => $message, 'details' => $result]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Missing parameters']);
    }

    exit;
}

function recurseCopyFolderTmp(string $src, string $dst, string $action = 'copyimages'): array
{
    $pluginFileSystem = Container::getPluginsFileSystem();
    $result = ['copied' => 0, 'skipped' => 0, 'errors' => []];

    $allowedExtensions = match ($action) {
        'copydocuments' => ['pdf', 'mp3'],
        'copyvideos' => ['mp4'],
        default => ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'svg', 'ico', 'tiff', 'tif'],
    };

    if (!$pluginFileSystem->directoryExists($src)) {
        $result['errors'][] = "Source path does not exist: $src";

        return $result;
    }

    if (!$pluginFileSystem->directoryExists($dst)) {
        $pluginFileSystem->createDirectory($dst);
    }

    foreach ($pluginFileSystem->listContents($src, true) as $item) {
        if ($item->isDir() || 'Thumbs.db' === basename($item->path())) {
            continue;
        }

        $relativePath = substr($item->path(), strlen($src) + 1);
        $ext = strtolower(pathinfo($relativePath, \PATHINFO_EXTENSION));

        if (!in_array($ext, $allowedExtensions, true)) {
            $result['skipped']++;

            continue;
        }

        $dstFile = $dst.'/'.basename($item->path());

        try {
            $pluginFileSystem->copy($item->path(), $dstFile);
            $result['copied']++;
        } catch (FilesystemException $e) {
            $result['errors'][] = 'Failed to copy: '.$item->path().' to '.$dstFile;
        }
    }

    return $result;
}
