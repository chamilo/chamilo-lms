<?php
/* For licensing terms, see /license.txt */

$course_plugin = 'h5pimport';
require_once __DIR__.'/config.php';

use Symfony\Component\Mime\MimeTypes;

api_block_anonymous_users();

$relativePath = isset($_GET['path']) ? ltrim((string) $_GET['path'], '/') : '';

if ('' === $relativePath || str_contains($relativePath, '..')) {
    header('HTTP/1.1 404 Not Found');
    exit;
}

$projectRoot = dirname(__DIR__, 3);
$coreBasePath = $projectRoot.'/vendor/h5p/h5p-core';
$absolutePath = $coreBasePath.'/'.$relativePath;

$realCoreBasePath = realpath($coreBasePath);
$realAbsolutePath = realpath($absolutePath);

if (
    false === $realCoreBasePath
    || false === $realAbsolutePath
    || !str_starts_with($realAbsolutePath, $realCoreBasePath.'/')
    || !is_file($realAbsolutePath)
    || !is_readable($realAbsolutePath)
) {
    header('HTTP/1.1 404 Not Found');
    exit;
}

$mimeTypes = new MimeTypes();
$mimeType = $mimeTypes->guessMimeType($realAbsolutePath) ?: 'application/octet-stream';

if (str_ends_with($realAbsolutePath, '.css')) {
    $mimeType = 'text/css';
} elseif (str_ends_with($realAbsolutePath, '.js')) {
    $mimeType = 'application/javascript';
}

header('Content-Type: '.$mimeType);
header('Content-Length: '.(string) filesize($realAbsolutePath));
header('Cache-Control: public, max-age=31536000');
header('X-Content-Type-Options: nosniff');

readfile($realAbsolutePath);
exit;
