<?php
/* For licensing terms, see /license.txt */

$course_plugin = 'h5pimport';
require_once __DIR__.'/config.php';

use Chamilo\PluginBundle\H5pImport\H5pImporter\H5pPackageTools;

api_block_anonymous_users();

$rawPath = isset($_GET['path']) ? (string) $_GET['path'] : '';

if ('' === $rawPath && isset($_SERVER['PATH_INFO']) && '' !== (string) $_SERVER['PATH_INFO']) {
    $rawPath = (string) $_SERVER['PATH_INFO'];
}

if ('' === $rawPath && isset($_SERVER['REQUEST_URI'])) {
    $requestUri = (string) $_SERVER['REQUEST_URI'];
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';

    $position = strpos($requestUri, $scriptName);
    if (false !== $position) {
        $rawPath = substr($requestUri, $position + strlen($scriptName));
        $queryPos = strpos($rawPath, '?');
        if (false !== $queryPos) {
            $rawPath = substr($rawPath, 0, $queryPos);
        }
    }
}

$rawPath = ltrim($rawPath, '/');

/*
 * When H5P appends "?ver=..." to a URL that already contains "?path=...",
 * the version suffix becomes part of the path value. Strip it here.
 */
$verPos = strpos($rawPath, '?');
if (false !== $verPos) {
    $rawPath = substr($rawPath, 0, $verPos);
}

$relativePath = H5pPackageTools::normalizeRelativeStoragePath($rawPath);

if (null === $relativePath) {
    header('HTTP/1.1 404 Not Found');
    exit;
}

$pluginStoragePath = H5pPackageTools::normalizePluginStorageRelativePath($relativePath);

if (null === $pluginStoragePath) {
    header('HTTP/1.1 404 Not Found');
    exit;
}

$mimeType = 'application/octet-stream';
$contentLength = null;
$stream = false;

try {
    if (H5pPackageTools::persistentFileExists($pluginStoragePath)) {
        $stream = H5pPackageTools::readPersistentStream($pluginStoragePath);
        $contentLength = H5pPackageTools::getPersistentFileSize($pluginStoragePath);
        $mimeType = H5pPackageTools::getPersistentMimeType($pluginStoragePath) ?: $mimeType;
    }
} catch (Throwable $e) {
    error_log('[H5pImport][package_asset][flysystem] '.$e->getMessage().' in '.$e->getFile().':'.$e->getLine());
    $stream = false;
}

if (false === $stream) {
    header('HTTP/1.1 404 Not Found');
    exit;
}

if (str_ends_with($pluginStoragePath, '.css')) {
    $mimeType = 'text/css';
} elseif (str_ends_with($pluginStoragePath, '.js')) {
    $mimeType = 'application/javascript';
}

header('Content-Type: '.$mimeType);

if (null !== $contentLength) {
    header('Content-Length: '.(string) $contentLength);
}

header('Cache-Control: public, max-age=31536000');
header('X-Content-Type-Options: nosniff');

fpassthru($stream);
fclose($stream);
exit;
