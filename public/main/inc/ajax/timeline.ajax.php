<?php
/* For licensing terms, see /license.txt */

require_once __DIR__.'/../global.inc.php';

$timeline = new Timeline();

$action = $_GET['a'];

switch ($action) {
    case 'get_timeline_content':
        $items = $timeline->get_timeline_content($_GET['id']);
        echo json_encode($items);
        break;
}
