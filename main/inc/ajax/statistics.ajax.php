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
    case 'report_quarterly_users':

        $currentQuarterDates = getQuarterDates();
        $pre1QuarterDates = getQuarterDates(
            date_create($currentQuarterDates['quarter_start'])
                ->modify('-3 month')
                ->format('Y-m-d')
        );
        $pre2QuarterDates = getQuarterDates(
            date_create($currentQuarterDates['quarter_start'])
                ->modify('-6 month')
                ->format('Y-m-d')
        );
        $pre3QuarterDates = getQuarterDates(
            date_create($currentQuarterDates['quarter_start'])
                ->modify('-9 month')
                ->format('Y-m-d')
        );
        $pre4QuarterDates = getQuarterDates(
            date_create($currentQuarterDates['quarter_start'])
                ->modify('-12 month')
                ->format('Y-m-d')
        );
        $pre5QuarterDates = getQuarterDates(
            date_create($currentQuarterDates['quarter_start'])
                ->modify('-15 month')
                ->format('Y-m-d')
        );

        // Make de headers for the table
        $headers = [
            '',
            $pre5QuarterDates['quarter_title'],
            $pre4QuarterDates['quarter_title'],
            $pre3QuarterDates['quarter_title'],
            $pre2QuarterDates['quarter_title'],
            $pre1QuarterDates['quarter_title'],
            get_lang('YoY'),
            $currentQuarterDates['quarter_title'].'*',
        ];

        // Get the data for the number of user registered row (2)
        $countUsersTotal = UserManager::get_number_of_users(
            null,
            null,
            null
        );
        $countUsersPre1Quarter = UserManager::get_number_of_users(
            null,
            null,
            null,
            null,
            $pre1QuarterDates['quarter_end']
        );
        $countUsersPre2Quarter = UserManager::get_number_of_users(
            null,
            null,
            null,
            null,
            $pre2QuarterDates['quarter_end']
        );
        $countUsersPre3Quarter = UserManager::get_number_of_users(
            null,
            null,
            null,
            null,
            $pre3QuarterDates['quarter_end']
        );
        $countUsersPre4Quarter = UserManager::get_number_of_users(
            null,
            null,
            null,
            null,
            $pre4QuarterDates['quarter_end']
        );
        $countUsersPre5Quarter = UserManager::get_number_of_users(
            null,
            null,
            null,
            null,
            $pre5QuarterDates['quarter_end']
        );
        // Calculate percent for first row
        $percentIncrementUsersRegistered = api_calculate_increment_percent(
            $countUsersPre1Quarter,
            $countUsersPre5Quarter
        );

        // Get the data for number of users connected row (3)
        $countUsersConnectedCurrentQuarter = count(
            Statistics::getLoginsByDate(
                $currentQuarterDates['quarter_start'],
                $currentQuarterDates['quarter_end']
            )
        );
        $countUsersConnectedPre1Quarter = count(
            Statistics::getLoginsByDate(
                $pre1QuarterDates['quarter_start'],
                $pre1QuarterDates['quarter_end']
            )
        );
        $countUsersConnectedPre2Quarter = count(
            Statistics::getLoginsByDate(
                $pre2QuarterDates['quarter_start'],
                $pre2QuarterDates['quarter_end']
            )
        );
        $countUsersConnectedPre3Quarter = count(
            Statistics::getLoginsByDate(
                $pre3QuarterDates['quarter_start'],
                $pre3QuarterDates['quarter_end']
            )
        );
        $countUsersConnectedPre4Quarter = count(
            Statistics::getLoginsByDate(
                $pre4QuarterDates['quarter_start'],
                $pre4QuarterDates['quarter_end']
            )
        );
        $countUsersConnectedPre5Quarter = count(
            Statistics::getLoginsByDate(
                $pre5QuarterDates['quarter_start'],
                $pre5QuarterDates['quarter_end']
            )
        );

        // Calculate percent for second row
        $percentIncrementUsersConnected = api_calculate_increment_percent(
            $countUsersConnectedPre1Quarter,
            $countUsersConnectedPre5Quarter
        );

        //Make de rows with the recollected data
        $rows = [];
        $rows[] = [
            get_lang('NumberOfUsersRegisteredTotal'),
            $countUsersPre5Quarter,
            $countUsersPre4Quarter,
            $countUsersPre3Quarter,
            $countUsersPre2Quarter,
            $countUsersPre1Quarter,
            $percentIncrementUsersRegistered,
            $countUsersTotal,
        ];
        //todo comprobacion + -
        $rows[] = [
            get_lang('NumberOfUsersRegisteredCompared'),
            '-',
            '+'.($countUsersPre1Quarter - $countUsersPre2Quarter),
            '+'.($countUsersPre2Quarter - $countUsersPre3Quarter),
            '+'.($countUsersPre3Quarter - $countUsersPre4Quarter),
            '+'.($countUsersPre4Quarter - $countUsersPre5Quarter),
            '-',
            '+'.($countUsersTotal - $countUsersPre1Quarter),
        ];
        $rows[] = [
            get_lang('NumberOfUsersWhoConnected'),
            $countUsersConnectedPre5Quarter,
            $countUsersConnectedPre4Quarter,
            $countUsersConnectedPre3Quarter,
            $countUsersConnectedPre2Quarter,
            $countUsersConnectedPre1Quarter,
            $percentIncrementUsersConnected,
            $countUsersConnectedCurrentQuarter,
        ];

        echo Display::table($headers, $rows, []);
        echo Display::label(get_lang('IncompleteDataCurrentQuarter'), 'warning');
        break;
    case 'report_quarterly_courses':

        $currentQuarterDates = getQuarterDates();
        $pre1QuarterDates = getQuarterDates(
            date_create($currentQuarterDates['quarter_start'])
                ->modify('-3 month')
                ->format('Y-m-d')
        );
        $pre2QuarterDates = getQuarterDates(
            date_create($currentQuarterDates['quarter_start'])
                ->modify('-6 month')
                ->format('Y-m-d')
        );
        $pre3QuarterDates = getQuarterDates(
            date_create($currentQuarterDates['quarter_start'])
                ->modify('-9 month')
                ->format('Y-m-d')
        );
        $pre4QuarterDates = getQuarterDates(
            date_create($currentQuarterDates['quarter_start'])
                ->modify('-12 month')
                ->format('Y-m-d')
        );
        $pre5QuarterDates = getQuarterDates(
            date_create($currentQuarterDates['quarter_start'])
                ->modify('-15 month')
                ->format('Y-m-d')
        );

        // Make the headers for the table
        $headers = [
            '',
            $pre5QuarterDates['quarter_title'],
            $pre4QuarterDates['quarter_title'],
            $pre3QuarterDates['quarter_title'],
            $pre2QuarterDates['quarter_title'],
            $pre1QuarterDates['quarter_title'],
            get_lang('YoY'),
            $currentQuarterDates['quarter_title'].'*',
        ];

        // Get the data for the rows
        $countCoursesCurrentQuarter = Statistics::countCourses(null, null, null);
        $countCoursesPre1Quarter = Statistics::countCourses(null, null, $pre1QuarterDates['quarter_end']);
        $countCoursesPre2Quarter = Statistics::countCourses(null, null, $pre2QuarterDates['quarter_end']);
        $countCoursesPre3Quarter = Statistics::countCourses(null, null, $pre3QuarterDates['quarter_end']);
        $countCoursesPre4Quarter = Statistics::countCourses(null, null, $pre4QuarterDates['quarter_end']);
        $countCoursesPre5Quarter = Statistics::countCourses(null, null, $pre5QuarterDates['quarter_end']);

        $auxArrayVisibilities = [
            COURSE_VISIBILITY_OPEN_WORLD,
            COURSE_VISIBILITY_OPEN_PLATFORM,
            COURSE_VISIBILITY_REGISTERED,
        ];

        $countCoursesAvailableCurrentQuarter = Statistics::countCoursesByVisibility($auxArrayVisibilities);
        $countCoursesAvailablePre1Quarter = Statistics::countCoursesByVisibility(
            $auxArrayVisibilities,
            null,
            $pre1QuarterDates['quarter_end']
        );
        $countCoursesAvailablePre2Quarter = Statistics::countCoursesByVisibility(
            $auxArrayVisibilities,
            null,
            $pre2QuarterDates['quarter_end']
        );
        $countCoursesAvailablePre3Quarter = Statistics::countCoursesByVisibility(
            $auxArrayVisibilities,
            null,
            $pre3QuarterDates['quarter_end']
        );
        $countCoursesAvailablePre4Quarter = Statistics::countCoursesByVisibility(
            $auxArrayVisibilities,
            null,
            $pre4QuarterDates['quarter_end']
        );
        $countCoursesAvailablePre5Quarter = Statistics::countCoursesByVisibility(
            $auxArrayVisibilities,
            null,
            $pre5QuarterDates['quarter_end']
        );

        // Calculate percents for first row
        $percentIncrementCourses = api_calculate_increment_percent(
            $countCoursesPre1Quarter,
            $countCoursesPre5Quarter
        );
        // Calculate percents for second row
        $percentIncrementUsersRegistered = api_calculate_increment_percent(
            $countCoursesAvailablePre1Quarter,
            $countCoursesAvailablePre5Quarter
        );

        //Make the rows with the recollected data
        $rows = [];
        $rows[] = [
            get_lang('NumberOfExistingCoursesTotal'),
            $countCoursesPre5Quarter,
            $countCoursesPre4Quarter,
            $countCoursesPre3Quarter,
            $countCoursesPre2Quarter,
            $countCoursesPre1Quarter,
            $percentIncrementCourses,
            $countCoursesCurrentQuarter,
        ];
        $rows[] = [
            get_lang('NumberOfAvailableCourses'),
            $countCoursesAvailablePre5Quarter,
            $countCoursesAvailablePre4Quarter,
            $countCoursesAvailablePre3Quarter,
            $countCoursesAvailablePre2Quarter,
            $countCoursesAvailablePre1Quarter,
            $percentIncrementUsersRegistered,
            $countCoursesAvailableCurrentQuarter,
        ];

        echo Display::table($headers, $rows, []);
        echo Display::label(get_lang('IncompleteDataCurrentQuarter'), 'warning');
    break;
    case 'report_quarterly_hours_of_training':

        $currentQuarterDates = getQuarterDates();
        $pre1QuarterDates = getQuarterDates(
            date_create($currentQuarterDates['quarter_start'])
                ->modify('-3 month')
                ->format('Y-m-d')
        );
        $pre2QuarterDates = getQuarterDates(
            date_create($currentQuarterDates['quarter_start'])
                ->modify('-6 month')
                ->format('Y-m-d')
        );
        $pre3QuarterDates = getQuarterDates(
            date_create($currentQuarterDates['quarter_start'])
                ->modify('-9 month')
                ->format('Y-m-d')
        );
        $pre4QuarterDates = getQuarterDates(
            date_create($currentQuarterDates['quarter_start'])
                ->modify('-12 month')
                ->format('Y-m-d')
        );
        $pre5QuarterDates = getQuarterDates(
            date_create($currentQuarterDates['quarter_start'])
                ->modify('-15 month')
                ->format('Y-m-d')
        );

        // Make the headers for the table
        $headers = [
            '',
            $pre5QuarterDates['quarter_title'],
            $pre4QuarterDates['quarter_title'],
            $pre3QuarterDates['quarter_title'],
            $pre2QuarterDates['quarter_title'],
            $pre1QuarterDates['quarter_title'],
            get_lang('YoY'),
            $currentQuarterDates['quarter_title'].'*',
        ];

        // Get data for the row
        $timeSpentCoursesCurrentQuarter = Tracking::getTotalTimeSpentInCourses(
            $currentQuarterDates['quarter_start'],
            $currentQuarterDates['quarter_end']
        );
        $timeSpentCourses1PreQuarter = Tracking::getTotalTimeSpentInCourses(
            $pre1QuarterDates['quarter_start'],
            $pre1QuarterDates['quarter_end']
        );
        $timeSpentCourses2PreQuarter = Tracking::getTotalTimeSpentInCourses(
            $pre2QuarterDates['quarter_start'],
            $pre2QuarterDates['quarter_end']
        );
        $timeSpentCourses3PreQuarter = Tracking::getTotalTimeSpentInCourses(
            $pre3QuarterDates['quarter_start'],
            $pre3QuarterDates['quarter_end']
        );
        $timeSpentCourses4PreQuarter = Tracking::getTotalTimeSpentInCourses(
            $pre4QuarterDates['quarter_start'],
            $pre4QuarterDates['quarter_end']
        );
        $timeSpentCourses5PreQuarter = Tracking::getTotalTimeSpentInCourses(
            $pre5QuarterDates['quarter_start'],
            $pre5QuarterDates['quarter_end']
        );

        // Calculate percent for the row
        $percentIncrementTimeSpent = api_calculate_increment_percent(
            $timeSpentCourses1PreQuarter,
            $timeSpentCourses5PreQuarter
        );

        //Make the row with the recollected data
        $rows = [];
        $rows[] = [
            get_lang('NumberOfHoursTrainingFollowed'),
            $timeSpentCourses5PreQuarter,
            $timeSpentCourses4PreQuarter,
            $timeSpentCourses3PreQuarter,
            $timeSpentCourses2PreQuarter,
            $timeSpentCourses1PreQuarter,
            $percentIncrementTimeSpent,
            $timeSpentCoursesCurrentQuarter,
        ];
        echo Display::table($headers, $rows, []);
        echo Display::label(get_lang('IncompleteDataCurrentQuarter'), 'warning');

    break;
    case 'report_quarterly_number_of_certificates_generated':

        $currentQuarterDates = getQuarterDates();
        $pre1QuarterDates = getQuarterDates(
            date_create($currentQuarterDates['quarter_start'])
                ->modify('-3 month')
                ->format('Y-m-d')
        );
        $pre2QuarterDates = getQuarterDates(
            date_create($currentQuarterDates['quarter_start'])
                ->modify('-6 month')
                ->format('Y-m-d')
        );
        $pre3QuarterDates = getQuarterDates(
            date_create($currentQuarterDates['quarter_start'])
                ->modify('-9 month')
                ->format('Y-m-d')
        );
        $pre4QuarterDates = getQuarterDates(
            date_create($currentQuarterDates['quarter_start'])
                ->modify('-12 month')
                ->format('Y-m-d')
        );
        $pre5QuarterDates = getQuarterDates(
            date_create($currentQuarterDates['quarter_start'])
                ->modify('-15 month')
                ->format('Y-m-d')
        );

        // Make the headers for the table
        $headers = [
            '',
            $pre5QuarterDates['quarter_title'],
            $pre4QuarterDates['quarter_title'],
            $pre3QuarterDates['quarter_title'],
            $pre2QuarterDates['quarter_title'],
            $pre1QuarterDates['quarter_title'],
            get_lang('YoY'),
            $currentQuarterDates['quarter_title'].'*',
        ];

        // Get data for the row
        $certificateGeneratedCurrentQuarter = Statistics::countCertificatesByQuarter(
            null,
            $currentQuarterDates['quarter_end']
        );
        $certificateGenerated1PreQuarter = Statistics::countCertificatesByQuarter(
            null,
            $pre1QuarterDates['quarter_end']
        );
        $certificateGenerated2PreQuarter = Statistics::countCertificatesByQuarter(
            null,
            $pre2QuarterDates['quarter_end']
        );
        $certificateGenerated3PreQuarter = Statistics::countCertificatesByQuarter(
            null,
            $pre3QuarterDates['quarter_end']
        );
        $certificateGenerated4PreQuarter = Statistics::countCertificatesByQuarter(
            null,
            $pre4QuarterDates['quarter_end']
        );
        $certificateGenerated5PreQuarter = Statistics::countCertificatesByQuarter(
            null,
            $pre5QuarterDates['quarter_end']
        );

        // Calculate percent for the row
        $percentIncrementCertificateGenerated = api_calculate_increment_percent(
            $certificateGenerated1PreQuarter,
            $certificateGenerated5PreQuarter
        );

        //Make the row with the recollected data
        $rows = [];
        $rows[] = [
            get_lang('NumberOfCertificatesGeneratedTotal'),
            $certificateGenerated5PreQuarter,
            $certificateGenerated4PreQuarter,
            $certificateGenerated3PreQuarter,
            $certificateGenerated2PreQuarter,
            $certificateGenerated1PreQuarter,
            $percentIncrementCertificateGenerated,
            $certificateGeneratedCurrentQuarter,
        ];

        echo Display::table($headers, $rows, []);
        echo Display::label(get_lang('IncompleteDataCurrentQuarter'), 'warning');

    break;
    case "report_quarterly_sessions_by_duration":

        $currentQuarterDates = getQuarterDates();
        $pre1QuarterDates = getQuarterDates(
            date_create($currentQuarterDates['quarter_start'])
                ->modify('-3 month')
                ->format('Y-m-d')
        );
        $pre2QuarterDates = getQuarterDates(
            date_create($currentQuarterDates['quarter_start'])
                ->modify('-6 month')
                ->format('Y-m-d')
        );
        $pre3QuarterDates = getQuarterDates(
            date_create($currentQuarterDates['quarter_start'])
                ->modify('-9 month')
                ->format('Y-m-d')
        );
        $pre4QuarterDates = getQuarterDates(
            date_create($currentQuarterDates['quarter_start'])
                ->modify('-12 month')
                ->format('Y-m-d')
        );
        $pre5QuarterDates = getQuarterDates(
            date_create($currentQuarterDates['quarter_start'])
                ->modify('-15 month')
                ->format('Y-m-d')
        );

        // Make the headers for the table
        $headers = [
            get_lang('SessionsByDurationByQuarter'),
            $pre5QuarterDates['quarter_title'],
            $pre4QuarterDates['quarter_title'],
            $pre3QuarterDates['quarter_title'],
            $pre2QuarterDates['quarter_title'],
            $pre1QuarterDates['quarter_title'],
            get_lang('YoY'),
            $currentQuarterDates['quarter_title'].'*',
        ];

        // Get the data for the rows
        $sessionsDurationCurrentQuarter = Statistics::getSessionsByDuration(
            $currentQuarterDates['quarter_start'],
            $currentQuarterDates['quarter_end']
        );
        $sessionsDuration1PreQuarter = Statistics::getSessionsByDuration(
            $pre1QuarterDates['quarter_start'],
            $pre1QuarterDates['quarter_end']
        );
        $sessionsDuration2PreQuarter = Statistics::getSessionsByDuration(
            $pre2QuarterDates['quarter_start'],
            $pre2QuarterDates['quarter_end']
        );
        $sessionsDuration3PreQuarter = Statistics::getSessionsByDuration(
            $pre3QuarterDates['quarter_start'],
            $pre3QuarterDates['quarter_end']
        );
        $sessionsDuration4PreQuarter = Statistics::getSessionsByDuration(
            $pre4QuarterDates['quarter_start'],
            $pre4QuarterDates['quarter_end']
        );
        $sessionsDuration5PreQuarter = Statistics::getSessionsByDuration(
            $pre5QuarterDates['quarter_start'],
            $pre5QuarterDates['quarter_end']
        );

        // Calculate percent for the rows
        $percentIncrementSessionDuration0 = api_calculate_increment_percent(
            $sessionsDuration1PreQuarter['0'],
            $sessionsDuration5PreQuarter['0']
        );
        $percentIncrementSessionDuration5 = api_calculate_increment_percent(
            $sessionsDuration1PreQuarter['5'],
            $sessionsDuration5PreQuarter['5']
        );
        $percentIncrementSessionDuration10 = api_calculate_increment_percent(
            $sessionsDuration1PreQuarter['10'],
            $sessionsDuration5PreQuarter['10']
        );
        $percentIncrementSessionDuration15 = api_calculate_increment_percent(
            $sessionsDuration1PreQuarter['15'],
            $sessionsDuration5PreQuarter['15']
        );
        $percentIncrementSessionDuration30 = api_calculate_increment_percent(
            $sessionsDuration1PreQuarter['30'],
            $sessionsDuration5PreQuarter['30']
        );
        $percentIncrementSessionDuration60 = api_calculate_increment_percent(
            $sessionsDuration1PreQuarter['60'],
            $sessionsDuration5PreQuarter['60']
        );

        //Make the rows with the recollected data
        $rows = [];
        $rows[] = [
            '0-5&#8242;',
            $sessionsDuration5PreQuarter['0'],
            $sessionsDuration4PreQuarter['0'],
            $sessionsDuration3PreQuarter['0'],
            $sessionsDuration2PreQuarter['0'],
            $sessionsDuration1PreQuarter['0'],
            $percentIncrementSessionDuration0,
            $sessionsDurationCurrentQuarter['0'],
        ];
        $rows[] = [
            '6-10&#8242;',
            $sessionsDuration5PreQuarter['5'],
            $sessionsDuration4PreQuarter['5'],
            $sessionsDuration3PreQuarter['5'],
            $sessionsDuration2PreQuarter['5'],
            $sessionsDuration1PreQuarter['5'],
            $percentIncrementSessionDuration5,
            $sessionsDurationCurrentQuarter['5'],
        ];
        $rows[] = [
            '11-15&#8242;',
            $sessionsDuration5PreQuarter['10'],
            $sessionsDuration4PreQuarter['10'],
            $sessionsDuration3PreQuarter['10'],
            $sessionsDuration2PreQuarter['10'],
            $sessionsDuration1PreQuarter['10'],
            $percentIncrementSessionDuration10,
            $sessionsDurationCurrentQuarter['10'],
        ];
        $rows[] = [
            '16-30&#8242;',
            $sessionsDuration5PreQuarter['15'],
            $sessionsDuration4PreQuarter['15'],
            $sessionsDuration3PreQuarter['15'],
            $sessionsDuration2PreQuarter['15'],
            $sessionsDuration1PreQuarter['15'],
            $percentIncrementSessionDuration15,
            $sessionsDurationCurrentQuarter['15'],
        ];
        $rows[] = [
            '31-60&#8242;',
            $sessionsDuration5PreQuarter['30'],
            $sessionsDuration4PreQuarter['30'],
            $sessionsDuration3PreQuarter['30'],
            $sessionsDuration2PreQuarter['30'],
            $sessionsDuration1PreQuarter['30'],
            $percentIncrementSessionDuration30,
            $sessionsDurationCurrentQuarter['30'],
        ];
        $rows[] = [
            '60-&#8734;&#8242;',
            $sessionsDuration5PreQuarter['60'],
            $sessionsDuration4PreQuarter['60'],
            $sessionsDuration3PreQuarter['60'],
            $sessionsDuration2PreQuarter['60'],
            $sessionsDuration1PreQuarter['60'],
            $percentIncrementSessionDuration60,
            $sessionsDurationCurrentQuarter['60'],
        ];

        echo Display::table($headers, $rows, []);
        echo Display::label(get_lang('IncompleteDataCurrentQuarter'), 'warning');
    break;
    case "report_quarterly_courses_and_sessions":

        // Make the headers for the tables
        $headers = [
             [
                get_lang('ListOfCoursesCodes'),
                get_lang('NumberOfSubscribedUsers').'*',
                get_lang('NumberOfUsersWhoFinishedCourse'),
            ],
            [
                get_lang('ListOfCoursesCodesAndSessions'),
                get_lang('NumberOfSubscribedUsers').'*',
                get_lang('NumberOfUsersWhoFinishedCourse'),
            ],
        ];

        // Get the data fot the first table
        $courses = UserManager::countUsersWhoFinishedCourses();

        //Make the rows for first table
        $rows = [];
        foreach ($courses as $course => $data) {
            $course_url = api_get_path(WEB_CODE_PATH).'course_home/course_home.php?cidReq='.$course;
            $rows[] = [
                Display::url($course, $course_url, ['target' => SESSION_LINK_TARGET]),
                $data['subscribed'],
                $data['finished'],
            ];
        }

        echo Display::table($headers[0], $rows, []);

        //Get the data for the second table (with sessions)
        $courses = UserManager::countUsersWhoFinishedCoursesInSessions();

        //Make the rows for second table
        $rows = [];
        foreach ($courses as $course => $data) {
            $rows[] = [
                $course,
                $data['subscribed'],
                $data['finished'],
            ];
        }

        echo Display::tag('br', '', ['style' => 'margin-top: 25px;']);
        echo Display::table($headers[1], $rows, []);
        echo Display::tag('br', '', ['style' => 'margin-top: 25px;']);
        echo Display::label(get_lang('AllUsersIncludingInactiveIncluded'), 'warning');

        break;
    case "report_quarterly_total_disk_usage":

        $accessUrlId = api_get_current_access_url_id();

        if (api_is_windows_os()) {
            $message = get_lang('SpaceUsedOnSystemCannotBeMeasuredOnWindows');
        } else {
            $dir = api_get_path(SYS_PATH);
            $du = exec('du -sh '.$dir, $err);
            list($size, $none) = explode("\t", $du);
            unset($none);
            $limit = 0;
            if (isset($_configuration[$accessUrlId]['hosting_limit_disk_space'])) {
                $limit = $_configuration[$accessUrlId]['hosting_limit_disk_space'];
            }
            $message = sprintf(get_lang('TotalSpaceUsedByPortalXLimitIsYMB'), $size, $limit);
        }
        echo Display::tag('H5', $message, ['style' => 'margin-bottom: 25px;']);
    break;
}
