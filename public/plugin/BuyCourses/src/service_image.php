<?php

declare(strict_types=1);
/* For license terms, see /license.txt */

use Chamilo\CoreBundle\Framework\Container;

$cidReset = true;

require_once '../../../main/inc/global.inc.php';

$filename = isset($_GET['f']) ? (string) $_GET['f'] : '';

if (empty($filename) || 1 !== preg_match('/^[A-Za-z0-9._-]+$/', $filename)) {
    http_response_code(404);
    exit;
}

$storagePath = 'BuyCourses/services/images/'.$filename;

$pluginsFilesystem = Container::getPluginsFileSystem();

if (!$pluginsFilesystem->fileExists($storagePath)) {
    http_response_code(404);
    exit;
}

$content = $pluginsFilesystem->read($storagePath);
$mimeType = $pluginsFilesystem->mimeType($storagePath) ?: 'image/png';

header('Content-Type: '.$mimeType);
header('Cache-Control: public, max-age=3600');
echo $content;
exit;
