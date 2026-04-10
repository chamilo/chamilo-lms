<?php

declare(strict_types=1);

require_once __DIR__.'/../../main/inc/global.inc.php';

use Chamilo\CoreBundle\Framework\Container;

if (api_is_anonymous()) {
    http_response_code(403);

    exit;
}

$requestedPath = $_GET['path'] ?? '';

if ('' === $requestedPath || str_contains($requestedPath, "\0")) {
    http_response_code(400);

    exit;
}

$requestedPath = trim(str_replace('\\', '/', $requestedPath), '/');

foreach (explode('/', $requestedPath) as $segment) {
    if ('' === $segment || '.' === $segment || '..' === $segment) {
        http_response_code(400);

        exit;
    }
}

$allowedExtensions = [
    'zip' => 'application/zip',
];

$ext = strtolower(pathinfo($requestedPath, \PATHINFO_EXTENSION));

if (!isset($allowedExtensions[$ext])) {
    http_response_code(403);

    exit;
}

$fsPath = 'CStudio/editor/sco_cache/'.$requestedPath;

$pluginFileSystem = Container::getPluginsFileSystem();

if (!$pluginFileSystem->fileExists($fsPath)) {
    http_response_code(404);

    exit;
}

$mimeType = $allowedExtensions[$ext];
$fileSize = $pluginFileSystem->fileSize($fsPath);

header('Content-Type: '.$mimeType);
header('Content-Length: '.$fileSize);
header('Cache-Control: private, max-age=3600');
header('X-Content-Type-Options: nosniff');
header('Content-Disposition: attachment; filename="'.basename($requestedPath).'"');

$stream = $pluginFileSystem->readStream($fsPath);

fpassthru($stream);
fclose($stream);
