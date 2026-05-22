<?php

/* For license terms, see /license.txt */

require_once __DIR__.'/../../main/inc/global.inc.php';

$plugin = LearningCalendarPlugin::create();

if (!$plugin->isEnabled()) {
    api_not_allowed(true);
}

$action = isset($_REQUEST['a']) ? Security::remove_XSS($_REQUEST['a']) : '';
$calendarId = isset($_REQUEST['id']) ? (int) $_REQUEST['id'] : 0;

$item = $plugin->getCalendar($calendarId);
$plugin->protectCalendar($item);

switch ($action) {
    case 'toggle_day':
        $startDate = isset($_REQUEST['start_date']) ? Security::remove_XSS($_REQUEST['start_date']) : '';
        if (empty($startDate)) {
            exit;
        }

        $endDate = isset($_REQUEST['end_date']) ? Security::remove_XSS($_REQUEST['end_date']) : '';
        if ($startDate === $endDate || empty($endDate)) {
            $plugin->toogleDayType($calendarId, $startDate);
        } else {
            $startDateTime = new DateTime($startDate);
            $endDateTime = new DateTime($endDate);
            $diff = $startDateTime->diff($endDateTime);
            $countDays = (int) $diff->format('%a');
            $dayList = [$startDate];
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
        header('Content-Type: application/json');
        echo json_encode($plugin->getEvents($calendarId));
        break;
}
