<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Framework\Container;
use League\Flysystem\FilesystemException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

require_once __DIR__.'/../../main/inc/global.inc.php';

api_block_anonymous_users();
api_protect_course_script();

$httpRequest = Container::getRequest();

$relativePath = $httpRequest->query->all()['path'] ?? '';
$relativePath = is_string($relativePath) ? trim($relativePath) : '';

if ('' === $relativePath) {
    (new Response('Missing path.', Response::HTTP_BAD_REQUEST))->send();

    exit;
}

$relativePath = normalize_relative_storage_path($relativePath);

if (null === $relativePath) {
    (new Response('Invalid path.', Response::HTTP_BAD_REQUEST))->send();

    exit;
}

// Only serve packages stored for the course the user has access to.
if (!str_starts_with($relativePath, 'course_'.api_get_course_int_id().'/')) {
    (new Response('Forbidden.', Response::HTTP_FORBIDDEN))->send();

    exit;
}

$pluginsFilesystem = Container::getPluginsFileSystem();
$storagePath = 'XApi/'.$relativePath;

try {
    if (!$pluginsFilesystem->fileExists($storagePath)) {
        (new Response('File not found.', Response::HTTP_NOT_FOUND))->send();

        exit;
    }

    $contentType = $pluginsFilesystem->mimeType($storagePath);
} catch (FilesystemException) {
    $contentType = 'application/octet-stream';
}

$extension = strtolower(pathinfo($relativePath, \PATHINFO_EXTENSION));

$isHtml = is_html_file($contentType, $extension);

if ($isHtml || is_css_file($contentType, $extension)) {
    try {
        $content = $pluginsFilesystem->read($storagePath);
    } catch (FilesystemException) {
        (new Response('Unable to read file.', Response::HTTP_INTERNAL_SERVER_ERROR))->send();

        exit;
    }

    $content = $isHtml
        ? rewrite_html_package_urls($content, $relativePath)
        : rewrite_css_package_urls($content, $relativePath);

    $response = new Response(
        $content,
        Response::HTTP_OK,
        [
            'Content-Type' => ($isHtml ? 'text/html' : 'text/css').'; charset=UTF-8',
            'Content-Length' => (string) strlen($content),
        ]
    );
} else {
    try {
        $stream = $pluginsFilesystem->readStream($storagePath);
        $fileSize = $pluginsFilesystem->fileSize($storagePath);
    } catch (FilesystemException) {
        (new Response('Unable to read file.', Response::HTTP_INTERNAL_SERVER_ERROR))->send();

        exit;
    }

    $response = new StreamedResponse(
        static function () use ($stream): void {
            fpassthru($stream);
            fclose($stream);
        },
        Response::HTTP_OK,
        [
            'Content-Type' => $contentType,
            'Content-Length' => (string) $fileSize,
        ]
    );
}

$response->setPrivate();
$response->setMaxAge(3600);
$response->prepare($httpRequest);
$response->send();
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

    $query = Container::getRequest()->query->all();
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
