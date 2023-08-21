<?php

/* For licensing terms, see /license.txt */

/**
 * Responses to AJAX calls.
 */
$type = isset($_REQUEST['type']) && in_array($_REQUEST['type'], ['personal', 'course', 'admin']) ? $_REQUEST['type'] : 'personal';

if ($type === 'personal') {
    $cidReset = true; // fixes #5162
}

require_once __DIR__.'/../global.inc.php';

$action = $_REQUEST['a'] ?? null;
$group_id = api_get_group_id();

if ($type === 'course') {
    api_protect_course_script(true);
}

$logInfo = [
    'tool' => TOOL_CALENDAR_EVENT,
    'action' => $action,
];
Event::registerLog($logInfo);

$agenda = new Agenda($type);
// get filtered type
$type = $agenda->getType();

$em = Database::getManager();

switch ($action) {
    case 'add_event':
        if (!$agenda->getIsAllowedToEdit()) {
            break;
        }
        if (false === Security::check_token('get')) {
            exit;
        }
        $add_as_announcement = $_REQUEST['add_as_annonuncement'] ?? null;
        $title = $_REQUEST['title'] ?? null;
        $content = $_REQUEST['content'] ?? null;
        $comment = $_REQUEST['comment'] ?? null;
        $userToSend = $_REQUEST['users_to_send'] ?? [];
        $inviteesList = $_REQUEST['invitees'] ?? [];
        $isCollective = isset($_REQUEST['collective']);
        $notificationCount = $_REQUEST['notification_count'] ?? [];
        $notificationPeriod = $_REQUEST['notification_period'] ?? [];
        $careerId = $_REQUEST['career_id'] ?? 0;
        $promotionId = $_REQUEST['promotion_id'] ?? 0;
        $subscriptionVisibility = (int) ($_REQUEST['subscription_visibility'] ?? 0);
        $subscriptionItemId = isset($_REQUEST['subscription_item']) ? (int) $_REQUEST['subscription_item'] : null;
        $maxSubscriptions = (int) ($_REQUEST['max_subscriptions'] ?? 0);

        $reminders = $notificationCount ? array_map(null, $notificationCount, $notificationPeriod) : [];

        $eventId = $agenda->addEvent(
            $_REQUEST['start'],
            $_REQUEST['end'],
            $_REQUEST['all_day'],
            $title,
            $content,
            $userToSend,
            $add_as_announcement,
            null,
            [],
            null,
            $comment,
            '',
            $inviteesList,
            $isCollective,
            $reminders,
            (int) $careerId,
            (int) $promotionId,
            $subscriptionVisibility,
            $subscriptionItemId,
            $maxSubscriptions
        );

        echo $eventId;
        break;
    case 'edit_event':
        if (!$agenda->getIsAllowedToEdit()) {
            break;
        }
        if (false === Security::check_token('get')) {
            exit;
        }
        $id_list = explode('_', $_REQUEST['id']);
        $id = $id_list[1];
        $agenda->editEvent(
            $id,
            $_REQUEST['start'],
            $_REQUEST['end'],
            $_REQUEST['all_day'],
            $title,
            $content
        );
        break;
    case 'delete_event':
        if (!$agenda->getIsAllowedToEdit()) {
            break;
        }
        if (false === Security::check_token('get')) {
            exit;
        }
        $id_list = explode('_', $_REQUEST['id']);
        $id = $id_list[1];
        $deleteAllEventsFromSerie = isset($_REQUEST['delete_all_events']);
        $agenda->deleteEvent($id, $deleteAllEventsFromSerie);
        break;
    case 'resize_event':
        if (!$agenda->getIsAllowedToEdit()) {
            break;
        }
        if (false === Security::check_token('get')) {
            exit;
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
        if (false === Security::check_token('get')) {
            exit;
        }
        $minute_delta = $_REQUEST['minute_delta'];
        $allDay = $_REQUEST['all_day'];
        $id = explode('_', $_REQUEST['id']);
        $id = $id[1];
        $agenda->move_event($id, $minute_delta, $allDay);
        break;
    case 'get_events':
        $filter = $_REQUEST['user_id'] ?? null;
        $sessionId = $_REQUEST['session_id'] ?? null;
        $result = $agenda->parseAgendaFilter($filter);

        $groupId = current($result['groups']);
        $userId = current($result['users']);

        $start = isset($_REQUEST['start']) ? api_strtotime($_REQUEST['start']) : null;
        $end = isset($_REQUEST['end']) ? api_strtotime($_REQUEST['end']) : null;

        if ($type === 'personal' && !empty($sessionId)) {
            $agenda->setSessionId($sessionId);
        }

        $events = $agenda->getEvents(
            $start,
            $end,
            api_get_course_int_id(),
            $groupId,
            $userId
        );
        header('Content-Type: application/json');
        echo $events;
        break;
    case 'get_user_agenda':
        // Used in the admin user list.
        api_protect_admin_script();

        if (api_is_allowed_to_edit(null, true)) {
            //@todo move this in the agenda class
            $DaysShort = api_get_week_days_short();
            $MonthsLong = api_get_months_long();

            $user_id = (int) $_REQUEST['user_id'];
            $my_course_list = CourseManager::get_courses_list_by_user_id($user_id, true);
            if (!is_array($my_course_list)) {
                // this is for the special case if the user has no courses (otherwise you get an error)
                $my_course_list = [];
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
                [],
                $monthName,
                false
            );
        }
        break;
    case 'event_subscribe':
        if (!$agenda->getIsAllowedToEdit()) {
            break;
        }

        if (false === Security::check_token('get')) {
            exit;
        }

        $id = (int) explode('_', $_REQUEST['id'])[1];

        $agenda->subscribeCurrentUserToEvent($id);
        break;
    case 'event_unsubscribe':
        if (!$agenda->getIsAllowedToEdit()) {
            break;
        }

        if (false === Security::check_token('get')) {
            exit;
        }

        $id = (int) explode('_', $_REQUEST['id'])[1];

        $agenda->unsubscribeCurrentUserToEvent($id);
        break;
    default:
        echo '';
}
exit;
