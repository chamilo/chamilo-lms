<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Framework\Container;
use League\Flysystem\FilesystemOperator;

$cidReset = true;

require_once __DIR__.'/../../main/inc/global.inc.php';

api_block_anonymous_users();

$relativePath = isset($_GET['path']) ? trim((string) $_GET['path']) : '';

if ('' === $relativePath) {
    header('HTTP/1.1 400 Bad Request');
    exit('Missing path.');
}

$relativePath = normalize_relative_storage_path($relativePath);

if (null === $relativePath) {
    header('HTTP/1.1 400 Bad Request');
    exit('Invalid path.');
}

$storageBasePath = rtrim(Container::getProjectDir().'/var/plugins/XApi', '/');
$absolutePath = $storageBasePath.'/'.$relativePath;

if (!is_file($absolutePath) || !is_readable($absolutePath)) {
    restore_runtime_file_from_plugins_filesystem($relativePath, $absolutePath);
}

$realBasePath = realpath($storageBasePath);
$realFilePath = realpath($absolutePath);

if (false === $realBasePath || false === $realFilePath) {
    header('HTTP/1.1 404 Not Found');
    exit('File not found.');
}

$realBasePath = str_replace('\\', '/', $realBasePath);
$realFilePath = str_replace('\\', '/', $realFilePath);

if (0 !== strpos($realFilePath, $realBasePath.'/') && $realFilePath !== $realBasePath) {
    header('HTTP/1.1 403 Forbidden');
    exit('Forbidden.');
}

if (!is_file($realFilePath) || !is_readable($realFilePath)) {
    header('HTTP/1.1 404 Not Found');
    exit('File not found.');
}

$extension = strtolower((string) pathinfo($realFilePath, PATHINFO_EXTENSION));

$finfo = new finfo(FILEINFO_MIME_TYPE);
$contentType = $finfo->file($realFilePath) ?: 'application/octet-stream';

if (is_html_file($contentType, $extension)) {
    $content = file_get_contents($realFilePath);

    if (false === $content) {
        header('HTTP/1.1 500 Internal Server Error');
        exit('Unable to read file.');
    }

    $content = rewrite_html_package_urls($content, $relativePath);

    header('Content-Type: text/html; charset=UTF-8');
    header('Content-Length: '.(string) strlen($content));
    header('Cache-Control: private, max-age=3600');

    echo $content;
    exit;
}

if (is_css_file($contentType, $extension)) {
    $content = file_get_contents($realFilePath);

    if (false === $content) {
        header('HTTP/1.1 500 Internal Server Error');
        exit('Unable to read file.');
    }

    $content = rewrite_css_package_urls($content, $relativePath);

    header('Content-Type: text/css; charset=UTF-8');
    header('Content-Length: '.(string) strlen($content));
    header('Cache-Control: private, max-age=3600');

    echo $content;
    exit;
}

header('Content-Type: '.$contentType);
header('Content-Length: '.(string) filesize($realFilePath));
header('Cache-Control: private, max-age=3600');

readfile($realFilePath);
exit;

/**
 * Normalize a storage-relative path and prevent traversal.
 */
function normalize_relative_storage_path(string $path): ?string
{
    $path = str_replace('\\', '/', trim($path));
    $path = ltrim($path, '/');

    if ('' === $path) {
        return null;
    }

    $segments = explode('/', $path);
    $normalized = [];

    foreach ($segments as $segment) {
        $segment = trim($segment);

        if ('' === $segment || '.' === $segment) {
            continue;
        }

        if ('..' === $segment) {
            if (empty($normalized)) {
                return null;
            }

            array_pop($normalized);
            continue;
        }

        $normalized[] = $segment;
    }

    if (empty($normalized)) {
        return null;
    }

    return implode('/', $normalized);
}

function is_html_file(string $contentType, string $extension): bool
{
    return \in_array($extension, ['html', 'htm', 'xhtml'], true)
        || str_contains($contentType, 'text/html')
        || str_contains($contentType, 'application/xhtml+xml');
}

function is_css_file(string $contentType, string $extension): bool
{
    return 'css' === $extension || str_contains($contentType, 'text/css');
}

/**
 * Restore a runtime file from the persistent plugins filesystem when the local
 * runtime copy does not exist.
 */
function restore_runtime_file_from_plugins_filesystem(string $relativePath, string $absolutePath): void
{
    $pluginsFilesystem = get_plugins_filesystem();

    if (null === $pluginsFilesystem) {
        return;
    }

    $storagePath = 'XApi/'.ltrim($relativePath, '/');

    try {
        if (!$pluginsFilesystem->fileExists($storagePath)) {
            return;
        }

        $stream = $pluginsFilesystem->readStream($storagePath);

        if (!is_resource($stream)) {
            return;
        }

        $directory = dirname($absolutePath);
        if (!is_dir($directory)) {
            mkdir($directory, api_get_permissions_for_new_directories(), true);
        }

        $target = @fopen($absolutePath, 'wb');
        if (false === $target) {
            fclose($stream);
            return;
        }

        stream_copy_to_stream($stream, $target);
        fclose($stream);
        fclose($target);
        @chmod($absolutePath, api_get_permissions_for_new_files());
    } catch (Throwable $throwable) {
        error_log('[XApi][package_asset][restore] '.$throwable->getMessage());
    }
}

function get_plugins_filesystem(): ?FilesystemOperator
{
    if (!Container::$container->has('oneup_flysystem.plugins_filesystem')) {
        return null;
    }

    $filesystem = Container::$container->get('oneup_flysystem.plugins_filesystem');

    return $filesystem instanceof FilesystemOperator ? $filesystem : null;
}

/**
 * Rewrite relative asset URLs inside HTML so they continue to work when served
 * through package_asset.php?path=...
 */
function rewrite_html_package_urls(string $html, string $currentRelativePath): string
{
    $attributes = ['src', 'href', 'action', 'poster', 'data'];

    foreach ($attributes as $attribute) {
        $pattern = '/('.$attribute.'\s*=\s*)(["\'])(.*?)\2/i';

        $html = preg_replace_callback(
            $pattern,
            static function (array $matches) use ($currentRelativePath): string {
                $originalValue = $matches[3];

                if (!should_rewrite_asset_reference($originalValue)) {
                    return $matches[0];
                }

                $rewritten = build_package_asset_url($currentRelativePath, $originalValue);

                return $matches[1].$matches[2].htmlspecialchars($rewritten, ENT_QUOTES).$matches[2];
            },
            $html
        ) ?? $html;
    }

    return $html;
}

/**
 * Rewrite relative url(...) references inside CSS files.
 */
function rewrite_css_package_urls(string $css, string $currentRelativePath): string
{
    $pattern = '/url\((["\']?)(.*?)\1\)/i';

    $css = preg_replace_callback(
        $pattern,
        static function (array $matches) use ($currentRelativePath): string {
            $originalValue = trim($matches[2]);

            if (!should_rewrite_asset_reference($originalValue)) {
                return $matches[0];
            }

            $rewritten = build_package_asset_url($currentRelativePath, $originalValue);

            return 'url('.$matches[1].$rewritten.$matches[1].')';
        },
        $css
    ) ?? $css;

    return $css;
}

function should_rewrite_asset_reference(string $value): bool
{
    $value = trim($value);

    if ('' === $value) {
        return false;
    }

    if (
        str_starts_with($value, '#') ||
        str_starts_with($value, 'data:') ||
        str_starts_with($value, 'mailto:') ||
        str_starts_with($value, 'tel:') ||
        str_starts_with($value, 'javascript:') ||
        str_starts_with($value, 'about:') ||
        str_starts_with($value, 'blob:')
    ) {
        return false;
    }

    if (preg_match('#^(?:[a-z][a-z0-9+\-.]*:)?//#i', $value)) {
        return false;
    }

    if (str_starts_with($value, '/')) {
        return false;
    }

    return true;
}

function build_package_asset_url(string $currentRelativePath, string $assetReference): string
{
    $parsed = parse_url($assetReference);

    $assetPath = $parsed['path'] ?? '';
    $assetQuery = $parsed['query'] ?? '';
    $assetFragment = $parsed['fragment'] ?? '';

    $currentDirectory = str_replace('\\', '/', dirname($currentRelativePath));
    if ('.' === $currentDirectory) {
        $currentDirectory = '';
    }

    $combinedPath = '' !== $currentDirectory
        ? $currentDirectory.'/'.$assetPath
        : $assetPath;

    $normalizedPath = normalize_relative_storage_path($combinedPath);

    if (null === $normalizedPath) {
        return $assetReference;
    }

    $query = $_GET;
    unset($query['path']);
    $query['path'] = $normalizedPath;

    if ('' !== $assetQuery) {
        parse_str($assetQuery, $assetQueryParams);
        if (!empty($assetQueryParams) && is_array($assetQueryParams)) {
            $query = array_merge($query, $assetQueryParams);
        }
    }

    $url = api_get_path(WEB_PLUGIN_PATH).'XApi/package_asset.php?'.http_build_query(
            $query,
            '',
            '&',
            PHP_QUERY_RFC3986
        );

    if ('' !== $assetFragment) {
        $url .= '#'.$assetFragment;
    }

    return $url;
}
