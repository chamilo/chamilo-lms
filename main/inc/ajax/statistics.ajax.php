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
$table = '';

switch ($action) {
    case 'add_student_to_boss':
        $studentId = isset($_GET['student_id']) ? (int) $_GET['student_id'] : 0;
        $bossId = isset($_GET['boss_id']) ? (int) $_GET['boss_id'] : 0;

        if ($studentId && $bossId) {
            UserManager::subscribeUserToBossList($studentId, [$bossId], true);
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
                $urlTable = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);
                $sql = "SELECT
                            count(DISTINCT su.user_id) count
                            FROM $table su
                            INNER JOIN $urlTable au
                            ON (su.user_id = au.user_id)
                            WHERE
                                access_url_id = $urlId AND
                                su.relation_type = 0 AND
                                su.registered_at >= '$start' AND
                                su.registered_at <= '$end' AND
                                su.session_id = '$sessionId' ";
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
        $all = Statistics::getRecentLoginStats(false, $sessionDuration, [31]);
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

        $distinct = Statistics::getRecentLoginStats(true, $sessionDuration, [31]);
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
        if ($action === 'tools_usage') {
            $statsName = 'Tools';
            $all = Statistics::getToolsStats();
        } elseif ($action === 'courses') {
            $statsName = 'CountCours';
            $course_categories = Statistics::getCourseCategories();
            // total amount of courses
            $all = [];
            foreach ($course_categories as $code => $name) {
                $all[$name] = Statistics::countCourses($code);
            }
        } elseif ($action === 'courses_by_language') {
            $statsName = 'CountCourseByLanguage';
            $all = Statistics::printCourseByLanguageStats();
            // use slightly different colors than previous chart
            for ($k = 0; $k < 3; $k++) {
                $item = array_shift($palette);
                array_push($palette, $item);
            }
        } elseif ($action === 'users') {
            $statsName = 'NumberOfUsers';
            $countInvisible = isset($_GET['count_invisible']) ? (int) $_GET['count_invisible'] : null;
            $all = [
                get_lang('Teachers') => Statistics::countUsers(COURSEMANAGER, null, $countInvisible),
                get_lang('Students') => Statistics::countUsers(STUDENT, null, $countInvisible),
            ];
        } elseif ($action === 'users_teachers') {
            $statsName = 'Teachers';
            $course_categories = Statistics::getCourseCategories();
            $countInvisible = isset($_GET['count_invisible']) ? (int) $_GET['count_invisible'] : null;
            $all = [];
            foreach ($course_categories as $code => $name) {
                $name = str_replace(get_lang('Department'), '', $name);
                $all[$name] = Statistics::countUsers(COURSEMANAGER, $code, $countInvisible);
            }
            // use slightly different colors than previous chart
            for ($k = 0; $k < 3; $k++) {
                $item = array_shift($palette);
                array_push($palette, $item);
            }
        } elseif ($action === 'users_students') {
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
                $conditions = ['active' => 1];
                $active = UserManager::getUserListExtraConditions(
                    $conditions,
                    [],
                    false,
                    false,
                    null,
                    $extraConditions,
                    true
                );
                $conditions = ['active' => 0];
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
                $extraFieldValueUser = new ExtraField('user');
                $extraField = $extraFieldValueUser->get_handler_field_info_by_field_variable('statusocial');

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
                $total = count($users);
                $usersFound = 0;
                $extraFieldOption = new ExtraFieldOption('user');
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
                    $usersFound += $count;

                    $option = $extraFieldOption->get($item['id'], true);
                    $item['display_text'] = $option['display_text'];
                    $all[$item['display_text']] = $count;
                }
                $all[get_lang('N/A')] = $total - $usersFound;

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
                    if (!isset($all[$key])) {
                        $all[$key] = 0;
                    }
                    $key = get_lang($key);
                    $all[$key] += UserManager::getUserListExtraConditions(
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
                $total = count($users);
                $usersFound = 0;
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
                    $usersFound += $count;
                    $item['display_text'] = get_lang(str_replace('2', '', $item['display_text']));
                    $all[$item['display_text']] = $count;
                }
                $all[get_lang('N/A')] = $total - $usersFound;
                break;

            case 'age':
                $extraFieldValueUser = new ExtraField('user');
                $extraField = $extraFieldValueUser->get_handler_field_info_by_field_variable('terms_datedenaissance');

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
                $total = count($users);

                $sql = "SELECT value
                        FROM $extraFieldValueUser->table_field_values
                        WHERE
                        item_id IN ('$userIdListToString') AND
                        field_id = ".$extraField['id'];
                $query = Database::query($sql);
                $usersFound = 0;
                $now = new DateTime();
                $all = [
                    //get_lang('N/A') => 0,
                    '16-17' => 0,
                    '18-25' => 0,
                    '26-30' => 0,
                ];

                while ($row = Database::fetch_array($query)) {
                    $usersFound++;
                    if (!empty($row['value'])) {
                        $date1 = new DateTime($row['value']);
                        $interval = $now->diff($date1);
                        $years = (int) $interval->y;

                        if ($years >= 16 && $years <= 17) {
                            $all['16-17']++;
                        }
                        if ($years >= 18 && $years <= 25) {
                            $all['18-25']++;
                        }
                        if ($years >= 26 && $years <= 30) {
                            $all['26-30']++;
                        }
                    }
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
                $usersFound = 0;

                $total = count($users);
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
                    $usersFound += $count;
                }

                $all[get_lang('N/A')] = $total - $usersFound;
                break;

            case 'contract':
                $extraFieldValueUser = new ExtraField('user');
                $extraField = $extraFieldValueUser->get_handler_field_info_by_field_variable('termactivated');

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
                $total = count($users);
                $sql = "SELECT count(id) count
                        FROM $extraFieldValueUser->table_field_values
                        WHERE
                        value = 1 AND
                        item_id IN ('$userIdListToString') AND
                        field_id = ".$extraField['id'];
                $query = Database::query($sql);
                $result = Database::fetch_array($query);
                $count = $result['count'];

                $all[get_lang('Yes')] = $count;
                $all[get_lang('No')] = $total - $count;
                break;
            case 'certificate':
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

                $total = count($users);
                $userIdList = array_column($users, 'user_id');
                $certificateCount = 0;
                foreach ($userIdList as $userId) {
                    $certificate = GradebookUtils::get_certificate_by_user_id(
                        0,
                        $userId
                    );

                    if (!empty($certificate)) {
                        $certificateCount++;
                    }
                }

                $all[get_lang('Yes')] = $certificateCount;
                $all[get_lang('No')] = $total - $certificateCount;
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
        $statusId = (int) $_REQUEST['status'];
        $table = Database::get_main_table(TABLE_MAIN_SESSION);

        $statusCondition = '';
        if (!empty($statusId)) {
            $statusCondition .= " AND status = $statusId ";
        }

        switch ($filter) {
            case 'category':
                $sql = "SELECT count(id) count, session_category_id FROM $table
                        WHERE
                            (display_start_date BETWEEN '$startDate' AND '$endDate' OR
                            display_end_date BETWEEN '$startDate' AND '$endDate')
                            $statusCondition
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

                $table = Statistics::buildJsChartData($all, '');
                $table = $table['table'];
                break;
            case 'status':
                $sessionStatusAllowed = api_get_configuration_value('allow_session_status');
                if (!$sessionStatusAllowed) {
                    exit;
                }

                $sql = "SELECT count(id) count, status FROM $table
                        WHERE
                            (
                                display_start_date BETWEEN '$startDate' AND '$endDate' OR
                                display_end_date BETWEEN '$startDate' AND '$endDate'
                            )
                            $statusCondition
                        GROUP BY status
                    ";
                $result = Database::query($sql);
                $all = [];
                while ($row = Database::fetch_array($result)) {
                    $row['status'] = SessionManager::getStatusLabel($row['status']);
                    $all[$row['status']] = $row['count'];
                }
                $table = Statistics::buildJsChartData($all, '');
                $table = $table['table'];

                break;
            case 'language':
                $sql = "SELECT id FROM $table
                        WHERE
                            (display_start_date BETWEEN '$startDate' AND '$endDate' OR
                            display_end_date BETWEEN '$startDate' AND '$endDate')
                            $statusCondition
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
                        $language = get_lang(ucfirst(str_replace(2, '', $language)));
                    }

                    if (!isset($all[$language])) {
                        $all[$language] = 0;
                    }
                    $all[$language]++;
                }
                $table = Statistics::buildJsChartData($all, '');
                $table = $table['table'];
                break;
            case 'course_in_session':
                $sql = "SELECT id FROM $table
                        WHERE
                            (display_start_date BETWEEN '$startDate' AND '$endDate' OR
                            display_end_date BETWEEN '$startDate' AND '$endDate')
                            $statusCondition
                    ";

                $result = Database::query($sql);

                $all = [];
                $courseSessions = [];
                $total = 0;
                while ($row = Database::fetch_array($result)) {
                    $courseList = SessionManager::getCoursesInSession($row['id']);
                    foreach ($courseList as $courseId) {
                        if (!isset($courseSessions[$courseId])) {
                            $courseSessions[$courseId] = 0;
                        }
                        $courseSessions[$courseId]++;
                        $total++;
                    }
                }

                if (!empty($courseSessions)) {
                    arsort($courseSessions);
                    foreach ($courseSessions as $courseId => $count) {
                        $courseInfo = api_get_course_info_by_id($courseId);
                        $all[$courseInfo['name']] = $count;
                    }
                }
                $table = Statistics::buildJsChartData($all, '');
                $table = $table['table'];

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

        $list['table'] = $table;

        header('Content-type: application/json');
        echo json_encode($list);
        break;
}
