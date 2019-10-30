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
        $foo_print = grapher($sql_result, $start_date, $end_date, $type);
        echo $foo_print;

        break;
    case 'access_detail_by_date':
        $db = ['is_empty' => true];
        $start_date = isset($_REQUEST['startDate']) ? $_REQUEST['startDate'] : '';
        $end_date = isset($_REQUEST['endDate']) ? $_REQUEST['endDate'] : '';
        $user_id = isset($_REQUEST['student']) ? $_REQUEST['student'] : '';
        $course_code = isset($_REQUEST['course']) ? $_REQUEST['course'] : '';
        $type = isset($_REQUEST['type']) ? $_REQUEST['type'] : '';
        $sessionId = isset($_REQUEST['session_id']) ? $_REQUEST['session_id'] : 0;
        $courseInfo = api_get_course_info($course_code);

        $sql_result = MySpace::get_connections_to_course_by_date(
            $user_id,
            $courseInfo,
            $sessionId,
            $start_date,
            $end_date
        );

        if (is_array($sql_result) && count($sql_result) > 0) {
            $db['is_empty'] = false;
            $db['result'] = convert_to_string($sql_result);
            $rst = get_stats(
                $user_id,
                $courseInfo,
                $sessionId,
                $start_date,
                $end_date
            );
            $foo_stats = '<strong>'.get_lang('Total').': </strong>'.$rst['total'].'<br />';
            $foo_stats .= '<strong>'.get_lang('Average').': </strong>'.$rst['avg'].'<br />';
            $foo_stats .= '<strong>'.get_lang('Quantity').' : </strong>'.$rst['times'].'<br />';
            $db['stats'] = $foo_stats;
            $db['graph_result'] = grapher($sql_result, $start_date, $end_date, $type);
        } else {
            $db['result'] = Display::return_message(
                get_lang('No data available'),
                'warning'
            );
            $db['graph_result'] = Display::return_message(
                get_lang('No data available'),
                'warning'
            );
            $db['stats'] = Display::return_message(
                get_lang('No data available'),
                'warning'
            );
        }
        header('Cache-Control: no-cache');
        echo json_encode($db);
        break;
}
exit;
