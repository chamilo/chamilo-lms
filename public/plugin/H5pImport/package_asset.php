<?php
/* For licensing terms, see /license.txt */

$course_plugin = 'h5pimport';
require_once __DIR__.'/config.php';

use Chamilo\PluginBundle\H5pImport\H5pImporter\H5pPackageTools;
use Symfony\Component\Mime\MimeTypes;

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

$storageBasePath = rtrim(H5pPackageTools::getStorageBasePath(), '/');
$absolutePath = $storageBasePath.'/'.$relativePath;
$realStorageBasePath = realpath($storageBasePath);
$realAbsolutePath = realpath($absolutePath);

if (
    false === $realStorageBasePath
    || false === $realAbsolutePath
    || !str_starts_with($realAbsolutePath, $realStorageBasePath.'/')
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
