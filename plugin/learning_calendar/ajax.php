<?php
/* For license terms, see /license.txt */

require_once __DIR__.'/../../main/inc/global.inc.php';

$action = isset($_REQUEST['a']) ? $_REQUEST['a'] : '';
$calendarId = isset($_REQUEST['id']) ? $_REQUEST['id'] : 0;

$plugin = LearningCalendarPlugin::create();
$item = $plugin->getCalendar($calendarId);
$plugin->protectCalendar($item);

switch ($action) {
    case 'toggle_day':
        $startDate = isset($_REQUEST['start_date']) ? $_REQUEST['start_date'] : '';
        if (empty($startDate)) {
            exit;
        }
        $endDate = isset($_REQUEST['end_date']) ? $_REQUEST['end_date'] : '';
        if ($startDate == $endDate) {
            // One day
            $plugin->toogleDayType($calendarId, $startDate);
        } else {
            // A list of days
            $startDateTime = new DateTime($startDate);
            $endDateTime = new DateTime($endDate);
            $diff = $startDateTime->diff($endDateTime);
            $countDays = $diff->format('%a');
            $dayList[] = $startDate;
            for ($i = 0; $i < $countDays; $i++) {
                $startDateTime->modify('+1 day');
                $dayList[] = $startDateTime->format('Y-m-d');
            }
            foreach ($dayList as $day) {
                $plugin->toogleDayType($calendarId, $day);
            }
        }
        break;
    case 'get_events':
        $list = $plugin->getEvents($calendarId);
        echo json_encode($list);
        break;
}
