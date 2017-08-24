<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\CourseRelUser;
use Chamilo\CoreBundle\Entity\SessionRelCourseRelUser;
use Chamilo\CoreBundle\Entity\Course;

require_once __DIR__.'/../inc/global.inc.php';

api_block_anonymous_users(true);

$isHrm = api_is_drh();

if (!$isHrm) {
    api_not_allowed(true);
}

function formatCourseInfo(Course $course, $sessionId = 0, $showCourseCode = false)
{
    $sysCoursePath = api_get_path(SYS_COURSE_PATH).$course->getDirectory();
    $webCoursePath = api_get_path(WEB_COURSE_PATH).$course->getDirectory();

    return [
        'visibility' => $course->getVisibility(),
        'link' => api_get_course_url($course->getCode(), $sessionId),
        'category' => $course->getCategoryCode(),
        'title' => $course->getTitle(),
        'title_cut' => $course->getTitle(),
        'code_course' => $showCourseCode ? $course->getCode() : null,
        'image' => file_exists($sysCoursePath.'/course-pic.png')
            ? $webCoursePath.'/course-pic.png'
            : Display::return_icon(
                    'session_default.png',
                    null,
                    null,
                    null,
                    null,
                    true
                ),
        'teachers' => api_get_setting('display_teacher_in_courselist') === 'true'
            ? $teachers = CourseManager::getTeachersFromCourse($course->getId(), true)
            : []
    ];
}

$showCourseCode = api_get_configuration_value('display_coursecode_in_courselist') === 'true';

$hrm = api_get_user_entity(api_get_user_id());
$assignedUsers = UserManager::get_users_followed_by_drh($hrm->getId());
$users = [];

$courseController = new IndexManager('');

foreach ($assignedUsers as $assignedUserId => $assignedUserInfo) {
    $assignedUser = api_get_user_entity($assignedUserId);

    if (!$assignedUser) {
        continue;
    }

    $userInfo = [
        'username' => $assignedUser->getUsername(),
        'complete_name' => $assignedUser->getCompleteName(),
        'picture_url' => UserManager::getUserPicture($assignedUserId),
        'course_list' => $courseController->returnCoursesAndSessions($assignedUserId)['html']
    ];

    $users[$assignedUser->getId()] = $userInfo;
}

$toolName = get_lang('HrmAssignedUsersCourseList');

$view = new Template($toolName);
$view->assign('users', $users);

$content = $view->fetch(
    $view->get_template('auth/hrm_courses.tpl')
);

$view->assign('header', $toolName);
$view->assign('content', $content);
$view->display_one_col_template();
