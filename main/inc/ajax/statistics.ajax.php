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
    case 'add_student_to_boss':
        $studentId =  isset($_GET['student_id']) ? (int) $_GET['student_id'] : 0;
        $bossId =  isset($_GET['boss_id']) ? (int) $_GET['boss_id'] : 0;

        if ($studentId && $bossId) {
            UserManager::subscribeBossToUsers($bossId, [$studentId], false);
        }

        echo Statistics::getBossTable($bossId);
        exit;
        break;
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

        header('Content-type: application/json');
        echo json_encode($list);
        break;
    case 'users_active':
        $list = [];
        $palette = ChamiloApi::getColorPalette(true, true);

        $statsName = 'NumberOfUsers';
        $filter = $_REQUEST['filter'];

        $startDate = $_REQUEST['date_start'];
        $endDate = $_REQUEST['date_end'];

        $extraConditions = '';
        if (!empty($startDate) && !empty($endDate)) {
            $extraConditions .= " AND registration_date BETWEEN '$startDate' AND '$endDate' ";
        }

        switch ($filter) {
            case 'active':
                $conditions = ['status' => STUDENT, 'active' => 1];
                $active = UserManager::getUserListExtraConditions(
                    $conditions,
                    [],
                    false,
                    false,
                    null,
                    $extraConditions,
                    true
                );
                $conditions = ['status' => STUDENT, 'active' => 0];
                $noActive = UserManager::getUserListExtraConditions(
                    $conditions,
                    [],
                    false,
                    false,
                    null,
                    $extraConditions,
                    true
                );

                $all = [
                    get_lang('Active') => $active,
                    get_lang('Inactive') => $noActive,
                ];

                break;
            case 'status':
                $statusList = api_get_status_langvars();
                unset($statusList[ANONYMOUS]);

                foreach ($statusList as $status => $name) {
                    $conditions = ['status' => $status];
                    $count = UserManager::getUserListExtraConditions(
                        $conditions,
                        [],
                        false,
                        false,
                        null,
                        $extraConditions,
                        true
                    );
                    $all[$name] = $count;
                }
                break;
            case 'language':
                $languages = api_get_languages();
                $all = [];
                foreach ($languages['folder'] as $language) {
                    $conditions = ['language' => $language];
                    $key = $language;
                    if (substr($language, -1) === '2') {
                        $key = str_replace(2, '', $language);
                    }
                    $all[$key] = UserManager::getUserListExtraConditions(
                        $conditions,
                        [],
                        false,
                        false,
                        null,
                        $extraConditions,
                        true
                    );
                }

                break;
            case 'language_cible':
                $extraFieldValueUser = new ExtraField('user');
                $extraField = $extraFieldValueUser->get_handler_field_info_by_field_variable('langue_cible');

                $users = UserManager::getUserListExtraConditions(
                    [],
                    [],
                    false,
                    false,
                    null,
                    $extraConditions,
                    false
                );

                $userIdList = array_column($users, 'user_id');
                $userIdListToString = implode("', '", $userIdList);

                $all = [];
                foreach ($extraField['options'] as $item) {
                    $value = Database::escape_string($item['option_value']);
                    $count = 0;
                    $sql = "SELECT count(id) count
                            FROM $extraFieldValueUser->table_field_values
                            WHERE
                            value = '$value' AND
                            item_id IN ('$userIdListToString') AND
                            field_id = ".$extraField['id'];
                    $query = Database::query($sql);
                    $result = Database::fetch_array($query);
                    $count = $result['count'];
                    //$item['display_text'] = str_replace('2', '', $item['display_text']);
                    $all[$item['display_text']] = $count;
                }

                break;

            case 'career':
                $extraFieldValueUser = new ExtraField('user');
                $extraField = $extraFieldValueUser->get_handler_field_info_by_field_variable('filiere_user');

                $all = [];
                $users = UserManager::getUserListExtraConditions(
                    [],
                    [],
                    false,
                    false,
                    null,
                    $extraConditions,
                    false
                );

                $userIdList = array_column($users, 'user_id');
                $userIdListToString = implode("', '", $userIdList);
                foreach ($extraField['options'] as $item) {
                    $value = Database::escape_string($item['option_value']);
                    $count = 0;
                    $sql = "SELECT count(id) count
                            FROM $extraFieldValueUser->table_field_values
                            WHERE
                            value = '$value' AND
                            item_id IN ('$userIdListToString') AND
                            field_id = ".$extraField['id'];
                    $query = Database::query($sql);
                    $result = Database::fetch_array($query);
                    $count = $result['count'];
                    $all[$item['display_text']] = $count;
                }
                break;
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

        header('Content-type: application/json');
        echo json_encode($list);
        break;

    case 'session_by_date':
        $list = [];
        $palette = ChamiloApi::getColorPalette(true, true);

        $statsName = 'NumberOfUsers';
        $filter = $_REQUEST['filter'];

        $startDate = Database::escape_string($_REQUEST['date_start']);
        $endDate = Database::escape_string($_REQUEST['date_end']);

        /*$extraConditions = '';
        if (!empty($startDate) && !empty($endDate)) {
            $extraConditions .= " AND registration_date BETWEEN '$startDate' AND '$endDate' ";
        }*/
        $table = Database::get_main_table(TABLE_MAIN_SESSION);

        switch ($filter) {
            case 'category':
                $sql = "SELECT count(id) count, session_category_id FROM $table
                        WHERE
                            display_start_date BETWEEN '$startDate' AND '$endDate' OR
                            display_end_date BETWEEN '$startDate' AND '$endDate'
                        GROUP BY session_category_id
                    ";

                $result = Database::query($sql);
                $all = [];
                while ($row = Database::fetch_array($result)) {
                    $categoryData = SessionManager::get_session_category($row['session_category_id']);
                    $label = get_lang('NoCategory');
                    if ($categoryData) {
                        $label = $categoryData['name'];
                    }
                    $all[$label] = $row['count'];
                }
                break;
            case 'status':
                $sessionStatusAllowed = api_get_configuration_value('allow_session_status');
                if (!$sessionStatusAllowed) {
                    exit;
                }

                $sql = "SELECT count(id) count, status FROM $table
                        WHERE
                            display_start_date BETWEEN '$startDate' AND '$endDate' OR
                            display_end_date BETWEEN '$startDate' AND '$endDate'
                        GROUP BY status
                    ";
                $result = Database::query($sql);
                $all = [];
                while ($row = Database::fetch_array($result)) {
                    $row['status'] = SessionManager::getStatusLabel($row['status']);
                    $all[$row['status']] = $row['count'];
                }

                break;
            case 'language':
                $sql = "SELECT id FROM $table
                        WHERE
                            display_start_date BETWEEN '$startDate' AND '$endDate' OR
                            display_end_date BETWEEN '$startDate' AND '$endDate'
                    ";

                $result = Database::query($sql);
                $all = [];
                while ($row = Database::fetch_array($result)) {
                    $courses = SessionManager::getCoursesInSession($row['id']);
                    $language = get_lang('Nothing');
                    if (!empty($courses)) {
                        $courseId = $courses[0];
                        $courseInfo = api_get_course_info_by_id($courseId);
                        $language = $courseInfo['language'];
                        $language = str_replace('2', '', $language);
                    }

                    if (!isset($all[$language])) {
                        $all[$language] = 0;
                    }
                    $all[$language] += 1;
                }
                break;
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

        header('Content-type: application/json');
        echo json_encode($list);
        break;
}
