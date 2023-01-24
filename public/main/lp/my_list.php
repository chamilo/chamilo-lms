<?php

/* For licensing terms, see /license.txt */

use Chamilo\CourseBundle\Entity\CLp;

$cidReset = true;

require_once __DIR__.'/../inc/global.inc.php';

if (api_get_configuration_value('disable_my_lps_page')) {
    api_not_allowed(true);
}

api_block_anonymous_users();

$userId = api_get_user_id();
$courseId = api_get_course_int_id();
$courses = CourseManager::get_courses_list_by_user_id($userId, true);

$lps = [];
if (!empty($courses)) {
    $courseIdList = array_column($courses, 'real_id');
    $courseWithSession = [];
    foreach ($courses as $course) {
        if (isset($course['session_id'])) {
            if (SESSION_VISIBLE === api_get_session_visibility($course['session_id'])) {
                $courseWithSession[$course['real_id']] = $course['session_id'];
            }
        }
    }

    $courseCondition = " AND lp.cId IN ('".implode("', '", $courseIdList)."') ";
    $order = ' ORDER BY lp.createdOn ASC, lp.name ASC';
    $now = api_get_utc_datetime();
    $conditions = " (
                (lp.publishedOn IS NOT NULL AND lp.publishedOn < '$now' AND lp.expiredOn IS NOT NULL AND lp.expiredOn > '$now') OR
                (lp.publishedOn IS NOT NULL AND lp.publishedOn < '$now' AND lp.expiredOn IS NULL) OR
                (lp.publishedOn IS NULL AND lp.expiredOn IS NOT NULL AND lp.expiredOn > '$now') OR
                (lp.publishedOn IS NULL AND lp.expiredOn IS NULL )
                )
            ";

    $dql = "SELECT lp FROM ChamiloCourseBundle:CLp as lp
            WHERE
                $conditions
                $courseCondition
            ORDER BY lp.createdOn ASC, lp.name ASC
            ";

    $learningPaths = Database::getManager()->createQuery($dql)->getResult();
    $course = api_get_course_entity($courseId);

    /** @var CLp $lp */
    foreach ($learningPaths as $lp) {
        $id = $lp->getIid();
        $lpVisibility = learnpath::is_lp_visible_for_student($id, $userId, $course);
        if (false === $lpVisibility) {
            continue;
        }
        $sessionId = 0;
        if (isset($courseWithSession[$courseId])) {
            $sessionId = $courseWithSession[$courseId];
        }
        //$lpSessionId = $lp->getSessionId();
        if (!empty($lpSessionId)) {
            $sessionId = $lpSessionId;
        }

        $params = '&cid='.$course->getId().'&sid='.$sessionId;
        $link = api_get_path(WEB_CODE_PATH).'lp/lp_controller.php?action=view'.$params.'&lp_id='.$id;
        $icon = Display::url(
            Display::return_icon(
                'learnpath.png',
                get_lang('Lp')
            ),
            $link
        );

        $name = trim(strip_tags(Security::remove_XSS($lp->getName())));
        $lps[] = [
            'name' => $name,
            'link' => $link,
            'icon' => $icon,
            //'creation_date' => api_get_local_time($lp->getCreatedOn()),
        ];
    }
}

$template = new Template(get_lang('MyLps'));
$template->assign('lps', $lps);
$content = $template->fetch($template->get_template('learnpath/my_list.tpl'));
$template->assign('content', $content);
$template->display_one_col_template();
