<?php

/* For licensing terms, see /license.txt */

require_once __DIR__.'/../inc/global.inc.php';
require_once api_get_path(SYS_CODE_PATH).'work/work.lib.php';

api_protect_course_script();

$allowToTrack = api_is_platform_admin() || api_is_allowed_to_edit();

if (!$allowToTrack) {
    api_not_allowed(true);
}
$consideredWorkingTime = api_get_configuration_value('considered_working_time');

if (false === $consideredWorkingTime) {
    api_not_allowed(true);
}

$courseCode = api_get_course_id();
$sessionId = api_get_session_id();

$nameTools = get_lang('Students');

$this_section = SECTION_TRACKING;
$webCodePath = api_get_path(WEB_CODE_PATH);
$interbreadcrumb[] = [
    'url' => api_is_student_boss() ? '#' : 'index.php',
    'name' => get_lang('MySpace'),
];

function get_count_users()
{
    $courseCode = api_get_course_id();
    $sessionId = api_get_session_id();

    return CourseManager::get_user_list_from_course_code(
        $courseCode,
        $sessionId,
        null,
        null,
        null,
        true
    );
}

function get_users($from, $number_of_items, $column, $direction)
{
    $consideredWorkingTime = api_get_configuration_value('considered_working_time');

    $courseId = api_get_course_int_id();
    $courseCode = api_get_course_id();
    $sessionId = api_get_session_id();
    $webCodePath = api_get_path(WEB_CODE_PATH);

    $lastConnectionDate = null;
    $is_western_name_order = api_is_western_name_order();
    $limit = null;
    $from = (int) $from;
    $number_of_items = (int) $number_of_items;
    $limit = 'LIMIT '.$from.','.$number_of_items;

    $students = CourseManager::get_user_list_from_course_code(
        $courseCode,
        $sessionId,
        $limit,
        null,
        null,
        false
    );
    $url = $webCodePath.'mySpace/myStudents.php';

    $workList = getWorkListTeacher(0, 100, null, null, null);

    $workTimeList = [];
    foreach ($workList as $work) {
        $fieldValue = new ExtraFieldValue('work');
        $resultExtra = $fieldValue->getAllValuesForAnItem(
            $work['id'],
            true
        );

        foreach ($resultExtra as $field) {
            $field = $field['value'];
            if ($consideredWorkingTime == $field->getField()->getVariable()) {
                $time = $field->getValue();
                $parsed = date_parse($time);
                $workTimeList[$work['id']] = $parsed['hour'] * 3600 + $parsed['minute'] * 60 + $parsed['second'];

                break;
            }
        }
    }

    $all_datas = [];
    foreach ($students as $studentData) {
        $studentId = $studentData['user_id'];
        $studentData = api_get_user_info($studentId);
        $urlDetails = $url."?student=$studentId&details=true&course=$courseCode&id_session=$sessionId";
        $row = [];
        if ($is_western_name_order) {
            $first = Display::url($studentData['firstname'], $urlDetails);
            $last = Display::url($studentData['lastname'], $urlDetails);
        } else {
            $first = Display::url($studentData['lastname'], $urlDetails);
            $last = Display::url($studentData['firstname'], $urlDetails);
        }

        $row[] = $first;
        $row[] = $last;

        $timeInSeconds = Tracking::get_time_spent_on_the_course(
            $studentId,
            $courseId,
            $sessionId
        );

        $row[] = api_time_to_hms($timeInSeconds);

        $userWorkTime = 0;
        foreach ($workList as $work) {
            $userWorks = get_work_user_list(
                0,
                100,
                null,
                null,
                $work['id'],
                null,
                $studentId,
                false,
                $courseId,
                $sessionId
            );

            if ($userWorks) {
                foreach ($userWorks as $work) {
                    $userWorkTime += $workTimeList[$work['parent_id']];
                }
            }
        }

        $row[] = api_time_to_hms($userWorkTime);
        $status = '';
        if ($userWorkTime && $timeInSeconds) {
            if ($userWorkTime > $timeInSeconds) {
                $status = Display::label('TimeToFix', 'warning');
            } else {
                $status = Display::label('Ok', 'success');
            }
        }

        $row[] = $status;
        /*$detailsLink = Display::url(
            Display::return_icon('2rightarrow.png', get_lang('Details').' '.$studentData['username']),
            $urlDetails,
            ['id' => 'details_'.$studentData['username']]
        );
        $row[] = $detailsLink;*/
        $all_datas[] = $row;
    }

    return $all_datas;
}

$is_western_name_order = api_is_western_name_order();
$sort_by_first_name = api_sort_by_first_name();
$actionsLeft = '';
$toolbar = Display::toolbarAction('toolbar-student', [$actionsLeft]);

$itemPerPage = 10;
$perPage = api_get_configuration_value('my_space_users_items_per_page');
if ($perPage) {
    $itemPerPage = (int) $perPage;
}

$table = new SortableTable(
    'tracking_work_student',
    'get_count_users',
    'get_users',
    ($is_western_name_order xor $sort_by_first_name) ? 1 : 0,
    $itemPerPage
);

$parameters = ['cidReq' => $courseCode, 'id_session' => $sessionId];
$table->set_additional_parameters($parameters);

if ($is_western_name_order) {
    $table->set_header(0, get_lang('FirstName'), false);
    $table->set_header(1, get_lang('LastName'), false);
} else {
    $table->set_header(0, get_lang('LastName'), false);
    $table->set_header(1, get_lang('FirstName'), false);
}

$table->set_header(2, get_lang('TimeSpentInTheCourse'), false);
$table->set_header(3, get_lang('TimeSpentOnAssignment'), false);
$table->set_header(4, get_lang('Status'), false);

Display::display_header($nameTools);
echo $toolbar;
echo Display::page_subheader($nameTools);
$table->display();

Display::display_footer();
