<?php
require_once '../global.inc.php';
require_once api_get_path(LIBRARY_PATH).'timeline.lib.php';

$timeline = new Timeline();

$action = $_GET['a'];

switch ($action) {		
	case 'get_timeline_content':
        $items = $timeline->get_timeline_content($_GET['id']);
        echo json_encode($items);      
        /*echo '<pre>';
        echo json_encode($items);
        echo '</pre>';
        var_dump($items);*/
    break;
}