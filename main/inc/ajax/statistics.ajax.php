<?php

/* For licensing terms, see /license.txt */
/**
 * Responses to AJAX calls
 */

require_once '../global.inc.php';

api_protect_admin_script();

$action = isset($_REQUEST['a']) ? $_REQUEST['a'] : null;

switch ($action) {
    case 'recentlogins':
        
        $list = [];
        
        $all = Statistics::getRecentLoginStats();
        $distinct = Statistics::getRecentLoginStats(true);
        
        foreach ($all as $tick => $tock) {
            $list['labels'][] = $tick;
        }
        
        $list['dataset'][0]['label'] = get_lang('Logins');
        $list['dataset'][0]['fillColor'] = "rgba(220,220,220,0.2)";
        $list['dataset'][0]['strokeColor'] = "rgba(220,220,220,1)";
        $list['dataset'][0]['pointColor'] = "rgba(220,220,220,1)";
        $list['dataset'][0]['pointStrokeColor'] = "#fff";
        $list['dataset'][0]['pointHighlightFill'] = "#fff";
        $list['dataset'][0]['pointHighlightStroke'] = "rgba(220,220,220,1)";
        
        foreach ($all as $tick => $tock) {
            $list['dataset'][0]['data'][] = $tock;
        }
        
        $list['dataset'][1]['label'] = get_lang('Logins2');
        $list['dataset'][1]['fillColor'] = "rgba(220,220,220,0.2)";
        $list['dataset'][1]['strokeColor'] = "rgba(220,220,220,1)";
        $list['dataset'][1]['pointColor'] = "rgba(220,220,220,1)";
        $list['dataset'][1]['pointStrokeColor'] = "#fff";
        $list['dataset'][1]['pointHighlightFill'] = "#fff";
        $list['dataset'][1]['pointHighlightStroke'] = "rgba(220,220,220,1)";
        
        foreach ($distinct as $tick => $tock) {
            $list['dataset'][1]['data'][] = $tock;
        }
        
        echo json_encode($list);
        break;
}


