<?php

/* For licensing terms, see /license.txt */

/**
 * Responses to AJAX calls.
 */
require_once __DIR__.'/../global.inc.php';
$action = $_GET['a'];

// Access restrictions.
$is_allowedToTrack = api_is_platform_admin(true, true) ||
    api_is_allowed_to_create_course() || api_is_course_tutor();

if (!$is_allowedToTrack) {
    exit;
}

switch ($action) {
    // At this date : 23/02/2017, a minor review can't determine where is used this case 'access_detail'
    case 'access_detail':
        $user_id = intval($_REQUEST['student']);
        $course_code = Security::remove_XSS($_REQUEST['course']);
        $type = Security::remove_XSS($_REQUEST['type']);
        $range = Security::remove_XSS($_REQUEST['range']);
        $sessionId = isset($_REQUEST['session_id']) ? $_REQUEST['session_id'] : 0;
        $courseInfo = api_get_course_info($course_code);

        if ($range == 1) {
            $start_date = Security::remove_XSS($_REQUEST['sd']);
            $end_date = Security::remove_XSS($_REQUEST['ed']);
            $sql_result = MySpace::get_connections_to_course_by_date(
                $user_id,
                $courseInfo,
                $sessionId,
                $start_date,
                $end_date
            );
        } else {
            $sql_result = MySpace::get_connections_to_course(
                $user_id,
                $courseInfo,
                $sessionId
            );
        }
        $foo_print = MySpace::grapher($sql_result, $start_date, $end_date, $type);
        echo $foo_print;

        break;
    case 'access_detail_by_date':
        $export = isset($_REQUEST['export']) ? $_REQUEST['export'] : false;

        $result = ['is_empty' => true];
        $start_date = isset($_REQUEST['startDate']) ? $_REQUEST['startDate'] : '';
        $end_date = isset($_REQUEST['endDate']) ? $_REQUEST['endDate'] : '';
        $user_id = isset($_REQUEST['student']) ? $_REQUEST['student'] : '';
        $course_code = isset($_REQUEST['course']) ? $_REQUEST['course'] : '';
        $type = isset($_REQUEST['type']) ? $_REQUEST['type'] : '';
        $sessionId = isset($_REQUEST['session_id']) ? $_REQUEST['session_id'] : 0;
        $courseInfo = api_get_course_info($course_code);

        $connections = MySpace::get_connections_to_course_by_date(
            $user_id,
            $courseInfo,
            $sessionId,
            $start_date,
            $end_date,
            true
        );

        if (is_array($connections) && count($connections) > 0) {
            $result['is_empty'] = false;
            $tableData = [];
            foreach ($connections as $data) {
                $item = [
                    api_get_local_time($data['login']),
                    api_time_to_hms(api_strtotime($data['logout']) - api_strtotime($data['login'])),
                    $data['user_ip'],
                ];
                $tableData[] = $item;
            }

            $table = new SortableTableFromArray(
                $tableData,
                0,
                500,
                'stat_table',
                null,
                'stat_table'
            );
            $table->set_header(1, get_lang('LoginDate'), false);
            $table->set_header(2, get_lang('Duration'), false);
            $table->set_header(3, get_lang('IP'), false);
            $result['result'] = $table->return_table();

            if ($export) {
                Export::arrayToXls($table->toArray());
                exit;
            }

            $rst = MySpace::getStats(
                $user_id,
                $courseInfo,
                $sessionId,
                $start_date,
                $end_date
            );
            $stats = '<strong>'.get_lang('Total').': </strong>'.$rst['total'].'<br />';
            $stats .= '<strong>'.get_lang('Average').': </strong>'.$rst['avg'].'<br />';
            $stats .= '<strong>'.get_lang('Quantity').' : </strong>'.$rst['times'].'<br />';
            $result['stats'] = $stats;
            $result['graph_result'] = MySpace::grapher($connections, $start_date, $end_date, $type);
        } else {
            $result['result'] = Display::return_message(
                get_lang('NoDataAvailable'),
                'warning'
            );
            $result['graph_result'] = Display::return_message(
                get_lang('NoDataAvailable'),
                'warning'
            );
            $result['stats'] = Display::return_message(
                get_lang('NoDataAvailable'),
                'warning'
            );
        }
        header('Cache-Control: no-cache');
        echo json_encode($result);
        break;
}
exit;
