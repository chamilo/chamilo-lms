<?php

/* For licensing terms, see /license.txt */

$cidReset = true;
require_once __DIR__.'/../inc/global.inc.php';

api_block_anonymous_users();

$itemView = Database::get_course_table(TABLE_LP_ITEM_VIEW);
$view = Database::get_course_table(TABLE_LP_VIEW);
$userId = api_get_user_id();

$sql = "SELECT v.lp_id, v.c_id, v.session_id
        FROM $view v
        INNER JOIN $itemView iv
        ON (v.c_id = iv.c_id AND v.iid = iv.lp_view_id)
        WHERE
            user_id = $userId
        ORDER BY start_time DESC
        LIMIT 1 ";
$result = Database::query($sql);

if (Database::num_rows($result)) {
    $result = Database::fetch_array($result, 'ASSOC');
    $lpId = (int) $result['lp_id'];
    $courseId = (int) $result['c_id'];
    $sessionId = (int) $result['session_id'];
    $courseInfo = api_get_course_info_by_id($courseId);
    if (!empty($courseInfo)) {
        $url = api_get_path(WEB_CODE_PATH).
            'lp/lp_controller.php?action=view&lp_id='.$lpId.'&cidReq='.$courseInfo['code'].'&id_session='.$sessionId;
        api_location($url);
    }
}

Display::addFlash(Display::return_message(get_lang('YouDidNotVisitALpHereTheLpList')));
api_location(api_get_path(WEB_CODE_PATH).'lp/my_list.php');
