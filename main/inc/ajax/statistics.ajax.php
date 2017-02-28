<?php

/* For licensing terms, see /license.txt */
/**
 * Responses to AJAX calls
 */

require_once __DIR__.'/../global.inc.php';

api_protect_admin_script();

$action = isset($_REQUEST['a']) ? $_REQUEST['a'] : null;

switch ($action) {
    case 'recentlogins':
        header('Content-type: application/json');
        $list = [];
        $all = Statistics::getRecentLoginStats();
        $distinct = Statistics::getRecentLoginStats(true);

        foreach ($all as $tick => $tock) {
            $list['labels'][] = $tick;
        }

        $list['datasets'][0]['label'] = get_lang('Logins');
        $list['datasets'][0]['fillColor'] = "rgba(151,187,205,0.2)";
        $list['datasets'][0]['strokeColor'] = "rgba(151,187,205,1)";
        $list['datasets'][0]['pointColor'] = "rgba(151,187,205,1)";
        $list['datasets'][0]['pointStrokeColor'] = "#fff";
        $list['datasets'][0]['pointHighlightFill'] = "#fff";
        $list['datasets'][0]['pointHighlightStroke'] = "rgba(151,187,205,1)";

        foreach ($all as $tick => $tock) {
            $list['datasets'][0]['data'][] = $tock;
        }

        $list['datasets'][1]['label'] = get_lang('DistinctUsersLogins');
        $list['datasets'][1]['fillColor'] = "rgba(0,204,0,0.2)";
        $list['datasets'][1]['strokeColor'] = "rgba(0,204,0,1)";
        $list['datasets'][1]['pointColor'] = "rgba(0,204,0,1)";
        $list['datasets'][1]['pointStrokeColor'] = "#fff";
        $list['datasets'][1]['pointHighlightFill'] = "#fff";
        $list['datasets'][1]['pointHighlightStroke'] = "rgba(0,204,0,1)";

        foreach ($distinct as $tick => $tock) {
            $list['datasets'][1]['data'][] = $tock;
        }

        echo json_encode($list);
        break;
}


