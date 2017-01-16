<?php
/* For licensing terms, see /license.txt */
/**
 * Responses to AJAX calls
 */

$type = isset($_REQUEST['type']) && in_array($_REQUEST['type'], array('personal', 'course', 'admin')) ? $_REQUEST['type'] : 'personal';

if ($type == 'personal') {
    $cidReset = true; // fixes #5162
}

require_once __DIR__.'/../global.inc.php';

$action = isset($_REQUEST['a']) ? $_REQUEST['a'] : null;
$group_id = api_get_group_id();

if ($type == 'course') {
    api_protect_course_script(true);
}

$agenda = new Agenda($type);

switch ($action) {
    case 'add_event':
        if (!$agenda->getIsAllowedToEdit()) {
            break;
        }
        $add_as_announcement = isset($_REQUEST['add_as_annonuncement']) ? $_REQUEST['add_as_annonuncement'] : null;
        $comment = isset($_REQUEST['comment']) ? $_REQUEST['comment'] : null;
        $userToSend = isset($_REQUEST['users_to_send']) ? $_REQUEST['users_to_send'] : array();

        echo $agenda->addEvent(
            $_REQUEST['start'],
            $_REQUEST['end'],
            $_REQUEST['all_day'],
            $_REQUEST['title'],
            $_REQUEST['content'],
            $userToSend,
            $add_as_announcement,
            null, //$parentEventId = null,
            array(), //$attachmentArray = array(),
            null, //$attachmentComment = null,
            $comment
        );
        break;
    case 'edit_event':
        if (!$agenda->getIsAllowedToEdit()) {
            break;
        }
        $id_list = explode('_', $_REQUEST['id']);
        $id = $id_list[1];
        $agenda->editEvent(
            $id,
            $_REQUEST['start'],
            $_REQUEST['end'],
            $_REQUEST['all_day'],
            $_REQUEST['title'],
            $_REQUEST['content']
        );
        break;
    case 'delete_event':
        if (!$agenda->getIsAllowedToEdit()) {
            break;
        }
        $id_list = explode('_', $_REQUEST['id']);
        $id = $id_list[1];
        $deleteAllEventsFromSerie = isset($_REQUEST['delete_all_events']) ? true : false;
        $agenda->deleteEvent($id, $deleteAllEventsFromSerie);
        break;
    case 'resize_event':
        if (!$agenda->getIsAllowedToEdit()) {
            break;
        }
        $minute_delta = $_REQUEST['minute_delta'];
        $id = explode('_', $_REQUEST['id']);
        $id = $id[1];
        $agenda->resizeEvent($id, $minute_delta);
        break;
    case 'move_event':
        if (!$agenda->getIsAllowedToEdit()) {
            break;
        }
        $minute_delta = $_REQUEST['minute_delta'];
        $allDay = $_REQUEST['all_day'];
        $id = explode('_', $_REQUEST['id']);
        $id = $id[1];
        $agenda->move_event($id, $minute_delta, $allDay);
        break;
    case 'get_events':
        $filter = isset($_REQUEST['user_id']) ? $_REQUEST['user_id'] : null;
        $sessionId = isset($_REQUEST['session_id']) ? $_REQUEST['session_id'] : null;
        $result = $agenda->parseAgendaFilter($filter);

        $groupId = current($result['groups']);
        $userId = current($result['users']);

        $start = isset($_REQUEST['start']) ? api_strtotime($_REQUEST['start']) : null;
        $end = isset($_REQUEST['end']) ? api_strtotime($_REQUEST['end']) : null;

        if ($type == 'personal' && !empty($sessionId)) {
            $agenda->setSessionId($sessionId);
        }

        $events = $agenda->getEvents(
            $start,
            $end,
            api_get_course_int_id(),
            $groupId,
            $userId
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
            $year = (!empty($_GET['year']) ? (int) $_GET['year'] : null);
            if ($year == null) {
                $year = $today['year'];
            }
            $month = (!empty($_GET['month']) ? (int) $_GET['month'] : null);
            if ($month == null) {
                $month = $today['mon'];
            }
            $day = (!empty($_GET['day']) ? (int) $_GET['day'] : null);
            if ($day == null) {
                $day = $today['mday'];
            }
            $monthName = $MonthsLong[$month - 1];
            $week = null;

            $agendaitems = Agenda::get_myagendaitems(
                $user_id,
                $my_course_list,
                $month,
                $year
            );
            $agendaitems = Agenda::get_global_agenda_items(
                $agendaitems,
                $day,
                $month,
                $year,
                $week,
                "month_view"
            );

            if (api_get_setting('allow_personal_agenda') == 'true') {
                $agendaitems = Agenda::get_personal_agenda_items(
                    $user_id,
                    $agendaitems,
                    $day,
                    $month,
                    $year,
                    $week,
                    "month_view"
                );
            }
            Agenda::display_mymonthcalendar(
                $user_id,
                $agendaitems,
                $month,
                $year,
                array(),
                $monthName,
                false
            );
        }
        break;
    default:
        echo '';
}
exit;
