<?php

/* For licensing terms, see /license.txt */

/**
 * Responses to AJAX calls.
 */

use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CoreBundle\Helpers\FileHelper;

require_once '../config.php';

api_protect_admin_script();

$plugin = CleanDeletedFilesPlugin::create();
$action = isset($_REQUEST['a']) ? $_REQUEST['a'] : null;

switch ($action) {
    case 'delete-file':
        $path = isset($_REQUEST['path']) ? $_REQUEST['path'] : null;
        if (empty($path)) {
            echo json_encode(["status" => "false", "message" => $plugin->get_lang('ErrorEmptyPath')]);
            exit;
        }

        if (Container::$container->get(FileHelper::class)->delete($path)) {
            Display::addFlash($plugin->get_lang("DeletedSuccess"), 'success');
            echo json_encode(["status" => "true"]);
        } else {
            echo json_encode(["status" => "false", "message" => $plugin->get_lang('ErrorDeleteFile')]);
        }
        break;
    case 'delete-files-list':
        $list = isset($_REQUEST['list']) ? $_REQUEST['list'] : [];
        if (empty($list)) {
            echo json_encode(["status" => "false", "message" => $plugin->get_lang('ErrorEmptyPath')]);
            exit;
        }

        foreach ($list as $value) {
            if (empty($value)) {
                continue;
            }
            Container::$container->get(FileHelper::class)->delete($value);
        }

        Display::addFlash($plugin->get_lang("DeletedSuccess"), 'success');
        echo json_encode(["status" => "true"]);
        break;
}
