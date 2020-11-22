<?php

/* For licensing terms, see /license.txt */

require_once __DIR__.'/../../../main/inc/global.inc.php';

api_protect_admin_script();

$plugin = Positioning::create();
$action = isset($_REQUEST['a']) ? $_REQUEST['a'] : null;

switch ($action) {
    case 'delete-file':
        $path = isset($_REQUEST['path']) ? $_REQUEST['path'] : null;
        if (empty($path)) {
            echo json_encode(["status" => "false", "message" => $plugin->get_lang('ErrorEmptyPath')]);
            exit;
        }

        if (unlink($path)) {
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
            unlink($value);
        }

        Display::addFlash($plugin->get_lang("DeletedSuccess"), 'success');
        echo json_encode(["status" => "true"]);
        break;
}
