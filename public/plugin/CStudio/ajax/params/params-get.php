<?php

declare(strict_types=1);
/**
 * This file contains the functions used by the OeL plugin.
 *
 * @version 18/05/2024
 */

use Chamilo\CoreBundle\Framework\Container;
use Symfony\Component\HttpFoundation\File\UploadedFile;

require_once '../../0_dal/dal.global_lib.php';

require_once '../../0_dal/dal.vdatabase.php';
$VDB = new VirtualDatabase();

require_once __DIR__.'/../inc/functions.php';

ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);

if (!isset($_GET['idteach'])) {
    exit;
}

$step = isset($_GET['step']) ? (int) $_GET['step'] : 0;
$idPageTop = get_int_from('idteach');

if (false == oel_ctr_rights($idPageTop)) {
    echo 'KO';

    exit;
}

$table = 'plugin_oel_tools_teachdoc';

if (0 == $step) {
    $sql = 'SELECT options FROM plugin_oel_tools_teachdoc ';
    $sql .= 'WHERE id = '.$idPageTop;
    $options = $VDB->get_value_by_query($sql, 'options');
    echo $options;
}

if (1 == $step) {
    $opt = get_string_from('opt');
    $VDB->update($table, ['options' => $opt], ['id = ?' => $idPageTop]);
    cstudio_sync_lp_thumbnail_from_project_options($idPageTop, $opt);
}

if (4 == $step) {
    $idPage = get_int_from('idpg');

    if (isset($_GET['opt']) && '' !== $_GET['opt']) {
        $opt = get_string_from('opt');
        $VDB->update($table, ['options' => $opt], ['id = ? AND id_parent = ?' => [$idPage, $idPageTop]]);
    }
}

function cstudio_sync_lp_thumbnail_from_project_options(int $idPageTop, string $options): void
{
    if ($idPageTop <= 0) {
        return;
    }

    $lpId = cstudio_get_lp_id_from_project($idPageTop);
    if ($lpId <= 0) {
        return;
    }

    try {
        $lpRepository = Container::getLpRepository();
        $lp = $lpRepository->find($lpId);
        if (!$lp || !method_exists($lp, 'getResourceNode')) {
            return;
        }

        $resourceNode = $lp->getResourceNode();
        if (!$resourceNode) {
            return;
        }

        $imageValue = cstudio_get_project_thumbnail_value($options);

        if ('' === $imageValue) {
            cstudio_remove_lp_resource_files($resourceNode);

            return;
        }

        $imageInfo = cstudio_resolve_project_thumbnail_file($imageValue);
        if (null === $imageInfo) {
            return;
        }

        $tmpFile = cstudio_create_temp_file_from_plugin_resource($imageInfo['path'], $imageInfo['extension']);
        if ('' === $tmpFile) {
            return;
        }

        cstudio_remove_lp_resource_files($resourceNode);

        $uploadedFile = new UploadedFile(
            $tmpFile,
            $imageInfo['filename'],
            $imageInfo['mime_type'],
            null,
            true
        );

        $lpRepository->addFile($lp, $uploadedFile);
        $lpRepository->update($lp);

        if (is_file($tmpFile)) {
            @unlink($tmpFile);
        }
    } catch (Throwable $exception) {
        error_log('CStudio thumbnail sync failed: '.$exception->getMessage());
    }
}

function cstudio_get_lp_id_from_project(int $idPageTop): int
{
    $virtualDatabase = new VirtualDatabase();
    $sql = 'SELECT lp_id FROM plugin_oel_tools_teachdoc ';
    $sql .= 'WHERE id = '.$idPageTop;

    return (int) $virtualDatabase->get_value_by_query($sql, 'lp_id');
}

function cstudio_get_project_thumbnail_value(string $options): string
{
    $parts = explode('@', $options);

    return trim((string) ($parts[0] ?? ''));
}

/**
 * @return array{path: string, filename: string, extension: string, mime_type: string}|null
 */
function cstudio_resolve_project_thumbnail_file(string $imageValue): ?array
{
    $rawPath = cstudio_extract_project_thumbnail_path($imageValue);
    if ('' === $rawPath) {
        return null;
    }

    $rawPath = str_replace('\\', '/', $rawPath);
    $rawPath = rawurldecode($rawPath);
    $rawPath = ltrim($rawPath, '/');

    $rawPath = preg_replace('#^web_plugin\|CStudio/#', '', $rawPath);
    $rawPath = preg_replace('#^plugin/CStudio/#', '', $rawPath);
    $rawPath = preg_replace('#^CStudio/#', '', $rawPath);

    $candidatePaths = [];

    if (str_starts_with($rawPath, 'editor/img_cache/')) {
        $candidatePaths[] = 'CStudio/'.$rawPath;
    }

    if (str_starts_with($rawPath, 'img_cache/')) {
        $candidatePaths[] = 'CStudio/editor/'.$rawPath;
        $candidatePaths[] = 'CStudio/editor/img_cache/'.substr($rawPath, strlen('img_cache/'));
    }

    $candidatePaths[] = 'CStudio/editor/img_cache/'.$rawPath;

    foreach (array_unique($candidatePaths) as $candidatePath) {
        $normalizedPath = cstudio_normalize_plugin_thumbnail_path($candidatePath);
        if ('' === $normalizedPath) {
            continue;
        }

        $extension = strtolower(pathinfo($normalizedPath, PATHINFO_EXTENSION));
        $mimeType = cstudio_allowed_thumbnail_mime_type($extension);
        if ('' === $mimeType) {
            continue;
        }

        $pluginFileSystem = Container::getPluginsFileSystem();
        if (!$pluginFileSystem->fileExists($normalizedPath)) {
            continue;
        }

        return [
            'path' => $normalizedPath,
            'filename' => basename($normalizedPath),
            'extension' => $extension,
            'mime_type' => $mimeType,
        ];
    }

    return null;
}

function cstudio_extract_project_thumbnail_path(string $imageValue): string
{
    $imageValue = trim($imageValue);

    if ('' === $imageValue) {
        return '';
    }

    if (str_contains($imageValue, 'img-cache.php')) {
        $imageValue = preg_replace('/img-cache\\.php([^?])/', 'img-cache.php?$1', $imageValue);
        $parsed = parse_url($imageValue);
        parse_str($parsed['query'] ?? '', $queryParams);

        return trim((string) ($queryParams['path'] ?? ''));
    }

    return $imageValue;
}

function cstudio_normalize_plugin_thumbnail_path(string $path): string
{
    $path = ltrim(str_replace('\\', '/', $path), '/');

    if (!str_starts_with($path, 'CStudio/editor/img_cache/')) {
        return '';
    }

    $segments = explode('/', $path);
    foreach ($segments as $segment) {
        if ('' === $segment || '.' === $segment || '..' === $segment || str_contains($segment, "\0")) {
            return '';
        }
    }

    return $path;
}

function cstudio_allowed_thumbnail_mime_type(string $extension): string
{
    return match ($extension) {
        'jpg', 'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
        'webp' => 'image/webp',
        default => '',
    };
}

function cstudio_create_temp_file_from_plugin_resource(string $pluginPath, string $extension): string
{
    $pluginFileSystem = Container::getPluginsFileSystem();
    if (!$pluginFileSystem->fileExists($pluginPath)) {
        return '';
    }

    $tmpFile = tempnam(sys_get_temp_dir(), 'cstudio_lp_thumbnail_');
    if (false === $tmpFile) {
        return '';
    }

    $tmpFileWithExtension = $tmpFile.'.'.$extension;

    try {
        $stream = $pluginFileSystem->readStream($pluginPath);
        $target = fopen($tmpFileWithExtension, 'wb');

        if (!is_resource($stream) || !is_resource($target)) {
            if (is_resource($stream)) {
                fclose($stream);
            }
            if (is_resource($target)) {
                fclose($target);
            }

            @unlink($tmpFile);
            @unlink($tmpFileWithExtension);

            return '';
        }

        stream_copy_to_stream($stream, $target);
        fclose($stream);
        fclose($target);
        @unlink($tmpFile);

        return $tmpFileWithExtension;
    } catch (Throwable $exception) {
        @unlink($tmpFile);
        @unlink($tmpFileWithExtension);
        error_log('CStudio thumbnail temporary file creation failed: '.$exception->getMessage());

        return '';
    }
}

function cstudio_remove_lp_resource_files(object $resourceNode): void
{
    if (!method_exists($resourceNode, 'getResourceFiles')) {
        return;
    }

    $entityManager = Database::getManager();
    $resourceFiles = $resourceNode->getResourceFiles();

    foreach ($resourceFiles as $resourceFile) {
        $entityManager->remove($resourceFile);
    }

    $entityManager->flush();
}
