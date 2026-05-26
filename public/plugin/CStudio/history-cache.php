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

$requestedPath = ltrim(str_replace('\\', '/', $requestedPath), '/');

foreach (explode('/', $requestedPath) as $segment) {
    if ('' === $segment || '.' === $segment || '..' === $segment) {
        http_response_code(400);

        exit;
    }
}

$allowedExtensions = [
    // Imágenes
    'jpg' => ['image/jpeg', ['image/jpeg'], false],
    'jpeg' => ['image/jpeg', ['image/jpeg'], false],
    'png' => ['image/png', ['image/png'], false],
    'gif' => ['image/gif', ['image/gif'], false],
    'svg' => ['image/svg+xml', ['image/svg+xml', 'text/html', 'text/xml', 'text/plain'], false],
    'webp' => ['image/webp', ['image/webp'], false],
    // Documentos inline
    'pdf' => ['application/pdf', ['application/pdf'], false],
    'html' => ['text/html; charset=utf-8', ['text/html', 'text/plain'], false],
    'css' => ['text/css', ['text/plain', 'text/css'], false],
    'js' => ['application/javascript', ['text/plain', 'application/javascript'], false],
];

$ext = strtolower(pathinfo($requestedPath, \PATHINFO_EXTENSION));

if (!isset($allowedExtensions[$ext])) {
    http_response_code(403);

    exit;
}

$fsPath = 'CStudio/editor/history_cache/'.$requestedPath;
$pluginFileSystem = Container::getPluginsFileSystem();

if (!$pluginFileSystem->fileExists($fsPath)) {
    http_response_code(404);

    exit;
}

$stream = $pluginFileSystem->readStream($fsPath);
$headerBytes = fread($stream, 8192);
fclose($stream);

$finfo = new finfo(\FILEINFO_MIME_TYPE);
$detectedMime = $finfo->buffer($headerBytes);

[$contentType, $allowedMimes, $forceDownload] = $allowedExtensions[$ext];

if (!in_array($detectedMime, $allowedMimes, true)) {
    http_response_code(403);

    exit;
}

$fileSize = $pluginFileSystem->fileSize($fsPath);

header('Content-Type: '.$contentType);
header('Content-Length: '.$fileSize);
header('Cache-Control: private, max-age=3600');
header('X-Content-Type-Options: nosniff');

if ($forceDownload) {
    header('Content-Disposition: attachment; filename="'.basename($requestedPath).'"');
}

$stream = $pluginFileSystem->readStream($fsPath);

fpassthru($stream);
fclose($stream);
