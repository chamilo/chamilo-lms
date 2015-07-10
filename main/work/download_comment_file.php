<?php
/* For licensing terms, see /license.txt */

/**
 *	This file is responsible for  passing requested documents to the browser.
 *	Html files are parsed to fix a few problems with URLs,
 *	but this code will hopefully be replaced soon by an Apache URL
 *	rewrite mechanism.
 *
 *	@package chamilo.work
 */
require_once '../inc/global.inc.php';
require_once 'work.lib.php';

// Course protection
api_protect_course_script(true);

$commentId = isset($_GET['comment_id']) ? intval($_GET['comment_id']) : null;
if (empty($commentId)) {
    api_not_allowed(true);
}
$workData = getWorkComment($commentId);
$courseInfo = api_get_course_info();

if (!empty($workData)) {
    if (
        empty($workData['file_path']) ||
        (isset($workData['file_path']) && !file_exists($workData['file_path']))
    ) {
        api_not_allowed(true);
    }

    $work = get_work_data_by_id($workData['work_id']);

    protectWork($courseInfo, $work['parent_id']);

    if (user_is_author($workData['work_id']) ||
        $courseInfo['show_score'] == 0 &&
        $work['active'] == 1 &&
        $work['accepted'] == 1
    ) {
        if (Security::check_abs_path(
            $workData['file_path'],
            api_get_path(SYS_COURSE_PATH) . api_get_course_path() . '/'
        )
        ) {
            DocumentManager::file_send_for_download(
                $workData['file_path'],
                true,
                $workData['file_name_to_show']
            );
        }
    } else {
        api_not_allowed(true);
    }

} else {
    api_not_allowed(true);
}
