<?php
/* For licensing terms, see /license.txt */
require_once __DIR__.'/../global.inc.php';

$id = isset($_REQUEST['id']) ? $_REQUEST['id'] : null;
$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : null;
$event_name = isset($_REQUEST['eventName']) ? $_REQUEST['eventName'] : null;

api_protect_admin_script();

switch ($action) {
    case 'getEventTypes':
        $events = Event::get_all_event_types();
        echo json_encode($events);
        break;
    case 'getUsers':
        $users = UserManager::get_user_list();
        echo json_encode($users);
        break;
    case 'get_event_users':
        $users = Event::get_event_users($event_name);
        echo json_encode($users);
        break;
}
exit;
