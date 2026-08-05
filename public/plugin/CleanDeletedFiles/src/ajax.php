<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

/**
 * Responses to AJAX calls.
 */
require_once __DIR__.'/../config.php';

api_protect_admin_script();

header('Content-Type: application/json');

/** @var CleanDeletedFilesPlugin $plugin */
$plugin = CleanDeletedFilesPlugin::create();

$sendJson = static function (array $data): void {
    $data['sec_token'] = Security::get_token();

    echo json_encode($data);
    exit;
};

if (!$plugin->isEnabled() || !api_is_platform_admin()) {
    $sendJson([
        'status' => 'false',
        'message' => get_lang('NotAllowed'),
    ]);
}

if (!Security::check_token('post')) {
    $sendJson([
        'status' => 'false',
        'message' => $plugin->get_lang('ErrorInvalidToken'),
    ]);
}

$action = $_REQUEST['a'] ?? null;

switch ($action) {
    case 'delete-file':
        $path = isset($_REQUEST['path']) ? (string) $_REQUEST['path'] : '';
        $result = $plugin->deleteRelativePath($path);

        if ($result['success']) {
            $sendJson([
                'status' => 'true',
                'message' => $result['message'],
                'deleted' => 1,
                'skipped' => 0,
                'errors' => [],
                'deleted_paths' => isset($result['deleted_path']) ? [$result['deleted_path']] : [],
            ]);
        }

        $sendJson([
            'status' => 'false',
            'message' => $result['message'],
            'deleted' => 0,
            'skipped' => 1,
            'errors' => [$result['message']],
            'deleted_paths' => [],
        ]);

    case 'delete-files-list':
        $list = $_REQUEST['list'] ?? [];
        if (!is_array($list) || [] === $list) {
            $sendJson([
                'status' => 'false',
                'message' => $plugin->get_lang('NoSelection'),
                'deleted' => 0,
                'skipped' => 0,
                'errors' => [],
                'deleted_paths' => [],
            ]);
        }

        $result = $plugin->deleteRelativePathList($list);
        $sendJson([
            'status' => $result['success'] ? 'true' : 'false',
            'message' => $result['message'],
            'deleted' => $result['deleted'],
            'skipped' => $result['skipped'],
            'errors' => $result['errors'],
            'deleted_paths' => $result['deleted_paths'],
        ]);

    default:
        $sendJson([
            'status' => 'false',
            'message' => get_lang('InvalidAction'),
            'deleted' => 0,
            'skipped' => 0,
            'errors' => [],
            'deleted_paths' => [],
        ]);
}
