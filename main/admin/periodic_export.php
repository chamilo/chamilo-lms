<?php
/* For licensing terms, see /license.txt */
exit;
$cidReset = true;
require_once __DIR__.'/../inc/global.inc.php';

api_protect_admin_script(true);
api_set_more_memory_and_time_limits();

$this_section = SECTION_PLATFORM_ADMIN;
$interbreadcrumb[] = ['url' => 'index.php', 'name' => get_lang('PlatformAdmin')];

$nameTools = get_lang('PeriodicExport');
$export = '';

Display::display_header($nameTools);

echo Display::page_header($nameTools);

$form = new FormValidator('special_exports', 'post');
$form->addDateRangePicker('date', get_lang('Dates'));
$form->addButtonSearch(get_lang('Search'));

$form->display();

if ($form->validate()) {
    $values = $form->getSubmitValues();
    $urlId = api_get_current_access_url_id();
    $startDate = $values['date_start'];
    $endDate = $values['date_end'];

    // Count active users in the platform
    $countUsers = UserManager::get_number_of_users(null, $urlId, 1);
    // Count user connected in those dates
    $connectedUsers = Statistics::getLoginCount($startDate, $endDate);

    $activeCourses = CourseManager::countActiveCourses($urlId);
    $totalCourses = CourseManager::count_courses($urlId);

    $total = Tracking::getTotalTimeSpentOnThePlatform();

    $now = api_get_utc_datetime();

    $beforeDateStart = new DateTime('-90 days', new DateTimeZone('UTC'));
    $end = $beforeDateStart->format('Y-m-d H:i:s');

    $thisTrimester = Tracking::getTotalTimeSpentOnThePlatform($end, $now);

    $beforeDateEnd = new DateTime('-180 days', new DateTimeZone('UTC'));
    $start = $beforeDateEnd->format('Y-m-d H:i:s');

    $lastTrimester = Tracking::getTotalTimeSpentOnThePlatform($start, $end);

    //var_dump($countUsers, $connectedUsers, $activeCourses, $totalCourses, $total, $thisTrimester, $lastTrimester);

    $courses = Statistics::getCoursesWithActivity($startDate, $endDate);

    $totalUsers = 0;
    $totalCertificates = 0;
    foreach ($courses as $courseId) {
        $courseInfo = api_get_course_info_by_id($courseId);

        $countUsers = CourseManager::get_user_list_from_course_code(
            $courseInfo['code'],
            0,
            null,
            null,
            null,
            true
        );

        $totalUsers += $countUsers;

        $categories = Category::load(
            null,
            null,
            $courseInfo['code'],
            null,
            false,
            0
        );

        $category = null;
        $certificateCount = 0;
        if (!empty($categories)) {
            $category = current($categories);
            // @todo use count
            $certificateCount = count(GradebookUtils::get_list_users_certificates($categoryId));
            $totalCertificates += $certificateCount;
        }
    }

    $totalUsersCourses = CourseManager::totalSubscribedUsersInCourses($urlId);
}

Display::display_footer();
