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
    'bmp' => ['image/bmp', ['image/bmp', 'image/x-bmp', 'image/x-ms-bmp'], false],
    'ico' => ['image/x-icon', ['image/x-icon', 'image/vnd.microsoft.icon'], false],
    // Audio / Vídeo
    'mp3' => ['audio/mpeg', ['audio/mpeg'], false],
    'mp4' => ['video/mp4', ['video/mp4'], false],
    // Documentos inline
    'pdf' => ['application/pdf', ['application/pdf'], false],
    'html' => ['text/html; charset=utf-8', ['text/html', 'text/plain'], false],
    'css' => ['text/css', ['text/plain', 'text/css'], false],
    'js' => ['application/javascript', ['text/plain', 'application/javascript'], false],
    'json' => ['application/json', ['text/plain', 'application/json'], false],
    'txt' => ['text/plain', ['text/plain'], false],
    // Office OpenXML (son ZIPs internamente)
    'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ['application/zip', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'], true],
    'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ['application/zip', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'], true],
    'pptx' => ['application/vnd.openxmlformats-officedocument.presentationml.presentation',
        ['application/zip', 'application/vnd.openxmlformats-officedocument.presentationml.presentation'], true],
    // OpenDocument
    'odt' => ['application/vnd.oasis.opendocument.text',
        ['application/zip', 'application/vnd.oasis.opendocument.text'], true],
    'ods' => ['application/vnd.oasis.opendocument.spreadsheet',
        ['application/zip', 'application/vnd.oasis.opendocument.spreadsheet'], true],
    'odp' => ['application/vnd.oasis.opendocument.presentation',
        ['application/zip', 'application/vnd.oasis.opendocument.presentation'], true],
    'otp' => ['application/vnd.oasis.opendocument.presentation-template',
        ['application/zip'], true],
];

$ext = strtolower(pathinfo($requestedPath, \PATHINFO_EXTENSION));
if (!isset($allowedExtensions[$ext])) {
    http_response_code(403);

    exit;
}

$fsPath = 'CStudio/editor/img_cache/'.$requestedPath;
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
