<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CoreBundle\Helpers\ChamiloHelper;
use League\Flysystem\FilesystemOperator;

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
    case 'get_user_registration_by_month':
        // Close the session as we don't need it any further
        session_write_close();
        $dateStart = Security::remove_XSS($_POST['date_start']);
        $dateEnd = Security::remove_XSS($_POST['date_end']);

        $registrations = Statistics::getNewUserRegistrations($dateStart, $dateEnd);
        $all = Statistics::groupByMonth($registrations);
        $labels = [];
        $data = [];
        foreach ($all as $month => $count) {
            $labels[] = $month;
            $data[] = $count;
        }

        echo json_encode(['labels' => $labels, 'data' => $data]);
        exit;
    case 'get_user_registration_by_day':
        // Close the session as we don't need it any further
        session_write_close();
        $year = intval($_POST['year']);
        $month = intval($_POST['month']);

        $startDate = "$year-$month-01";
        $endDate = date("Y-m-t", strtotime($startDate));
        $dailyData = Statistics::getNewUserRegistrations($startDate, $endDate);
        $labels = [];
        $data = [];
        foreach ($dailyData as $registration) {
            $labels[] = $registration['date'];
            $data[] = $registration['count'];
        }

        echo json_encode(['labels' => $labels, 'data' => $data]);
        exit;
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

        $urlList = Container::getAccessUrlRepository()->findAll($order);
        $sessionUrl = api_get_path(WEB_CODE_PATH).'session/resume_session.php?id_session=';

        $start = isset($_GET['start']) ? Database::escape_string(api_get_utc_datetime($_GET['start'])) : api_get_utc_datetime();
        $end = isset($_GET['end']) ? Database::escape_string(api_get_utc_datetime($_GET['end'])) : api_get_utc_datetime();

        if (!empty($operation)) {
            $list[] = [
                'URL',
                get_lang('Session'),
                get_lang('Course'),
                get_lang('Number of users'),
            ];
        }

        $courseListInfo = [];
        foreach ($urlList as $url) {
            $sessionList = SessionManager::get_sessions_list([], [], null, null, $url->getId());
            foreach ($sessionList as $session) {
                $sessionId = $session['id'];
                $row = [];
                $row['url'] = $url->getUrl();
                $row['session'] = Display::url(
                    $session['title'],
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
                                access_url_id = ".$url->getId()." AND
                                su.relation_type = ".Session::STUDENT." AND
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
                '_'.get_lang('Portal user session stats').'_'.api_get_local_time() : 'report';
            switch ($exportFormat) {
                case 'xls':
                    Export::arrayToXls($list, $fileName);
                    break;
                case 'xls_html':
                    //TODO add date if exists
                    Export::export_table_xls_html($list, $fileName);
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
        // Close the session as we don't need it any further
        session_write_close();
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

        $list['datasets'][1]['label'] = get_lang('Distinct users logins');
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
        // Close the session as we don't need it any further
        session_write_close();
        // Give a JSON array to the stats page main/admin/statistics/index.php
        // for global tools usage (number of clicks)
        $list = [];
        $palette = ChamiloHelper::getColorPalette(true, true);
        if ('tools_usage' == $action) {
            $statsName = 'Tools';
            $all = Statistics::getToolsStats();
        } elseif ('courses' == $action) {
            $courseCategoryRepo = Container::getCourseCategoryRepository();
            $categories = $courseCategoryRepo->findAll();
            $statsName = 'Total number of courses';
            // total amount of courses
            $all = [];
            foreach ($categories as $category) {
                /* @var Chamilo\CoreBundle\Entity\CourseCategory $category */
                $all[$category->getTitle()] = $category->getCourses()->count();
            }
        } elseif ('courses_by_language' == $action) {
            $statsName = 'Count course by language';
            $all = Statistics::printCourseByLanguageStats();
            // use slightly different colors than previous chart
            for ($k = 0; $k < 3; $k++) {
                $item = array_shift($palette);
                array_push($palette, $item);
            }
        } elseif ('users' == $action) {
            $statsName = 'Number of users';
            $countInvisible = isset($_GET['count_invisible']) ? (int) $_GET['count_invisible'] : null;
            $all = [
                get_lang('Trainers') => Statistics::countUsers(COURSEMANAGER, null, $countInvisible),
                get_lang('Learners') => Statistics::countUsers(STUDENT, null, $countInvisible),
            ];
        } elseif ('users_teachers' == $action) {
            $statsName = 'Teachers';
            $courseCategoryRepo = Container::getCourseCategoryRepository();
            $categories = $courseCategoryRepo->findAll();
            $countInvisible = isset($_GET['count_invisible']) ? (int) $_GET['count_invisible'] : null;
            $all = [];
            foreach ($categories as $category) {
                /* @var Chamilo\CoreBundle\Entity\CourseCategory $category */
                $code = $category->getCode();
                $name = $category->getTitle();
                $name = str_replace(get_lang('Department'), '', $name);
                $all[$name] = Statistics::countUsers(COURSEMANAGER, $code, $countInvisible);
            }
            // use slightly different colors than previous chart
            for ($k = 0; $k < 3; $k++) {
                $item = array_shift($palette);
                array_push($palette, $item);
            }
        } elseif ('users_students' == $action) {
            $statsName = 'Students';
            $courseCategoryRepo = Container::getCourseCategoryRepository();
            $categories = $courseCategoryRepo->findAll();
            $countInvisible = isset($_GET['count_invisible']) ? (int) $_GET['count_invisible'] : null;
            $all = [];
            foreach ($categories as $category) {
                /* @var Chamilo\CoreBundle\Entity\CourseCategory $category */
                $code = $category->getCode();
                $name = $category->getTitle();
                $name = str_replace(get_lang('Department'), '', $name);
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
        $palette = ChamiloHelper::getColorPalette(true, true);

        $statsName = 'Number of users';
        $filter = $_REQUEST['filter'];

        $startDate = $_REQUEST['date_start'];
        $endDate = $_REQUEST['date_end'];

        $extraConditions = '';
        if (!empty($startDate) && !empty($endDate)) {
            $extraConditions .= " AND created_at BETWEEN '$startDate' AND '$endDate' ";
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
                // Close the session as we don't need it any further
                session_write_close();
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

                    $option = $extraFieldOption->get($item['id']);
                    $item['display_text'] = $option['display_text'];
                    $all[$item['display_text']] = $count;
                }
                $all[get_lang('Not available')] = $total - $usersFound;

                break;
            case 'language':
                // Close the session as we don't need it any further
                session_write_close();
                $languages = api_get_languages();
                $all = [];
                foreach ($languages['folder'] as $language) {
                    $conditions = ['language' => $language];
                    $key = $language;
                    if ('2' === substr($language, -1)) {
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
                // Close the session as we don't need it any further
                session_write_close();
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
                $all[get_lang('Not available')] = $total - $usersFound;
                break;

            case 'age':
                // Close the session as we don't need it any further
                session_write_close();
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
                    //get_lang('Not available') => 0,
                    '16-17' => 0,
                    '18-25' => 0,
                    '26-30' => 0,
                ];

                while ($row = Database::fetch_array($query)) {
                    $usersFound++;
                    if (!empty($row['value'])) {
                        $date1 = new DateTime($row['value']);
                        $interval = $now->diff($date1);
                        if ($interval) {
                            $years = $interval->y;
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
                }

                break;

            case 'career':
                // Close the session as we don't need it any further
                session_write_close();
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

                $all[get_lang('Not available')] = $total - $usersFound;
                break;

            case 'contract':
                // Close the session as we don't need it any further
                session_write_close();
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
                // Close the session as we don't need it any further
                session_write_close();
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
        // Close the session as we don't need it any further
        session_write_close();
        $list = [];
        $palette = ChamiloHelper::getColorPalette(true, true);

        $statsName = 'Number of users';
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
                    $label = get_lang('Without category');
                    if ($categoryData) {
                        $label = $categoryData['name'];
                    }
                    $all[$label] = $row['count'];
                }

                $table = Statistics::buildJsChartData($all, '');
                $table = $table['table'];
                break;
            case 'status':
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
                // Close the session as we don't need it any further
                session_write_close();
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
                        $all[$courseInfo['title']] = $count;
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
        // Close the session as we don't need it any further
       session_write_close();
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
            get_lang('Number of users registered (total)'),
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
            get_lang('Number of users registered (new vs previous quarter)'),
            '-',
            '+'.($countUsersPre4Quarter - $countUsersPre5Quarter),
            '+'.($countUsersPre3Quarter - $countUsersPre4Quarter),
            '+'.($countUsersPre2Quarter - $countUsersPre3Quarter),
            '+'.($countUsersPre1Quarter - $countUsersPre2Quarter),
            '-',
            '+'.($countUsersTotal - $countUsersPre1Quarter),
        ];
        $rows[] = [
            get_lang('Number of users who connected'),
            $countUsersConnectedPre5Quarter,
            $countUsersConnectedPre4Quarter,
            $countUsersConnectedPre3Quarter,
            $countUsersConnectedPre2Quarter,
            $countUsersConnectedPre1Quarter,
            $percentIncrementUsersConnected,
            $countUsersConnectedCurrentQuarter,
        ];
        echo Display::table($headers, $rows, []);
        echo Display::label(get_lang('*: Current quarter, incomplete data'), 'warning');
        break;
    case 'report_quarterly_courses':
        // Close the session as we don't need it any further
        session_write_close();
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
            get_lang('Number of existing courses (total)'),
            $countCoursesPre5Quarter,
            $countCoursesPre4Quarter,
            $countCoursesPre3Quarter,
            $countCoursesPre2Quarter,
            $countCoursesPre1Quarter,
            $percentIncrementCourses,
            $countCoursesCurrentQuarter,
        ];
        $rows[] = [
            get_lang('Number of available courses (not closed or hidden, total)'),
            $countCoursesAvailablePre5Quarter,
            $countCoursesAvailablePre4Quarter,
            $countCoursesAvailablePre3Quarter,
            $countCoursesAvailablePre2Quarter,
            $countCoursesAvailablePre1Quarter,
            $percentIncrementUsersRegistered,
            $countCoursesAvailableCurrentQuarter,
        ];
        echo Display::table($headers, $rows, []);
        echo Display::label(get_lang('*: Current quarter, incomplete data'), 'warning');
        break;
    case 'report_quarterly_hours_of_training':
        // Close the session as we don't need it any further
        session_write_close();
        // The maximum time spent, in number of hours, to be considered.
        // Anything above that is considered a time registration error.
        $maxTimeSpent = 6;
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
            $currentQuarterDates['quarter_end'],
            $maxTimeSpent
        );
        $timeSpentCourses1PreQuarter = Tracking::getTotalTimeSpentInCourses(
            $pre1QuarterDates['quarter_start'],
            $pre1QuarterDates['quarter_end'],
            $maxTimeSpent
        );
        $timeSpentCourses2PreQuarter = Tracking::getTotalTimeSpentInCourses(
            $pre2QuarterDates['quarter_start'],
            $pre2QuarterDates['quarter_end'],
            $maxTimeSpent
        );
        $timeSpentCourses3PreQuarter = Tracking::getTotalTimeSpentInCourses(
            $pre3QuarterDates['quarter_start'],
            $pre3QuarterDates['quarter_end'],
            $maxTimeSpent
        );
        $timeSpentCourses4PreQuarter = Tracking::getTotalTimeSpentInCourses(
            $pre4QuarterDates['quarter_start'],
            $pre4QuarterDates['quarter_end'],
            $maxTimeSpent
        );
        $timeSpentCourses5PreQuarter = Tracking::getTotalTimeSpentInCourses(
            $pre5QuarterDates['quarter_start'],
            $pre5QuarterDates['quarter_end'],
            $maxTimeSpent
        );
        // Calculate percent for the row
        $percentIncrementTimeSpent = api_calculate_increment_percent(
            $timeSpentCourses1PreQuarter,
            $timeSpentCourses5PreQuarter
        );
        //Make the row with the recollected data
        $rows = [];
        $rows[] = [
            get_lang('Number of hours of training followed (total)'),
            $timeSpentCourses5PreQuarter,
            $timeSpentCourses4PreQuarter,
            $timeSpentCourses3PreQuarter,
            $timeSpentCourses2PreQuarter,
            $timeSpentCourses1PreQuarter,
            $percentIncrementTimeSpent,
            $timeSpentCoursesCurrentQuarter,
        ];
        echo Display::table($headers, $rows, []);
        echo Display::label(get_lang('*: Current quarter, incomplete data'), 'warning');
        break;
    case 'report_quarterly_number_of_certificates_generated':
        // Close the session as we don't need it any further
        session_write_close();
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
            get_lang('Number of certificates generated'),
            $certificateGenerated5PreQuarter,
            $certificateGenerated4PreQuarter,
            $certificateGenerated3PreQuarter,
            $certificateGenerated2PreQuarter,
            $certificateGenerated1PreQuarter,
            $percentIncrementCertificateGenerated,
            $certificateGeneratedCurrentQuarter,
        ];
        echo Display::table($headers, $rows, []);
        echo Display::label(get_lang('*: Current quarter, incomplete data'), 'warning');
        break;
    case "report_quarterly_sessions_by_duration":
        // Close the session as we don't need it any further
        session_write_close();
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
            get_lang('Sessions per duration (by quarter)'),
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
        echo Display::label(get_lang('*: Current quarter, incomplete data'), 'warning');
        break;
    case "report_quarterly_courses_and_sessions":
        // Close the session as we don't need it any further
        session_write_close();
        // Make the headers for the tables
        $headers = [
            [
                get_lang('List of course codes'),
                get_lang('Number of subscribed users').'*',
                get_lang('Number of users who finished the course (as defined in gradebook)'),
            ],
            [
                get_lang('List of course codes and sessions'),
                get_lang('Number of subscribed users').'*',
                get_lang('Number of users who finished the course (as defined in gradebook)'),
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
        echo Display::label(get_lang('*: All users, including inactive, are included'), 'warning');
        break;
    case "report_quarterly_total_disk_usage":
        // Close the session as we don't need it any further
        session_write_close();
        $accessUrlId = api_get_current_access_url_id();
        if (api_is_windows_os()) {
            $message = get_lang('The space used on disk cannot be measured properly on Windows-based systems.');
        } else {
            // @TODO Scanning the var folder should be done through oneup_flysystem
            /** @var FilesystemOperator $assetFS */
            //$assetFS = Container::$container->get('oneup_flysystem.asset_filesystem');
            /** @var FilesystemOperator $resourceFS */
            //$resourceFS = Container::$container->get('oneup_flysystem.resource_filesystem');
            /** @var FilesystemOperator $themesFS */
            //$themesFS = Container::$container->get('oneup_flysystem.themes_filesystem');
            /** @var FilesystemOperator $pluginsFS */
            //$pluginsFS = Container::$container->get('oneup_flysystem.plugins_filesystem');

            $dir = api_get_path(SYMFONY_SYS_PATH).'var/';
            $du = exec('du -s '.$dir, $err);
            list($size, $none) = explode("\t", $du);
            $size = round((int) $size / (1024*1024), 1);
            unset($none);
            $limit = '';
            $url = api_get_access_url($accessUrlId)['url'];
            if (!empty($_configuration[$accessUrlId]['hosting_limit_disk_space'])) {
                $limit = round($_configuration[$accessUrlId]['hosting_limit_disk_space'] / (1024), 1);
                $message = sprintf(get_lang('Total space used by portal %s is %sGB (limit is set to %sGB)'), $url, $size, $limit);
            } else {
                $message = sprintf(get_lang('Total space used by %s is %sGB'), $url, $size);
            }
        }
        echo Display::tag('H5', $message, ['style' => 'margin-bottom: 25px;']);
        break;
}
