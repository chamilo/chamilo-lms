<?php

require_once __DIR__.'/../../main/inc/global.inc.php';

$allow = api_is_allowed_to_edit();

if (!$allow) {
    api_not_allowed(true);
}

$action = isset($_REQUEST['a']) ? $_REQUEST['a'] : '';
$calendarId = isset($_REQUEST['id']) ? $_REQUEST['id'] : 0;

$eventTypeList = LpCalendarPlugin::getEventTypeList();

switch ($action) {
    case 'toggle_day':
        $startDate = isset($_REQUEST['date']) ? $_REQUEST['date'] : '';
        //$endDate = isset($_REQUEST['date']) ? $_REQUEST['date'] : 0;

        $sql = "SELECT * FROM learning_calendar_events 
                WHERE start_date = '$startDate' AND calendar_id = $calendarId ";
        $result = Database::query($sql);

        if (Database::num_rows($result)) {
            $row = Database::fetch_array($result, 'ASSOC');
            $currentType = $row['type'];
            $currentType++;
            if ($currentType > count($eventTypeList)) {
                $currentType = 1;
                Database::delete(
                    'learning_calendar_events',
                    [' calendar_id = ? AND start_date = ?' => [$calendarId, $startDate]]
                );
            } else {
                $params = [
                    'type' => $currentType,
                ];
                Database::update(
                    'learning_calendar_events',
                    $params,
                    [' calendar_id = ? AND start_date = ?' => [$calendarId, $startDate]]
                );
            }
        } else {
            $params = [
                'name' => '',
                'calendar_id' => $calendarId,
                'start_date' => $startDate,
                'end_date' => $startDate,
                'type' => LpCalendarPlugin::EVENT_TYPE_TAKEN
            ];
            Database::insert('learning_calendar_events', $params);
        }
        break;
    case 'get_events':
        $sql = "SELECT * FROM learning_calendar_events";
        $result = Database::query($sql);
        $list = [];
        while ($row = Database::fetch_array($result, 'ASSOC')) {
            $list[] = [
                'start_date' => $row['start_date'],
                'end_date' => $row['start_date'],
                'color' => $eventTypeList[$row['type']]
            ];
        }
        echo json_encode($list);
        break;
}