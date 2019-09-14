<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Component\Utils\ChamiloApi;

/**
 * Responses to AJAX calls.
 */
require_once __DIR__.'/../global.inc.php';

api_protect_admin_script();

$action = isset($_REQUEST['a']) ? $_REQUEST['a'] : null;
$sessionDuration = isset($_GET['session_duration']) ? (int) $_GET['session_duration'] : 0;
$exportFormat = isset($_REQUEST['export_format']) ? $_REQUEST['export_format'] : 'csv';
$operation = isset($_REQUEST['oper']) ? $_REQUEST['oper'] : false;
$order = isset($_REQUEST['sord']) && in_array($_REQUEST['sord'], ['asc', 'desc']) ? $_REQUEST['sord'] : 'asc';

switch ($action) {
    case 'get_user_session':
        $list = [];

        $urlList = UrlManager::get_url_data("url $order");
        $sessionUrl = api_get_path(WEB_CODE_PATH).'session/resume_session.php?id_session=';

        $start = isset($_GET['start']) ? Database::escape_string(api_get_utc_datetime($_GET['start'])) : api_get_utc_datetime();
        $end = isset($_GET['end']) ? Database::escape_string(api_get_utc_datetime($_GET['end'])) : api_get_utc_datetime();

        if (!empty($operation)) {
            $list[] = [
                'URL',
                get_lang('Session'),
                get_lang('Course'),
                get_lang('CountUsers'),
            ];
        }

        $courseListInfo = [];
        foreach ($urlList as $url) {
            $urlId = $url['id'];
            $sessionList = SessionManager::get_sessions_list([], [], null, null, $urlId);
            foreach ($sessionList as $session) {
                $sessionId = $session['id'];
                $row = [];
                $row['url'] = $url['url'];
                $row['session'] = Display::url(
                    $session['name'],
                    $sessionUrl.$sessionId
                );

                if (!empty($operation)) {
                    $row['session'] = strip_tags($row['session']);
                }

                $courseList = SessionManager::getCoursesInSession($sessionId);
                $courseTitleList = [];
                $courseListInString = '';
                foreach ($courseList as $courseId) {
                    if (!isset($courseListInfo[$courseId])) {
                        $courseListInfo[$courseId] = $courseInfo = api_get_course_info_by_id($courseId);
                    } else {
                        $courseInfo = $courseListInfo[$courseId];
                    }
                    $courseTitleList[] = $courseInfo['title'];
                }
                $courseListInString = implode(', ', $courseTitleList);

                $table = Database::get_main_table(TABLE_MAIN_SESSION_USER);
                $sql = "SELECT
                            count(DISTINCT user_id) count
                            FROM $table
                            WHERE
                                relation_type = 0 AND
                                registered_at >= '$start' AND  
                                registered_at <= '$end' AND
                                session_id = '$sessionId' ";
                $result = Database::query($sql);
                $result = Database::fetch_array($result);

                $row['course'] = $courseListInString;
                $row['count'] = $result['count'];
                $list[] = $row;
            }
        }

        if (!empty($operation)) {
            $fileName = !empty($action) ? api_get_setting('siteName').
                '_'.get_lang('PortalUserSessionStats').'_'.api_get_local_time() : 'report';
            switch ($exportFormat) {
                case 'xls':
                    Export::arrayToXls($list, $fileName);
                    break;
                case 'xls_html':
                    //TODO add date if exists
                    $browser = new Browser();
                    if ($browser->getPlatform() == Browser::PLATFORM_WINDOWS) {
                        Export::export_table_xls_html($list, $fileName, 'ISO-8859-15');
                    } else {
                        Export::export_table_xls_html($list, $fileName);
                    }
                    break;
                case 'csv':
                default:
                    Export::arrayToCsv($list, $fileName);
                    break;
            }
        }

        echo json_encode($list);
        break;
    case 'recent_logins':
        // Give a JSON array to the stats page main/admin/statistics/index.php
        // for global recent logins
        header('Content-type: application/json');
        $list = [];
        $all = Statistics::getRecentLoginStats(false, $sessionDuration);
        foreach ($all as $tick => $tock) {
            $list['labels'][] = $tick;
        }

        $list['datasets'][0]['label'] = get_lang('Logins');
        $list['datasets'][0]['backgroundColor'] = 'rgba(151,187,205,0.2)';
        $list['datasets'][0]['borderColor'] = 'rgba(151,187,205,1)';
        $list['datasets'][0]['pointBackgroundColor'] = 'rgba(151,187,205,1)';
        $list['datasets'][0]['pointBorderColor'] = '#fff';
        $list['datasets'][0]['pointHoverBackgroundColor'] = '#fff';
        $list['datasets'][0]['pointHoverBorderColor'] = 'rgba(151,187,205,1)';

        foreach ($all as $tick => $tock) {
            $list['datasets'][0]['data'][] = $tock;
        }

        $list['datasets'][1]['label'] = get_lang('DistinctUsersLogins');
        $list['datasets'][1]['backgroundColor'] = 'rgba(0,204,0,0.2)';
        $list['datasets'][1]['borderColor'] = 'rgba(0,204,0,1)';
        $list['datasets'][1]['pointBackgroundColor'] = 'rgba(0,204,0,1)';
        $list['datasets'][1]['pointBorderColor'] = '#fff';
        $list['datasets'][1]['pointHoverBackgroundColor'] = '#fff';
        $list['datasets'][1]['pointHoverBorderColor'] = 'rgba(0,204,0,1)';

        $distinct = Statistics::getRecentLoginStats(true, $sessionDuration);
        foreach ($distinct as $tick => $tock) {
            $list['datasets'][1]['data'][] = $tock;
        }

        echo json_encode($list);
        break;
    case 'tools_usage':
    case 'courses':
    case 'courses_by_language':
    case 'users':
    case 'users_teachers':
    case 'users_students':
        // Give a JSON array to the stats page main/admin/statistics/index.php
        // for global tools usage (number of clicks)
        header('Content-type: application/json');
        $list = [];
        $palette = ChamiloApi::getColorPalette(true, true);
        if ($action == 'tools_usage') {
            $statsName = 'Tools';
            $all = Statistics::getToolsStats();
        } elseif ($action == 'courses') {
            $statsName = 'CountCours';
            $course_categories = Statistics::getCourseCategories();
            // total amount of courses
            $all = [];
            foreach ($course_categories as $code => $name) {
                $all[$name] = Statistics::countCourses($code);
            }
        } elseif ($action == 'courses_by_language') {
            $statsName = 'CountCourseByLanguage';
            $all = Statistics::printCourseByLanguageStats();
            // use slightly different colors than previous chart
            for ($k = 0; $k < 3; $k++) {
                $item = array_shift($palette);
                array_push($palette, $item);
            }
        } elseif ($action == 'users') {
            $statsName = 'NumberOfUsers';
            $countInvisible = isset($_GET['count_invisible']) ? (int) $_GET['count_invisible'] : null;
            $all = [
                get_lang('Teachers') => Statistics::countUsers(COURSEMANAGER, null, $countInvisible),
                get_lang('Students') => Statistics::countUsers(STUDENT, null, $countInvisible),
            ];
        } elseif ($action == 'users_teachers') {
            $statsName = 'Teachers';
            $course_categories = Statistics::getCourseCategories();
            $countInvisible = isset($_GET['count_invisible']) ? (int) $_GET['count_invisible'] : null;
            $all = [];
            foreach ($course_categories as $code => $name) {
                $name = str_replace(get_lang('Department'), "", $name);
                $all[$name] = Statistics::countUsers(COURSEMANAGER, $code, $countInvisible);
            }
            // use slightly different colors than previous chart
            for ($k = 0; $k < 3; $k++) {
                $item = array_shift($palette);
                array_push($palette, $item);
            }
        } elseif ($action == 'users_students') {
            $statsName = 'Students';
            $course_categories = Statistics::getCourseCategories();
            $countInvisible = isset($_GET['count_invisible']) ? (int) $_GET['count_invisible'] : null;
            $all = [];
            foreach ($course_categories as $code => $name) {
                $name = str_replace(get_lang('Department'), "", $name);
                $all[$name] = Statistics::countUsers(STUDENT, $code, $countInvisible);
            }
            // use slightly different colors than previous chart
            for ($k = 0; $k < 6; $k++) {
                $item = array_shift($palette);
                array_push($palette, $item);
            }
        }
        foreach ($all as $tick => $tock) {
            $list['labels'][] = $tick;
        }

        $list['datasets'][0]['label'] = get_lang($statsName);
        $list['datasets'][0]['borderColor'] = 'rgba(255,255,255,1)';

        $i = 0;
        foreach ($all as $tick => $tock) {
            $j = $i % count($palette);
            $list['datasets'][0]['data'][] = $tock;
            $list['datasets'][0]['backgroundColor'][] = $palette[$j];
            $i++;
        }

        echo json_encode($list);
        break;
}
