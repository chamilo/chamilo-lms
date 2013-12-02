<?php
/* For licensing terms, see /license.txt */
/**
 * Responses to AJAX calls
 */
$type = isset($_GET['type']) && in_array($_GET['type'], array('personal', 'course', 'admin')) ? $_GET['type'] : 'personal';

if ($type == 'personal') {
    $cidReset = true; // fixes #5162
}
require_once api_get_path(SYS_CODE_PATH).'calendar/agenda.inc.php';

$action = isset($_GET['a']) ? $_GET['a'] : null;

if ($type == 'course') {
    api_protect_course_script(true);
}

$group_id = api_get_group_id();
$user_id = api_get_user_id();
$is_group_tutor = GroupManager::is_tutor_of_group($user_id, $group_id);

$agenda = new Agenda();
$agenda->setType($type); //course,admin or personal

switch ($action) {
    case 'add_event':
        if ((!api_is_allowed_to_edit(null, true) && !$is_group_tutor) && $type == 'course') {
            break;
        }
        $add_as_announcement = isset($_REQUEST['add_as_annonuncement']) ? $_REQUEST['add_as_annonuncement'] : null;
        $usersToSend = isset($_REQUEST['users_to_send']) ? $_REQUEST['users_to_send'] : null;
        echo $agenda->add_event($_REQUEST['start'], $_REQUEST['end'], $_REQUEST['all_day'], $_REQUEST['view'], $_REQUEST['title'], $_REQUEST['content'], $usersToSend, $add_as_announcement);
        break;
    case 'edit_event':
        if (!api_is_allowed_to_edit(null, true) && $type == 'course') {
            break;
        }
        $id_list = explode('_', $_REQUEST['id']);
        $id = $id_list[1];
        $agenda->edit_event($id, $_REQUEST['start'], $_REQUEST['end'], $_REQUEST['all_day'], $_REQUEST['view'], $_REQUEST['title'], $_REQUEST['content']);
        break;
    case 'delete_event':
        if (!api_is_allowed_to_edit(null, true) && $type == 'course') {
            break;
        }
        $id_list = explode('_', $_REQUEST['id']);
        $id = $id_list[1];
        $agenda->delete_event($id);
        break;
    case 'resize_event':
        if (!api_is_allowed_to_edit(null, true) && $type == 'course') {
            break;
        }
        $day_delta = $_REQUEST['day_delta'];
        $minute_delta = $_REQUEST['minute_delta'];
        $id = explode('_', $_REQUEST['id']);
        $id = $id[1];
        $agenda->resize_event($id, $day_delta, $minute_delta);
        break;
    case 'move_event':
        if (!api_is_allowed_to_edit(null, true) && $type == 'course') {
            break;
        }
        $day_delta = $_REQUEST['day_delta'];
        $minute_delta = $_REQUEST['minute_delta'];
        $id = explode('_', $_REQUEST['id']);
        $id = $id[1];
        $agenda->move_event($id, $day_delta, $minute_delta);
        break;
    case 'get_events':
        $user_id = isset($_REQUEST['user_id']) ? $_REQUEST['user_id'] : null;
        if (substr($user_id, 0, 1) == 'G') {
            $length = strlen($user_id);
            $group_id = substr($user_id, 2, $length-1);
        }
        $events = $agenda->get_events(
            $_REQUEST['start'],
            $_REQUEST['end'],
            api_get_course_int_id(),
            $group_id ,
            $user_id
        );
        echo $events;
        break;
    case 'get_user_agenda':
        //Used in the admin user list
        api_protect_admin_script();

        if (api_is_allowed_to_edit(null, true)) {
            //@todo move this in the agenda class
            $DaysShort = api_get_week_days_short();
            $MonthsLong = api_get_months_long();

            $user_id = intval($_REQUEST['user_id']);
            $my_course_list = CourseManager::get_courses_list_by_user_id($user_id, true);
            if (!is_array($my_course_list)) {
                // this is for the special case if the user has no courses (otherwise you get an error)
                $my_course_list = array();
            }
            $today = getdate();
            $year = (!empty($_GET['year']) ? (int) $_GET['year'] : NULL);
            if ($year == NULL) {
                $year = $today['year'];
            }
            $month = (!empty($_GET['month']) ? (int) $_GET['month'] : NULL);
            if ($month == NULL) {
                $month = $today['mon'];
            }
            $day = (!empty($_GET['day']) ? (int) $_GET['day'] : NULL);
            if ($day == NULL) {
                $day = $today['mday'];
            }
            $monthName = $MonthsLong[$month - 1];

            $agendaitems = get_myagendaitems($user_id, $my_course_list, $month, $year);
            $agendaitems = get_global_agenda_items($agendaitems, $day, $month, $year, $week, "month_view");

            if (api_get_setting('allow_personal_agenda') == 'true') {
                $agendaitems = get_personal_agenda_items($user_id, $agendaitems, $day, $month, $year, $week, "month_view");
            }
            display_mymonthcalendar($user_id, $agendaitems, $month, $year, array(), $monthName, false);
        }
        break;
    default:
        echo '';
}
exit;
