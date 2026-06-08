<?php

/* For licensing terms, see /license.txt */

/**
 * Responses to AJAX calls.
 */
require_once '../config.php';

api_protect_admin_script();

$plugin = CleanDeletedFilesPlugin::create();
$action = isset($_REQUEST['a']) ? $_REQUEST['a'] : null;

/**
 * Resolves a client-supplied path to a safe, canonical file path.
 *
 * Only regular files whose name contains "DELETED" and that live inside the
 * directories the plugin actually scans (app/courses, app/upload) are allowed.
 * Returns the canonical path on success, or null when the path is not allowed.
 *
 * @param string|null $path
 *
 * @return string|null
 */
function cleanDeletedFilesResolvePath($path)
{
    if (!is_string($path) || "" === $path) {
        return null;
    }

    // The front-end may send the path prefixed with the file:// scheme.
    if (0 === strpos($path, "file://")) {
        $path = substr($path, 7);
    }

    $realPath = realpath($path);
    if (false === $realPath || !is_file($realPath)) {
        return null;
    }

    // Only files flagged as DELETED by Chamilo may be removed here.
    if (false === strpos(basename($realPath), "DELETED")) {
        return null;
    }

    $allowedDirs = [
        realpath(api_get_path(SYS_PATH)."app/courses"),
        realpath(api_get_path(SYS_PATH)."app/upload"),
    ];

    foreach ($allowedDirs as $allowedDir) {
        if (false !== $allowedDir
            && 0 === strpos($realPath, $allowedDir.DIRECTORY_SEPARATOR)
        ) {
            return $realPath;
        }
    }

    return null;
}

switch ($action) {
    case 'delete-file':
        $path = $_REQUEST['path'] ?? null;
        if (empty($path)) {
            echo json_encode(["status" => "false", "message" => $plugin->get_lang('ErrorEmptyPath')]);

            exit;
        }

        $realPath = cleanDeletedFilesResolvePath($path);

        if (null === $realPath) {
            echo json_encode(["status" => "false", "message" => $plugin->get_lang('ErrorDeleteFile')]);

            exit;
        }

        if (unlink($realPath)) {
            Display::addFlash($plugin->get_lang("DeletedSuccess"), 'success');
            echo json_encode(["status" => "true"]);
        } else {
            echo json_encode(["status" => "false", "message" => $plugin->get_lang('ErrorDeleteFile')]);
        }

        break;
    case 'delete-files-list':
        $list = $_REQUEST['list'] ?? [];

        if (empty($list)) {
            echo json_encode(["status" => "false", "message" => $plugin->get_lang('ErrorEmptyPath')]);

            exit;
        }

        foreach ($list as $value) {
            $realPath = cleanDeletedFilesResolvePath($value);

            if (null !== $realPath) {
                unlink($realPath);
            }
        }

        Display::addFlash($plugin->get_lang("DeletedSuccess"), 'success');
        echo json_encode(["status" => "true"]);
        break;
}
