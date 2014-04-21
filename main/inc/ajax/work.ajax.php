<?php
/* For licensing terms, see /license.txt */
/**
 * Responses to AJAX calls
 */

require_once '../global.inc.php';

require_once api_get_path(SYS_CODE_PATH).'work/work.lib.php';

$action = isset($_REQUEST['a']) ? $_REQUEST['a'] : null;

$is_allowed_to_edit = api_is_allowed_to_edit();

switch ($action) {
    case 'delete_work':
        if ($is_allowed_to_edit) {
            $workList = explode(',', $_REQUEST['id']);
            foreach ($workList as $workId) {
                deleteDirWork($workId);
            }
        }
        break;
    default:
        echo '';
        break;
}
exit;