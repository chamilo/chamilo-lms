<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Framework\Container;
use ChamiloSession as Session;

require_once __DIR__.'/../global.inc.php';

// 1. Setting variables needed by jqgrid
$action = $_GET['a'];
$page = (int) $_REQUEST['page']; //page
$limit = (int) $_REQUEST['rows']; //quantity of rows

// Makes max row persistence after refreshing the grid
$savedRows = Session::read('max_rows_'.$action);
if (empty($savedRows)) {
    Session::write('max_rows_'.$action, $limit);
} else {
    if ($limit != $savedRows) {
        Session::write('max_rows_'.$action, $limit);
    }
}

$sidx = $_REQUEST['sidx']; //index (field) to filter
$sord = $_REQUEST['sord']; //asc or desc
$exportFilename = isset($_REQUEST['export_filename']) ? $_REQUEST['export_filename'] : '';

if (false !== strpos(strtolower($sidx), 'asc')) {
    $sidx = str_replace(['asc', ','], '', $sidx);
    $sord = 'asc';
}

if (false !== strpos(strtolower($sidx), 'desc')) {
    $sidx = str_replace(['desc', ','], '', $sidx);
    $sord = 'desc';
}

if (!in_array($sord, ['asc', 'desc'])) {
    $sord = 'desc';
}

// Actions allowed to other roles.
if (!in_array(
        $action,
        [
            'get_exercise_results',
            'get_exercise_pending_results',
            'get_exercise_results_report',
            'get_work_student_list_overview',
            'get_work_teacher',
            'get_work_student',
            'get_all_work_student',
            'get_work_user_list',
            'get_work_user_list_others',
            'get_work_user_list_all',
            'get_work_pending_list',
            'get_user_skill_ranking',
            'get_usergroups',
            'get_usergroups_teacher',
            'get_user_course_report_resumed',
            'get_user_course_report',
            'get_sessions_tracking',
            'get_sessions',
            'get_course_announcements',
            'course_log_events',
            'get_learning_path_calendars',
            'get_usergroups_users',
            'get_calendar_users',
            'get_exercise_categories',
        ]
    ) && !isset($_REQUEST['from_course_session'])) {
    api_protect_admin_script(true);
} elseif (isset($_REQUEST['from_course_session']) &&
    1 == $_REQUEST['from_course_session']
) {
    api_protect_teacher_script(true);
}

$toRemove = ['extra_access_start_date', 'extra_access_end_date'];

// Search features

//@todo move this in the display_class or somewhere else
/**
 * @param string $col
 * @param string $oper
 * @param string $val
 *
 * @return string
 */
function getWhereClause($col, $oper, $val)
{
    $ops = [
        'eq' => '=', //equal
        'ne' => '<>', //not equal
        'lt' => '<', //less than
        'le' => '<=', //less than or equal
        'gt' => '>', //greater than
        'ge' => '>=', //greater than or equal
        'bw' => 'LIKE', //begins with
        'bn' => 'NOT LIKE', //doesn't begin with
        'in' => 'LIKE', //is in
        'ni' => 'NOT LIKE', //is not in
        'ew' => 'LIKE', //ends with
        'en' => 'NOT LIKE', //doesn't end with
        'cn' => 'LIKE', //contains
        'nc' => 'NOT LIKE',  //doesn't contain
    ];

    $col = Database::escapeField($col);

    if (empty($col)) {
        return '';
    }

    if ('bw' == $oper || 'bn' == $oper) {
        $val .= '%';
    }
    if ('ew' == $oper || 'en' == $oper) {
        $val = '%'.$val;
    }
    if ('cn' == $oper || 'nc' == $oper || 'in' == $oper || 'ni' == $oper) {
        $val = '%'.$val.'%';
    }
    $val = Database::escape_string($val);

    return " $col {$ops[$oper]} '$val' ";
}

// If there is no search request sent by jqgrid, $where should be empty
$whereCondition = '';
$operation = isset($_REQUEST['oper']) ? $_REQUEST['oper'] : false;
$exportFormat = isset($_REQUEST['export_format']) ? $_REQUEST['export_format'] : 'csv';
$searchField = isset($_REQUEST['searchField']) ? $_REQUEST['searchField'] : false;
$searchOperator = isset($_REQUEST['searchOper']) ? $_REQUEST['searchOper'] : false;
$searchString = isset($_REQUEST['searchString']) ? $_REQUEST['searchString'] : false;
$search = isset($_REQUEST['_search']) ? $_REQUEST['_search'] : false;
$forceSearch = isset($_REQUEST['_force_search']) ? $_REQUEST['_force_search'] : false;
$extra_fields = [];
$accessStartDate = '';
$accessEndDate = '';
$overwriteColumnHeaderExport = [];

if (!empty($search)) {
    $search = 'true';
}

if (($search || $forceSearch) && ('false' !== $search)) {
    $whereCondition = ' 1 = 1 ';
    $whereConditionInForm = getWhereClause(
        $searchField,
        $searchOperator,
        $searchString
    );

    if (!empty($whereConditionInForm)) {
        $whereCondition .= ' AND ( ';
        $whereCondition .= '  ('.$whereConditionInForm.') ';
    }
    $filters = isset($_REQUEST['filters']) && !is_array($_REQUEST['filters']) ? json_decode(
        $_REQUEST['filters']
    ) : false;
    if (isset($_REQUEST['filters2'])) {
        $filters = json_decode($_REQUEST['filters2']);
    }

    if (!empty($filters)) {
        if (in_array(
            $action,
            [
                'get_user_course_report_resumed',
                'get_user_course_report',
                'get_questions',
                'get_sessions',
                'get_sessions_tracking',
            ]
        )) {
            switch ($action) {
                case 'get_user_course_report_resumed':
                case 'get_user_course_report':
                    $type = 'user';
                    break;
                case 'get_questions':
                    $type = 'question';
                    break;
                case 'get_sessions':
                case 'get_sessions_tracking':
                    $type = 'session';
                    break;
            }

            if (!empty($type)) {
                // Extra field.
                $extraField = new ExtraField($type);

                if (is_object($filters)
                    && property_exists($filters, 'rules')
                    && is_array($filters->rules)
                    && !empty($filters->rules)
                ) {
                    foreach ($filters->rules as $key => $data) {
                        if (empty($data)) {
                            continue;
                        }
                        if ('extra_access_start_date' === $data->field) {
                            $accessStartDate = $data->data;
                        }

                        if ('extra_access_end_date' === $data->field) {
                            $accessEndDate = $data->data;
                        }

                        if (in_array($data->field, $toRemove)) {
                            unset($filters->rules[$key]);
                        }
                    }
                }

                $result = $extraField->getExtraFieldRules($filters, 'extra_');

                $extra_fields = $result['extra_fields'];
                $condition_array = $result['condition_array'];
                $extraCondition = '';
                if (!empty($condition_array)) {
                    $extraCondition = $filters->groupOp.' ( ';
                    $extraCondition .= implode($filters->groupOp, $condition_array);
                    $extraCondition .= ' ) ';
                }
                $whereCondition .= $extraCondition;

                // Question field
                $resultQuestion = $extraField->getExtraFieldRules(
                    $filters,
                    'question_'
                );
                $questionFields = $resultQuestion['extra_fields'];
                $condition_array = $resultQuestion['condition_array'];

                $extraQuestionCondition = '';
                if (!empty($condition_array)) {
                    $extraQuestionCondition = $filters->groupOp.' ( ';
                    $extraQuestionCondition .= implode($filters->groupOp, $condition_array);
                    $extraQuestionCondition .= ' ) ';
                    // Remove conditions already added
                    $extraQuestionCondition = str_replace(
                        $extraCondition,
                        '',
                        $extraQuestionCondition
                    );
                }

                $whereCondition .= $extraQuestionCondition;

                if (isset($filters->custom_dates)) {
                    $whereCondition .= $filters->custom_dates;
                }
            }
        } elseif (!empty($filters->rules)) {
            $whereCondition .= ' AND ( ';
            $counter = 0;
            foreach ($filters->rules as $key => $rule) {
                $whereCondition .= getWhereClause(
                    $rule->field,
                    $rule->op,
                    $rule->data
                );

                if ($counter < count($filters->rules) - 1) {
                    $whereCondition .= $filters->groupOp;
                }
                $counter++;
            }
            $whereCondition .= ' ) ';
        }
    }

    if (!empty($whereConditionInForm)) {
        $whereCondition .= ' ) ';
    }
}

// get index row - i.e. user click to sort $sord = $_GET['sord'];
// get the direction
if (!$sidx) {
    $sidx = 1;
}

//2. Selecting the count FIRST
//@todo rework this

switch ($action) {
    case 'get_exercise_categories':
        $courseId = isset($_REQUEST['c_id']) ? $_REQUEST['c_id'] : 0;
        $repo = Container::getExerciseCategoryRepository();
        $qb = $repo->getResourcesByCourse(api_get_course_entity($courseId));
        $count = $qb->select('COUNT(resource)')->getQuery()->getSingleScalarResult();

        break;
    case 'get_calendar_users':
        $calendarPlugin = LearningCalendarPlugin::create();
        $id = isset($_REQUEST['id']) ? $_REQUEST['id'] : 0;
        $count = $calendarPlugin->getUsersPerCalendarCount($id);
        break;
    case 'get_usergroups_users':
        $usergroup = new UserGroupModel();
        $usergroup->protectScript(null, true, true);
        $id = isset($_REQUEST['id']) ? $_REQUEST['id'] : 0;
        $count = $usergroup->getUserGroupUsers($id, true);
        break;
    case 'get_learning_path_calendars':
        $calendarPlugin = LearningCalendarPlugin::create();
        $count = $calendarPlugin->getCalendarCount();
        break;
    case 'course_log_events':
        $courseId = api_get_course_int_id();
        if (empty($courseId)) {
            exit;
        }
        $sessionId = api_get_session_id();
        if (!api_is_allowed_to_edit()) {
            exit;
        }
        $count = Statistics::getNumberOfActivities($courseId, $sessionId);
        break;
    case 'get_programmed_announcements':
        $object = new ScheduledAnnouncement();
        $count = $object->get_count();
        break;
    case 'get_group_reporting':
        $course_id = isset($_REQUEST['course_id']) ? $_REQUEST['course_id'] : null;
        $group_id = isset($_REQUEST['gidReq']) ? $_REQUEST['gidReq'] : null;
        $sessionId = isset($_REQUEST['session_id']) ? $_REQUEST['session_id'] : null;
        $count = Tracking::get_group_reporting(
            $course_id,
            $sessionId,
            $group_id,
            'count'
        );
        break;
    case 'get_user_course_report':
    case 'get_user_course_report_resumed':
        $userNotAllowed = !api_is_student_boss() && !api_is_platform_admin(false, true);

        if ($userNotAllowed) {
            exit;
        }
        $userId = api_get_user_id();
        $sessionId = isset($_GET['session_id']) ? (int) $_GET['session_id'] : 0;
        $courseCodeList = [];
        $userIdList = [];
        $sessionIdList = [];
        $searchByGroups = false;

        if (api_is_drh()) {
            if (api_drh_can_access_all_session_content()) {
                $userList = SessionManager::getAllUsersFromCoursesFromAllSessionFromStatus(
                    'drh_all',
                    api_get_user_id()
                );

                if (!empty($userList)) {
                    foreach ($userList as $user) {
                        $userIdList[] = $user['user_id'];
                    }
                }

                $courseList = SessionManager::getAllCoursesFollowedByUser(
                    api_get_user_id(),
                    null
                );
                if (!empty($courseList)) {
                    foreach ($courseList as $course) {
                        $courseCodeList[] = $course['code'];
                    }
                }
            } else {
                $userList = UserManager::get_users_followed_by_drh(api_get_user_id());
                if (!empty($userList)) {
                    $userIdList = array_keys($userList);
                }

                $courseList = CourseManager::get_courses_followed_by_drh(api_get_user_id());
                if (!empty($courseList)) {
                    $courseCodeList = array_keys($courseList);
                }
            }

            if (empty($userIdList) || empty($courseCodeList)) {
                exit;
            }
        } elseif (api_is_student_boss()) {
            $supervisorStudents = UserManager::getUsersFollowedByUser(
                api_get_user_id(),
                api_is_student_boss() ? null : STUDENT,
                false,
                false,
                false,
                null,
                null,
                null,
                null,
                1,
                null,
                api_is_student_boss() ? STUDENT_BOSS : COURSEMANAGER,
                null
            );
            $supervisorStudents = array_column($supervisorStudents, 'user_id');

            //get students with course or session
            $userIdList = SessionManager::getAllUsersFromCoursesFromAllSessionFromStatus(
                'admin',
                null,
                false,
                null,
                null,
                null,
                'asc',
                null,
                null,
                null,
                [],
                $supervisorStudents,
                5
            );
            $userIdList = array_column($userIdList, 'user_id');

            //get students session courses
            if (-1 == $sessionId) {
                $sessionList = SessionManager::get_sessions_list();
                $sessionIdList = array_column($sessionList, 'id');

                $courseCodeList = [];
                foreach ($sessionList as $session) {
                    $courses = SessionManager::get_course_list_by_session_id($session['id']);
                    $courseCodeList = array_merge($courseCodeList, array_column($courses, 'code'));
                }
            }

            $searchByGroups = true;
        } elseif (api_is_platform_admin()) {
            // Get students with course or session
            $userIdList = SessionManager::getAllUsersFromCoursesFromAllSessionFromStatus(
                'admin',
                null,
                false,
                null,
                null,
                null,
                'asc',
                null,
                null,
                null,
                [],
                [],
                5
            );
            $userIdList = array_column($userIdList, 'user_id');

            //get students session courses
            if (-1 == $sessionId) {
                $sessionList = SessionManager::get_sessions_list();
                $sessionIdList = array_column($sessionList, 'id');
                $courseCodeList = [];
                foreach ($sessionList as $session) {
                    $courses = SessionManager::get_course_list_by_session_id($session['id']);
                    $courseCodeList = array_merge(
                        $courseCodeList,
                        array_column($courses, 'code')
                    );
                }
            }
            $searchByGroups = true;
        }

        if ($searchByGroups) {
            $userGroup = new UserGroupModel();
            $userIdList = array_merge(
                $userIdList,
                $userGroup->getGroupUsersByUser(api_get_user_id())
            );
        }

        if (is_array($userIdList)) {
            $userIdList = array_unique($userIdList);
        }

        if (api_is_student_boss()) {
            $userCourses = [];
            foreach ($userIdList as $userId) {
                $userCourses = array_merge(
                    $userCourses,
                    CourseManager::get_courses_list_by_user_id($userId, true)
                );

                $userSessions = SessionManager::getSessionsFollowedByUser($userId);

                $sessionIdList = array_merge(
                    $sessionIdList,
                    array_column($userSessions, 'id')
                );
            }
            $courseCodeList = array_column($userCourses, 'code');
        }

        if (!empty($courseCodeList)) {
            $courseCodeList = array_unique($courseCodeList);
        }

        if (!empty($sessionIdList)) {
            $sessionIdList = array_unique($sessionIdList);
        }

        if (api_is_student_boss() && empty($userIdList)) {
            $count = 0;
            break;
        }

        if ('get_user_course_report' == $action) {
            $count = CourseManager::get_count_user_list_from_course_code(
                false,
                null,
                $courseCodeList,
                $userIdList,
                $sessionIdList,
                ['where' => $whereCondition, 'extra' => $extra_fields]
            );
        } else {
            $count = CourseManager::get_count_user_list_from_course_code(
                true,
                ['ruc'],
                $courseCodeList,
                $userIdList,
                $sessionIdList,
                ['where' => $whereCondition, 'extra' => $extra_fields]
            );
        }
        break;
    case 'get_course_exercise_medias':
        $course_id = api_get_course_int_id();
        $count = Question::get_count_course_medias($course_id);
        break;
    case 'get_user_skill_ranking':
        $skill = new SkillModel();
        $count = $skill->getUserListSkillRankingCount();
        break;
    case 'get_course_announcements':
        $count = AnnouncementManager::getNumberAnnouncements();
        break;
    case 'get_work_teacher':
        $count = getWorkListTeacher(0, $limit, null, null, $whereCondition, true);
        break;
    case 'get_work_student':
        $count = getWorkListStudent(0, $limit, null, null, $whereCondition, true);
        break;
    case 'get_all_work_student':
        $withResults = isset($_REQUEST['with_results']) ? (int) $_REQUEST['with_results'] : 0;
        $count = getAllWorkListStudent(0, $limit, null, null, $whereCondition, true, $withResults);
        break;
    case 'get_work_user_list_all':
        $work_id = $_REQUEST['work_id'];
        $count = get_count_work($work_id);
        break;
    case 'get_work_pending_list':
        $courseId = $_REQUEST['course'] ?? 0;
        $status = $_REQUEST['status'] ?? 0;
        $count = getAllWork(
            null,
            null,
            null,
            null,
            $whereCondition,
            true,
            $courseId,
            $status
        );
        break;
    case 'get_work_user_list_others':
        $work_id = $_REQUEST['work_id'];
        $count = get_count_work($work_id, api_get_user_id());
        break;
    case 'get_work_user_list':
        $work_id = $_REQUEST['work_id'];
        $courseInfo = api_get_course_info();
        $documents = getAllDocumentToWork($work_id, api_get_course_int_id());

        if ('1 = 1' === trim($whereCondition)) {
            $whereCondition = '';
        }

        if (empty($documents)) {
            $whereCondition .= " AND u.id = ".api_get_user_id();
            $count = get_work_user_list(
                0,
                $limit,
                null,
                null,
                $work_id,
                $whereCondition,
                null,
                true
            );
        } else {
            $count = get_work_user_list_from_documents(
                0,
                $limit,
                null,
                null,
                $work_id,
                api_get_user_id(),
                $whereCondition,
                true
            );
        }
        break;
    case 'get_work_student_list_overview':
        if (!(api_is_allowed_to_edit() || api_is_coach())) {
            return 0;
        }
        $workId = isset($_GET['work_id']) ? $_GET['work_id'] : null;
        $count = getWorkUserListData(
            $workId,
            api_get_course_id(),
            api_get_session_id(),
            api_get_group_id(),
            0,
            $limit,
            null,
            null,
            true
        );
        break;
    case 'get_exercise_pending_results':
        if (false === api_is_teacher()) {
            exit;
        }

        $courseId = $_REQUEST['course_id'] ?? 0;
        $exerciseId = $_REQUEST['exercise_id'] ?? 0;
        $status = $_REQUEST['status'] ?? 0;
        if (isset($_GET['filter_by_user']) && !empty($_GET['filter_by_user'])) {
            $filter_user = (int) $_GET['filter_by_user'];
            if (empty($whereCondition)) {
                $whereCondition .= " te.exe_user_id  = '$filter_user'";
            } else {
                $whereCondition .= " AND te.exe_user_id  = '$filter_user'";
            }
        }

        if (isset($_GET['group_id_in_toolbar']) && !empty($_GET['group_id_in_toolbar'])) {
            $groupIdFromToolbar = (int) $_GET['group_id_in_toolbar'];
            if (!empty($groupIdFromToolbar)) {
                if (empty($whereCondition)) {
                    $whereCondition .= " te.group_id  = '$groupIdFromToolbar'";
                } else {
                    $whereCondition .= " AND group_id  = '$groupIdFromToolbar'";
                }
            }
        }

        if (!empty($whereCondition)) {
            $whereCondition = " AND $whereCondition";
        }

        if (!empty($courseId)) {
            $whereCondition .= " AND te.c_id = $courseId";
        }

        $count = ExerciseLib::get_count_exam_results(
            $exerciseId,
            $whereCondition,
            '',
            false,
            true,
            $status
        );

        break;
    case 'get_exercise_results':
        $exercise_id = $_REQUEST['exerciseId'];

        $courseId = $_REQUEST['course_id'] ?? 0;
        $exerciseId = $_REQUEST['exercise_id'] ?? 0;
        $status = $_REQUEST['status'] ?? 0;
        if (isset($_GET['filter_by_user']) && !empty($_GET['filter_by_user'])) {
            $filter_user = (int) $_GET['filter_by_user'];
            if (empty($whereCondition)) {
                $whereCondition .= " te.exe_user_id  = '$filter_user'";
            } else {
                $whereCondition .= " AND te.exe_user_id  = '$filter_user'";
            }
        }

        if (isset($_GET['group_id_in_toolbar']) && !empty($_GET['group_id_in_toolbar'])) {
            $groupIdFromToolbar = (int) $_GET['group_id_in_toolbar'];
            if (!empty($groupIdFromToolbar)) {
                if (empty($whereCondition)) {
                    $whereCondition .= " te.group_id  = '$groupIdFromToolbar'";
                } else {
                    $whereCondition .= " AND group_id  = '$groupIdFromToolbar'";
                }
            }
        }

        if (!empty($whereCondition)) {
            $whereCondition = " AND $whereCondition";
        }

        $count = ExerciseLib::get_count_exam_results(
            $exercise_id,
            $whereCondition,
            api_get_course_int_id()
        );
        break;
    case 'get_exercise_results_report':
        api_protect_admin_script();
        $exerciseId = isset($_REQUEST['exercise_id']) ? $_REQUEST['exercise_id'] : 0;
        $courseId = isset($_REQUEST['course_id']) ? $_REQUEST['course_id'] : 0;

        if (empty($exerciseId)) {
            exit;
        }

        if (!empty($courseId)) {
            $courseInfo = api_get_course_info_by_id($courseId);
        } else {
            $courseId = isset($_REQUEST['cid']) ? $_REQUEST['cid'] : '';
            if (!empty($courseId)) {
                $courseInfo = api_get_course_info_by_id($courseId);
            }
        }

        if (empty($courseInfo)) {
            exit;
        }

        $startDate = Database::escape_string($_REQUEST['start_date']);
        if (!empty($whereCondition)) {
            $whereCondition = " AND $whereCondition";
        }
        $whereCondition .= " AND exe_date > '$startDate' AND te.status = '' ";
        $count = ExerciseLib::get_count_exam_results(
            $exerciseId,
            $whereCondition,
            $courseInfo['real_id'],
            true
        );
        break;
    case 'get_sessions_tracking':
        $keyword = isset($_REQUEST['keyword']) ? $_REQUEST['keyword'] : '';

        $description = '';
        $setting = api_get_setting('show_session_description');
        if ('true' === $setting) {
            $description = $keyword;
        }

        if (api_is_drh() || api_is_session_admin()) {
            $count = SessionManager::get_sessions_followed_by_drh(
                api_get_user_id(),
                null,
                null,
                true,
                false,
                false,
                null,
                $keyword,
                $description,
                ['where' => $whereCondition, 'extra' => $extra_fields]
            );
        } elseif (api_is_session_admin()) {
            $count = SessionManager::getSessionsFollowedByUser(
                api_get_user_id(),
                SESSIONADMIN,
                null,
                null,
                true,
                false,
                false,
                null,
                $keyword,
                $description,
                ['where' => $whereCondition, 'extra' => $extra_fields]
            );
        } else {
            // Sessions for the coach
            $count = Tracking::get_sessions_coached_by_user(
                api_get_user_id(),
                null,
                null,
                true,
                $keyword,
                $description,
                null,
                null,
                ['where' => $whereCondition, 'extra' => $extra_fields]
            );
        }
        break;
    case 'get_sessions':
        $listType = isset($_REQUEST['list_type']) ? $_REQUEST['list_type'] : 'simple';
        $language = isset($_REQUEST['lang']) ? $_REQUEST['lang'] : '';
        $order = isset($_REQUEST['order']) ? $_REQUEST['order'] : '';

        $sessionColumns = SessionManager::getGridColumns($listType);
        $columns = $sessionColumns['simple_column_name'];

        $loadExtraFields = isset($_REQUEST['load_extra_field']) ? $_REQUEST['load_extra_field'] : '';
        $extraFieldsToLoad = [];
        if (!empty($loadExtraFields)) {
            $loadExtraFields = explode(',', $loadExtraFields);
            foreach ($loadExtraFields as $fieldId) {
                $extraField = new ExtraField('session');
                $fieldData = $extraField->get($fieldId);
                $extraFieldsToLoad[] = $fieldData;
            }
        }

        if ('custom' === $listType && api_get_configuration_value('allow_session_status')) {
            $whereCondition .= ' AND (s.status IN ("'.SessionManager::STATUS_PLANNED.'", "'.SessionManager::STATUS_PROGRESS.'") ) ';
        }

        switch ($listType) {
            case 'complete':
                $count = SessionManager::get_count_admin_complete(
                    ['where' => $whereCondition, 'extra' => $extra_fields]
                );
                break;
            case 'custom':
            case 'simple':
                $count = SessionManager::getSessionsForAdmin(
                    api_get_user_id(),
                    ['where' => $whereCondition, 'extra' => $extra_fields],
                    true,
                    [],
                    $listType,
                    $extraFieldsToLoad,
                    $search
                );
                break;
            case 'active':
            case 'close':
            case 'all':
            default:
                $count = SessionManager::formatSessionsAdminForGrid(
                    ['where' => $whereCondition, 'extra' => $extra_fields],
                    true,
                    [],
                    [],
                    $listType
                );
                break;
        }
        break;
    case 'get_session_lp_progress':
    case 'get_session_progress':
        //@TODO replace this for a more efficient function (not retrieving the whole data)
        $course = api_get_course_info_by_id($_GET['course_id']);
        $users = CourseManager::get_student_list_from_course_code(
            $course['code'],
            true,
            $_GET['session_id'],
            $_GET['date_from'],
            $_GET['date_to']
        );
        $count = count($users);
        break;
    case 'get_exercise_progress':
        //@TODO replace this for a more efficient function (not retrieving the whole data)
        $records = Tracking::get_exercise_progress(
            $_GET['session_id'],
            $_GET['course_id'],
            $_GET['exercise_id'],
            $_GET['date_from'],
            $_GET['date_to']
        );
        $count = count($records);
        break;
    case 'get_session_access_overview':
        //@TODO replace this for a more efficient function (not retrieving the whole data)
        $records = SessionManager::get_user_data_access_tracking_overview(
            $_GET['session_id'],
            $_GET['course_id'],
            $_GET['student_id'],
            $_GET['profile'],
            $_GET['date_from'],
            $_GET['date_to'],
            $options
        );
        $count = count($records);
        break;
    case 'get_survey_overview':
        //@TODO replace this for a more efficient function (not retrieving the whole data)
        $records = SessionManager::get_survey_overview(
            $_GET['session_id'],
            $_GET['course_id'],
            $_GET['survey_id'],
            $_GET['date_from'],
            $_GET['date_to'],
            $options
        );
        $count = count($records);
        break;
    case 'get_exercise_grade':
        //@TODO replace this for a more efficient function (not retrieving the whole data)
        $course = api_get_course_info_by_id($_GET['course_id']);
        $users = CourseManager::get_student_list_from_course_code(
            $course['code'],
            true,
            $_GET['session_id']
        );

        $count = count($users);
        break;
    case 'get_extra_fields':
        $type = $_REQUEST['type'];
        $obj = new ExtraField($type);
        $count = $obj->get_count();
        break;
    case 'get_extra_field_options':
        $type = $_REQUEST['type'];
        $field_id = $_REQUEST['field_id'];
        $obj = new ExtraFieldOption($type);
        $count = $obj->get_count_by_field_id($field_id);
        break;
    case 'get_gradebooks':
        $obj = new Gradebook();
        $count = $obj->get_count();
        break;
    case 'get_careers':
        $obj = new Career();
        $count = $obj->get_count();
        break;
    case 'get_promotions':
        $obj = new Promotion();
        $count = $obj->get_count();
        break;
    case 'get_mail_template':
        $obj = new MailTemplateManager();
        $count = $obj->get_count();
        break;
    case 'get_grade_models':
        $obj = new GradeModel();
        $count = $obj->get_count();
        break;
    case 'get_usergroups':
        $obj = new UserGroupModel();
        $obj->protectScript();
        $count = $obj->get_count($whereCondition);
        break;
    case 'get_usergroups_teacher':
        $obj = new UserGroupModel();
        $obj->protectScript(null, false, true);
        $type = isset($_REQUEST['type']) ? $_REQUEST['type'] : 'registered';
        $groupFilter = isset($_REQUEST['group_filter']) ? (int) $_REQUEST['group_filter'] : 0;
        $keyword = isset($_REQUEST['keyword']) ? $_REQUEST['keyword'] : '';

        $course_id = api_get_course_int_id();
        $sessionId = api_get_session_id();
        $options = [];
        $options['course_id'] = $course_id;
        $options['session_id'] = $sessionId;

        switch ($type) {
            case 'not_registered':
                if (empty($sessionId)) {
                    $options['where'] = [' (course_id IS NULL OR course_id != ?) ' => $course_id];
                } else {
                    $options['where'] = [' (session_id IS NULL OR session_id != ?) ' => $sessionId];
                }
                if (!empty($keyword)) {
                    $options['where']['AND name like %?% '] = $keyword;
                }
                $count = $obj->getUserGroupNotInCourse(
                    $options,
                    $groupFilter,
                    true
                );
                break;
            case 'registered':
                if (empty($sessionId)) {
                    $options['where'] = [' usergroup.course_id = ? ' => $course_id];
                } else {
                    $options['where'] = [' usergroup.session_id = ? ' => $sessionId];
                }
                $count = $obj->getUserGroupInCourse(
                    $options,
                    $groupFilter,
                    true
                );
                break;
        }
        break;
    default:
        exit;
}

// 3. Calculating first, end, etc
$total_pages = 0;
if ($count > 0) {
    if (!empty($limit)) {
        $total_pages = ceil((float) $count / (float) $limit);
    }
}
if ($page > $total_pages) {
    $page = $total_pages;
}

$start = $limit * $page - $limit;
if ($start < 0) {
    $start = 0;
}

//4. Deleting an element if the user wants to
if (isset($_REQUEST['oper']) && 'del' == $_REQUEST['oper']) {
    $obj->delete($_REQUEST['id']);
}

$is_allowedToEdit = api_is_allowed_to_edit(null, true) || api_is_allowed_to_edit(true) || api_is_drh();

//5. Querying the DB for the elements
$columns = [];

switch ($action) {
    case 'get_exercise_categories':
        api_protect_course_script();
        if (!api_is_allowed_to_edit()) {
            api_not_allowed(true);
        }

        $columns = ['name', 'actions'];
        $qb = $repo->getResourcesByCourse(api_get_course_entity($courseId));
        $items = $qb->getQuery()->getResult();
        /** @var \Chamilo\CourseBundle\Entity\CExerciseCategory $item */
        $result = [];
        foreach ($items as $item) {
            $result[] = [
                'id' => $item->getId(),
                'name' => $item->getName(),
            ];
        }
        /*$result = $manager->get_all([
            'where' => ['c_id = ? ' => $courseId],
            'order' => "$sidx $sord",
            'LIMIT' => "$start , $limit",
        ]);*/
        break;
    case 'get_calendar_users':
        $columns = ['firstname', 'lastname', 'exam'];
        $result = $calendarPlugin->getUsersPerCalendar($id);
        break;
    case 'get_usergroups_users':
        $columns = ['name', 'actions'];
        if ('true' === api_get_plugin_setting('learning_calendar', 'enabled')) {
            $columns = [
                'name',
                'calendar',
                'gradebook_items',
                'time_spent',
                'lp_day_completed',
                'days_diff',
                'actions',
                'calendar_id',
            ];
        }
        $result = $usergroup->getUserGroupUsers($id, false, $start, $limit);
        break;
    case 'get_learning_path_calendars':
        $columns = ['title', 'total_hours', 'minutes_per_day', 'actions'];
        $sidx = in_array($sidx, $columns) ? $sidx : 'title';
        $result = $calendarPlugin->getCalendars(
            $start,
            $limit,
            $sidx,
            $sord
        );
        break;
    case 'course_log_events':
        $columns = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
        $sidx = in_array($sidx, $columns) ? $sidx : '0';
        $result = Statistics::getActivitiesData(
            $start,
            $limit,
            $sidx,
            $sord,
            $courseId,
            $sessionId
        );
        break;
    case 'get_programmed_announcements':
        $columns = ['subject', 'date', 'sent', 'actions'];
        $sessionId = isset($_REQUEST['session_id']) ? (int) $_REQUEST['session_id'] : 0;

        $sidx = in_array($sidx, $columns) ? $sidx : 'subject';
        $result = Database::select(
            '*',
            $object->table,
            [
                'where' => ['session_id = ? ' => $sessionId],
                'order' => "$sidx $sord",
                'LIMIT' => "$start , $limit",
            ]
        );
        if ($result) {
            foreach ($result as &$item) {
                $item['sent'] = 1 == $item['sent'] ? get_lang('Yes') : get_lang('No');
                $item['date'] = api_get_local_time($item['date']);
            }
        }
        break;
    case 'get_group_reporting':
        $columns = ['name', 'time', 'progress', 'score', 'works', 'messages', 'actions'];
        $sidx = in_array($sidx, $columns) ? $sidx : 'name';

        $result = Tracking::get_group_reporting(
            $course_id,
            $sessionId,
            $group_id,
            'all',
            $start,
            $limit,
            $sidx,
            $sord,
            $whereCondition
        );
        break;
    case 'get_course_exercise_medias':
        $columns = ['question'];
        $sidx = in_array($sidx, $columns) ? $sidx : 'question';
        $result = Question::get_course_medias(
            $course_id,
            $start,
            $limit,
            $sidx,
            $sord,
            $whereCondition
        );
        break;
    case 'get_user_course_report_resumed':
        $columns = [
            'extra_ruc',
            'training_hours',
            'count_users',
            'count_users_registered',
            'average_hours_per_user',
            'count_certificates',
        ];

        $column_names = [
            get_lang('Company'),
            get_lang('Hours of accumulated training'),
            get_lang('Subscriptions count'),
            get_lang('Users count'),
            get_lang('Avg hours/student'),
            get_lang('Certificates count'),
        ];

        $userExtraFields = UserManager::get_extra_fields(
            0,
            100,
            null,
            null,
            true,
            true
        );

        if (!empty($userExtraFields)) {
            foreach ($userExtraFields as $extra) {
                if ('ruc' == $extra['1']) {
                    continue;
                }
                $columns[] = $extra['1'];
                $column_names[] = $extra['3'];
            }
        }

        if (api_is_student_boss() && empty($userIdList)) {
            $result = [];
            break;
        }

        $result = CourseManager::get_user_list_from_course_code(
            null,
            null,
            "LIMIT $start, $limit",
            null,
            null,
            null,
            true,
            true,
            ['ruc'],
            $courseCodeList,
            $userIdList,
            null,
            $sessionIdList,
            null,
            ['where' => $whereCondition, 'extra' => $extra_fields]
        );

        $new_result = [];
        if (!empty($result)) {
            foreach ($result as $row) {
                $row['training_hours'] = api_time_to_hms($row['training_hours']);
                $row['average_hours_per_user'] = api_time_to_hms($row['average_hours_per_user']);
                $new_result[] = $row;
            }
            $result = $new_result;
        }
        break;
    case 'get_user_course_report':
        $columns = [
            'course',
            'user',
            'email',
            'time',
            'certificate',
            'progress_100',
            'progress',
        ];
        $column_names = [
            get_lang('Course'),
            get_lang('User'),
            get_lang('e-mail'),
            get_lang('Man hours'),
            get_lang('Generated certificate'),
            get_lang('Approved'),
            get_lang('Course progress'),
        ];

        $userExtraFields = UserManager::get_extra_fields(
            0,
            100,
            null,
            null,
            true,
            true
        );
        if (!empty($userExtraFields)) {
            foreach ($userExtraFields as $extra) {
                $columns[] = $extra['1'];
                $column_names[] = $extra['3'];
            }
        }

        if (api_is_student_boss()) {
            $columns[] = 'group';
            $column_names[] = get_lang('Group');
        }

        if (!in_array($sidx, ['title'])) {
            $sidx = 'title';
        }

        if (api_is_student_boss() && empty($userIdList)) {
            $result = [];
            break;
        }

        //get sessions
        $sessions = [];
        if (count($sessionIdList) > 0) {
            $sessions = CourseManager::get_user_list_from_course_code(
                null,
                null,
                "LIMIT $start, $limit",
                " $sidx $sord",
                null,
                null,
                true,
                false,
                null,
                $courseCodeList,
                $userIdList,
                null,
                $sessionIdList,
                null,
                ['where' => $whereCondition, 'extra' => $extra_fields]
            );
        }

        //get courses
        $courses = CourseManager::get_user_list_from_course_code(
            null,
            null,
            "LIMIT $start, $limit",
            " $sidx $sord",
            null,
            null,
            true,
            false,
            null,
            [],
            $userIdList,
            null,
            null,
            null,
            ['where' => $whereCondition, 'extra' => $extra_fields]
        );

        //merge courses and sessions
        $result = array_merge($sessions, $courses);

        if (api_is_student_boss()) {
            $userGroup = new UserGroupModel();
            foreach ($result as &$item) {
                $userGroups = $userGroup->get_groups_by_user($item['user_id']);
                $item['group'] = implode(", ", array_column($userGroups, 'name'));
                unset($item['user_id']);
            }
        }

        break;
    case 'get_user_skill_ranking':
        $columns = [
            'photo',
            'firstname',
            'lastname',
            'skills_acquired',
            'currently_learning',
            'rank',
        ];
        if ('1 = 1' === trim($whereCondition)) {
            $whereCondition = '';
        }
        $sidx = in_array($sidx, $columns) ? $sidx : 'firstname';
        $result = $skill->getUserListSkillRanking(
            $start,
            $limit,
            $sidx,
            $sord,
            $whereCondition
        );
        $result = msort($result, 'skills_acquired', 'asc');

        $skills_in_course = [];
        if (!empty($result)) {
            foreach ($result as &$item) {
                $user_info = api_get_user_info($item['user_id']);
                $personal_course_list = UserManager::get_personal_session_course_list(
                    $item['user_id']
                );
                $count_skill_by_course = [];
                foreach ($personal_course_list as $course_item) {
                    if (!isset($skills_in_course[$course_item['code']])) {
                        $count_skill_by_course[$course_item['code']] = $skill->getCountSkillsByCourse(
                            $course_item['code']
                        );
                        $skills_in_course[$course_item['code']] = $count_skill_by_course[$course_item['code']];
                    } else {
                        $count_skill_by_course[$course_item['code']] = $skills_in_course[$course_item['code']];
                    }
                }
                $item['photo'] = Display::img($user_info['avatar_small'], $user_info['complete_name'], [], false);
                $item['currently_learning'] = !empty($count_skill_by_course) ? array_sum($count_skill_by_course) : 0;
            }
        }
        break;
    case 'get_course_announcements':
        $columns = [
            'title',
            'username',
            'insert_date',
            'actions',
        ];

        $titleToSearch = $_REQUEST['title_to_search'] ?? '';
        $userIdToSearch = $_REQUEST['user_id_to_search'] ?? 0;

        $result = AnnouncementManager::getAnnouncements(
            null,
            null,
            false,
            $start,
            $limit,
            $sidx,
            $sord,
            $titleToSearch,
            $userIdToSearch
        );

        break;
    case 'get_work_teacher':
        $columns = [
            'type',
            'title',
            'sent_date',
            'expires_on',
            'amount',
            'actions',
        ];
        $sidx = in_array($sidx, $columns) ? $sidx : 'title';
        $result = getWorkListTeacher(
            $start,
            $limit,
            $sidx,
            $sord,
            $whereCondition
        );
        break;
    case 'get_work_student':
        $columns = [
            'type',
            'title',
            'expires_on',
            'feedback',
            'last_upload',
            'others',
        ];
        $sidx = in_array($sidx, $columns) ? $sidx : 'title';
        $result = getWorkListStudent(
            $start,
            $limit,
            $sidx,
            $sord,
            $whereCondition
        );
        break;
    case 'get_all_work_student':
        $columns = [
            'type',
            'title',
            'expires_on',
        ];

        if ($withResults) {
            $columns[] = 'feedback';
            $columns[] = 'last_upload';
        }
        $sidx = in_array($sidx, $columns) ? $sidx : 'title';
        $result = getAllWorkListStudent(
            $start,
            $limit,
            $sidx,
            $sord,
            $whereCondition,
            false,
            $withResults
        );
        break;
    case 'get_work_user_list_all':
        $plagiarismColumns = [];
        if (api_get_configuration_value('allow_compilatio_tool')) {
            $plagiarismColumns = ['compilatio'];
        }
        if (isset($_GET['type']) && 'simple' === $_GET['type']) {
            $columns = [
                'fullname',
                'title',
                'qualification',
                'sent_date',
                'qualificator_id',
                'correction',
            ];
            $columns = array_merge($columns, $plagiarismColumns);
            $columns[] = 'actions';
        } else {
            $columns = [
                'fullname',
                'title',
                'qualification',
                'sent_date',
                'correction',
            ];
            $columns = array_merge($columns, $plagiarismColumns);
            $columns[] = 'actions';
        }

        $whereCondition = " AND $whereCondition ";
        $columnOrderValidList = array_merge(['firstname', 'lastname'], $columns);
        $sidx = in_array($sidx, $columnOrderValidList) ? $sidx : 'title';

        $result = get_work_user_list(
            $start,
            $limit,
            $sidx,
            $sord,
            $work_id,
            $whereCondition
        );
        break;
    case 'get_work_pending_list':
        api_block_anonymous_users();
        if (false === api_is_teacher()) {
            exit;
        }
        $plagiarismColumns = [];
        if (api_get_configuration_value('allow_compilatio_tool')) {
            $plagiarismColumns = ['compilatio'];
        }
        $columns = [
            'course',
            'work_name',
            'fullname',
            'title',
            'qualification',
            'sent_date',
            'qualificator_id',
            'correction',
        ];
        $columns = array_merge($columns, $plagiarismColumns);
        $columns[] = 'actions';
        $sidx = in_array($sidx, $columns) ? $sidx : 'work_name';
        $result = getAllWork(
            $start,
            $limit,
            $sidx,
            $sord,
            $whereCondition,
            false,
            $courseId,
            $status
        );
        break;
    case 'get_work_user_list_others':
        $plagiarismColumns = [];
        if (api_get_configuration_value('allow_compilatio_tool')) {
            $plagiarismColumns = ['compilatio'];
        }

        if (isset($_GET['type']) && 'simple' === $_GET['type']) {
            $columns = [
                'type',
                'firstname',
                'lastname',
                'title',
                'qualification',
                'sent_date',
                'qualificator_id',
            ];
            $columns = array_merge($columns, $plagiarismColumns);
            $columns[] = 'actions';
        } else {
            $columns = ['type', 'firstname', 'lastname', 'title', 'sent_date'];
            $columns = array_merge($columns, $plagiarismColumns);
            $columns[] = 'actions';
        }

        if ('1 = 1' === trim($whereCondition)) {
            $whereCondition = '';
        }

        $whereCondition .= " AND u.id <> ".api_get_user_id();

        $sidx = in_array($sidx, $columns) ? $sidx : 'firstname';
        $result = get_work_user_list(
            $start,
            $limit,
            $sidx,
            $sord,
            $work_id,
            $whereCondition
        );
        break;
    case 'get_work_user_list':
        $plagiarismColumns = [];
        if (api_get_configuration_value('allow_compilatio_tool') && api_is_allowed_to_edit()) {
            $plagiarismColumns = ['compilatio'];
        }
        if (isset($_GET['type']) && 'simple' == $_GET['type']) {
            $columns = [
                'type',
                'title',
                'qualification',
                'sent_date',
                'qualificator_id',
            ];
            $columns = array_merge($columns, $plagiarismColumns);
            $columns[] = 'actions';
        } else {
            $columns = ['type', 'title', 'qualification', 'sent_date'];
            $columns = array_merge($columns, $plagiarismColumns);
            $columns[] = 'actions';
        }
        $documents = getAllDocumentToWork($work_id, api_get_course_int_id());

        if ('1 = 1' === trim($whereCondition)) {
            $whereCondition = '';
        }

        $sidx = in_array($sidx, $columns) ? $sidx : 'title';
        if (empty($documents)) {
            $whereCondition .= ' AND u.id = '.api_get_user_id();
            $result = get_work_user_list(
                $start,
                $limit,
                $sidx,
                $sord,
                $work_id,
                $whereCondition
            );
        } else {
            $result = get_work_user_list_from_documents(
                $start,
                $limit,
                $sidx,
                $sord,
                $work_id,
                api_get_user_id(),
                $whereCondition
            );
        }
        break;
    case 'get_exercise_pending_results':
        $columns = [
            'course',
            'exercise',
            'firstname',
            'lastname',
            'username',
            'exe_duration',
            'start_date',
            'exe_date',
            'score',
            'user_ip',
            'status',
            'actions',
        ];
        $officialCodeInList = api_get_setting('show_official_code_exercise_result_list');
        if ('true' === $officialCodeInList) {
            $columns = array_merge(['official_code'], $columns);
        }

        $sidx = in_array($sidx, $columns) ? $sidx : 'course';

        $result = ExerciseLib::get_exam_results_data(
            $start,
            $limit,
            $sidx,
            $sord,
            $exerciseId,
            $whereCondition,
            false,
            null,
            false,
            false,
            [],
            false,
            false,
            false,
            true,
            $status
        );

        break;
    case 'get_exercise_results':
        $is_allowedToEdit = api_is_allowed_to_edit(null, true) ||
            api_is_drh() ||
            api_is_student_boss() ||
            api_is_session_admin();
        if ($is_allowedToEdit || api_is_student_boss()) {
            $columns = [
                'firstname',
                'lastname',
                'username',
                'group_name',
                'exe_duration',
                'start_date',
                'exe_date',
                'score',
                'user_ip',
                'status',
                'lp',
                'actions',
            ];
            $officialCodeInList = api_get_setting('show_official_code_exercise_result_list');
            if ('true' === $officialCodeInList) {
                $columns = array_merge(['official_code'], $columns);
            }
        }

        $sidx = in_array($sidx, $columns) ? $sidx : 'firstname';
        $result = ExerciseLib::get_exam_results_data(
            $start,
            $limit,
            $sidx,
            $sord,
            $exercise_id,
            $whereCondition,
            false,
            api_get_course_int_id()
        );
        break;
    case 'get_exercise_results_report':
        $columns = [
            'firstname',
            'lastname',
            'username',
        ];
        $extraFieldsToAdd = [];
        $extraFields = api_get_configuration_value('exercise_category_report_user_extra_fields');
        $roundValues = api_get_configuration_value('exercise_category_round_score_in_export');

        if (!empty($extraFields) && isset($extraFields['fields'])) {
            $extraField = new ExtraField('user');
            foreach ($extraFields['fields'] as $variable) {
                $info = $extraField->get_handler_field_info_by_field_variable($variable);
                if ($info) {
                    $extraFieldsToAdd[] = $variable;
                }
            }
        }
        if (!empty($extraFieldsToAdd)) {
            $columns = array_merge($columns, $extraFieldsToAdd);
        }

        $columns[] = 'session';
        $columns[] = 'session_access_start_date';
        $columns[] = 'exe_date';
        $columns[] = 'score';

        if ('excel' === $operation) {
            $columns = [
                'firstname',
                'lastname',
                'username',
            ];

            if (!empty($extraFieldsToAdd)) {
                $columns = array_merge($columns, $extraFieldsToAdd);
            }

            $columns[] = 'session';
            $columns[] = 'session_access_start_date';
            $columns[] = 'exe_date';
            $columns[] = 'score_percentage';
            $columns[] = 'only_score';
            $columns[] = 'total';

            $overwriteColumnHeaderExport['session_access_start_date'] = get_lang('Access start date');
            $overwriteColumnHeaderExport['exe_date'] = get_lang('Start Date');
            $overwriteColumnHeaderExport['score_percentage'] = get_lang('Score').' - '.get_lang('Percentage');
            $overwriteColumnHeaderExport['only_score'] = get_lang('Score').' - '.get_lang('Note');
            $overwriteColumnHeaderExport['total'] = get_lang('Score').' - '.get_lang('Test');
        }
        $categoryList = TestCategory::getListOfCategoriesIDForTest($exerciseId, $courseId);

        if (!empty($categoryList)) {
            foreach ($categoryList as $categoryInfo) {
                $label = 'category_'.$categoryInfo['id'];
                if ('excel' === $operation) {
                    $columns[] = $label.'_score_percentage';
                    $columns[] = $label.'_only_score';
                    $columns[] = $label.'_total';
                    $overwriteColumnHeaderExport[$label] = $categoryInfo['title'];
                    $overwriteColumnHeaderExport[$label.'_score_percentage'] = $categoryInfo['title'].
                        ' - '.get_lang('Percentage');
                    $overwriteColumnHeaderExport[$label.'_only_score'] = $categoryInfo['title'].
                        ' - '.get_lang('Note');
                    $overwriteColumnHeaderExport[$label.'_total'] = $categoryInfo['title'].
                        ' - '.get_lang('Test');
                } else {
                    $columns[] = $label;
                }
            }
        }

        if ('excel' !== $operation) {
            $columns[] = 'actions';
        }

        $whereCondition .= " AND te.status = '' ";

        $sidx = in_array($sidx, $columns) ? $sidx : 'firstname';
        $result = ExerciseLib::get_exam_results_data(
            $start,
            $limit,
            $sidx,
            $sord,
            $exerciseId,
            $whereCondition,
            false,
            $courseInfo['real_id'],
            true,
            true,
            $extraFieldsToAdd,
            true,
            $roundValues
        );
        break;
    case 'get_work_student_list_overview':
        if (!(api_is_allowed_to_edit() || api_is_coach())) {
            return [];
        }
        $columns = [
            'student',
            'works',
        ];

        $sidx = in_array($sidx, $columns) ? $sidx : 'student';
        $result = getWorkUserListData(
            $workId,
            api_get_course_id(),
            api_get_session_id(),
            api_get_group_id(),
            $start,
            $limit,
            $sidx,
            $sord
        );
        break;
    case 'get_sessions_tracking':
        $sessionColumns = SessionManager::getGridColumns('my_space');
        $columns = $sessionColumns['simple_column_name'];

        if (api_is_drh()) {
            $orderByName = Database::escape_string($sidx);
            $orderByName = in_array($orderByName, ['name', 'access_start_date']) ? $orderByName : 'name';
            $orderBy = " ORDER BY $orderByName $sord";
            $sessions = SessionManager::get_sessions_followed_by_drh(
                api_get_user_id(),
                $start,
                $limit,
                false,
                false,
                false,
                $orderBy,
                $keyword,
                $description,
                ['where' => $whereCondition, 'extra' => $extra_fields]
            );
        } elseif (api_is_session_admin()) {
            $orderByName = Database::escape_string($sidx);
            $orderByName = in_array($orderByName, ['name', 'access_start_date']) ? $orderByName : 'name';
            $orderBy = " ORDER BY $orderByName $sord";
            $sessions = SessionManager::getSessionsFollowedByUser(
                api_get_user_id(),
                SESSIONADMIN,
                $start,
                $limit,
                false,
                false,
                false,
                $orderBy,
                $keyword,
                $description,
                ['where' => $whereCondition, 'extra' => $extra_fields]
            );
        } else {
            $sidx = in_array($sidx, $columns) ? $sidx : 'name';
            // Sessions for the coach
            $sessions = Tracking::get_sessions_coached_by_user(
                api_get_user_id(),
                $start,
                $limit,
                false,
                $keyword,
                $description,
                $sidx,
                $sord,
                ['where' => $whereCondition, 'extra' => $extra_fields]
            );
        }

        $result = [];
        if (!empty($sessions)) {
            $pdfIcon = Display::return_icon('pdf.png', get_lang('CertificateOfAchievement'), [], ICON_SIZE_SMALL);
            foreach ($sessions as $session) {
                $sessionEntity = api_get_session_entity($session['id']);
                if (api_drh_can_access_all_session_content()) {
                    $count_courses_in_session = SessionManager::get_course_list_by_session_id(
                        $session['id'],
                        '',
                        null,
                        true
                    );
                } else {
                    $count_courses_in_session = count(
                        Tracking::get_courses_followed_by_coach(
                            api_get_user_id(),
                            $session['id']
                        )
                    );
                }

                $count_users_in_session = SessionManager::get_users_by_session(
                    $session['id'],
                    0,
                    true
                );

                $dateData = SessionManager::parseSessionDates($sessionEntity, true);
                $dateToString = $dateData['access'];

                $detailButtons = [];
                $detailButtons[] = Display::url(
                    $pdfIcon,
                    api_get_path(WEB_CODE_PATH).'my_space/session.php?'
                    .http_build_query(
                        [
                            'action' => 'export_to_pdf',
                            'type' => 'achievement',
                            'session_to_export' => $session['id'],
                            'all_students' => 1,
                        ]
                    ),
                    ['target' => '_blank']
                );
                $detailButtons[] = Display::url(
                    Display::return_icon('works.png', get_lang('WorksReport')),
                    api_get_path(WEB_CODE_PATH).'my_space/works_in_session_report.php?session='.$session['id']
                );
                $detailButtons[] = Display::url(
                    Display::return_icon('2rightarrow.png'),
                    api_get_path(WEB_CODE_PATH).'my_space/course.php?sid='.$session['id']
                );

                $item = [
                    'name' => Display::url(
                        $session['name'],
                        api_get_path(WEB_CODE_PATH).'my_space/course.php?sid='.$session['id']
                    ),
                    'date' => $dateToString,
                    'course_per_session' => $count_courses_in_session,
                    'student_per_session' => $count_users_in_session,
                    'actions' => implode(' ', $detailButtons),
                ];

                if (!empty($extra_fields)) {
                    foreach ($extra_fields as $extraField) {
                        $item[$extraField['field']] = $extraField['data'];
                    }
                }
                $result[] = $item;
            }
        }
        break;
    case 'get_sessions':
        $sessionColumns = SessionManager::getGridColumns($listType);
        $columns = $sessionColumns['simple_column_name'];

        $loadExtraFields = isset($_REQUEST['load_extra_field']) ? $_REQUEST['load_extra_field'] : '';
        $extraFieldsToLoad = [];
        if (!empty($loadExtraFields)) {
            $loadExtraFields = explode(',', $loadExtraFields);
            foreach ($loadExtraFields as $fieldId) {
                $extraField = new ExtraField('session');
                $fieldData = $extraField->get($fieldId);
                $extraFieldsToLoad[] = $fieldData;
            }
        }

        $sidx = in_array($sidx, $columns) ? $sidx : 'name';
        switch ($listType) {
            case 'complete':
                $result = SessionManager::get_sessions_admin_complete(
                    [
                        'where' => $whereCondition,
                        'order' => "$sidx $sord, s.name",
                        'extra' => $extra_fields,
                        'limit' => "$start , $limit",
                    ]
                );
                break;
            case 'custom':
            case 'simple':
                $result = SessionManager::getSessionsForAdmin(
                    api_get_user_id(),
                    [
                        'where' => $whereCondition,
                        'order' => "$sidx $sord, s.name",
                        'extra' => $extra_fields,
                        'limit' => "$start , $limit",
                    ],
                    false,
                    $sessionColumns,
                    $listType,
                    $extraFieldsToLoad,
                    $search
                );
                break;
            case 'active':
            case 'close':
            case 'all':
                $result = SessionManager::formatSessionsAdminForGrid(
                    [
                        'where' => $whereCondition,
                        'order' => "s.access_start_date, s.name",
                        'extra' => $extra_fields,
                        'limit' => "$start , $limit",
                    ],
                    false,
                    $sessionColumns,
                    $extraFieldsToLoad,
                    $listType
                );
                break;
        }
        break;
    case 'get_exercise_progress':
        $sessionId = (int) $_GET['session_id'];
        $courseId = (int) $_GET['course_id'];
        $exerciseId = (int) $_GET['exercise_id'];
        $date_from = $_GET['date_from'];
        $date_to = $_GET['date_to'];

        $columns = [
            'session',
            'exercise_id',
            'quiz_title',
            'username',
            'lastname',
            'firstname',
            'time',
            'question_id',
            'question',
            'description',
            'answer',
            'correct',
        ];
        $sidx = in_array($sidx, $columns) ? $sidx : 'quiz_title';

        $result = Tracking::get_exercise_progress(
            $sessionId,
            $courseId,
            $exerciseId,
            $date_from,
            $date_to,
            [
                'where' => $whereCondition,
                'order' => "$sidx $sord",
                'limit' => "$start , $limit",
            ]
        );
        break;
    case 'get_session_lp_progress':
        $sessionId = 0;
        if (!empty($_GET['session_id']) && !empty($_GET['course_id'])) {
            $sessionId = (int) $_GET['session_id'];
            $courseId = (int) $_GET['course_id'];
            $course = api_get_course_info_by_id($courseId);
        }

        /**
         * Add lessons of course.
         */
        $columns = [
            'username',
            'firstname',
            'lastname',
        ];
        $lessons = LearnpathList::get_course_lessons($course['code'], $sessionId);
        foreach ($lessons as $lesson_id => $lesson) {
            $columns[] = $lesson_id;
        }
        $columns[] = 'total';
        $sidx = in_array($sidx, $columns) ? $sidx : 'username';
        $result = SessionManager::get_session_lp_progress(
            $sessionId,
            $courseId,
            $date_from,
            $date_to,
            [
                'where' => $whereCondition,
                'order' => "$sidx $sord",
                'limit' => "$start , $limit",
            ]
        );
        break;
    case 'get_survey_overview':
        $sessionId = 0;
        if (!empty($_GET['session_id']) &&
            !empty($_GET['course_id']) &&
            !empty($_GET['survey_id'])
        ) {
            $sessionId = (int) $_GET['session_id'];
            $courseId = (int) $_GET['course_id'];
            $surveyId = (int) $_GET['survey_id'];
            $date_from = $_GET['date_from'];
            $date_to = $_GET['date_to'];
        }
        /**
         * Add lessons of course.
         */
        $columns = [
            'username',
            'firstname',
            'lastname',
        ];

        $questions = SurveyManager::get_questions($surveyId, $courseId);

        foreach ($questions as $question_id => $question) {
            $columns[] = $question_id;
        }

        $sidx = in_array($sidx, $columns) ? $sidx : 'username';
        $result = SessionManager::get_survey_overview(
            $sessionId,
            $courseId,
            $surveyId,
            $date_from,
            $date_to,
            [
                'where' => $whereCondition,
                'order' => "$sidx $sord",
                'limit' => "$start , $limit",
            ]
        );
        break;
    case 'get_session_progress':
        $columns = [
            'lastname',
            'firstname',
            'username',
            //'profile',
            'total',
            'courses',
            'lessons',
            'exercises',
            'forums',
            'homeworks',
            'wikis',
            'surveys',
            //exercises
            'lessons_total',
            'lessons_done',
            'lessons_left',
            'lessons_progress',
            //exercises
            'exercises_total',
            'exercises_done',
            'exercises_left',
            'exercises_progress',
            //forums
            'forums_total',
            'forums_done',
            'forums_left',
            'forums_progress',
            //assignments
            'assignments_total',
            'assignments_done',
            'assignments_left',
            'assignments_progress',
            //Wiki
            'wiki_total',
            'wiki_revisions',
            'wiki_read',
            'wiki_unread',
            'wiki_progress',
            //surveys
            'surveys_total',
            'surveys_done',
            'surveys_left',
            'surveys_progress',
        ];
        $sessionId = 0;
        if (!empty($_GET['course_id']) && !empty($_GET['session_id'])) {
            $sessionId = intval($_GET['session_id']);
            $courseId = intval($_GET['course_id']);
        }
        $sidx = in_array($sidx, $columns) ? $sidx : 'username';
        $result = SessionManager::get_session_progress(
            $sessionId,
            $courseId,
            null,
            null,
            [
                'where' => $whereCondition,
                'order' => "$sidx $sord",
                'limit' => "$start , $limit",
            ]
        );
        break;
    case 'get_session_access_overview':
        $columns = [
            'logindate',
            'username',
            'lastname',
            'firstname',
            'clicks',
            'ip',
            'timeLoggedIn',
            'session',
        ];
        $sessionId = 0;
        if (!empty($_GET['course_id']) && !empty($_GET['session_id'])) {
            $sessionId = intval($_GET['session_id']);
            $courseId = intval($_GET['course_id']);
            $studentId = intval($_GET['student_id']);
            $profile = intval($_GET['profile']);
            $date_from = intval($_GET['date_from']);
            $date_to = intval($_GET['date_to']);
        }
        $sidx = in_array($sidx, $columns) ? $sidx : 'logindate';

        $result = SessionManager::get_user_data_access_tracking_overview(
            $sessionId,
            $courseId,
            $studentId,
            $profile,
            $date_to,
            $date_from,
            [
                'where' => $whereCondition,
                'order' => "$sidx $sord",
                'limit' => "$start , $limit",
            ]
        );
        break;
    case 'get_gradebooks':
        $columns = ['name', 'certificates', 'skills', 'actions', 'has_certificates'];
        if (!in_array($sidx, $columns)) {
            $sidx = 'name';
        }
        $result = Database::select(
            '*',
            $obj->table,
            ['order' => "$sidx $sord", 'LIMIT' => "$start , $limit"]
        );
        $new_result = [];
        foreach ($result as $item) {
            if (0 != $item['parent_id']) {
                continue;
            }
            $skills = $obj->getSkillsByGradebook($item['id']);
            $courseId = $item['c_id'];
            $courseInfo = api_get_course_info_by_id($courseId);

            //Fixes bug when gradebook doesn't have names
            if (empty($item['name'])) {
                $item['name'] = $courseInfo['code'];
            }

            $item['name'] = Display::url(
                $item['name'],
                api_get_path(WEB_CODE_PATH).'gradebook/index.php?sid=0&cid='.$courseInfo['real_id']
            );

            if (!empty($item['certif_min_score']) && !empty($item['document_id'])) {
                $item['certificates'] = Display::return_icon(
                    'accept.png',
                    get_lang('With Certificate'),
                    [],
                    ICON_SIZE_SMALL
                );
                $item['has_certificates'] = '1';
            } else {
                $item['certificates'] = Display::return_icon(
                    'warning.png',
                    get_lang('No certificate'),
                    [],
                    ICON_SIZE_SMALL
                );
                $item['has_certificates'] = '0';
            }

            if (!empty($skills)) {
                $item['skills'] = '';
                foreach ($skills as $skill) {
                    $item['skills'] .= Display::span($skill['name'], ['class' => 'label_tag skill']);
                }
            }
            $new_result[] = $item;
        }
        $result = $new_result;
        break;
    case 'get_careers':
        $columns = ['name', 'description', 'actions'];
        if (!in_array($sidx, $columns)) {
            $sidx = 'name';
        }
        $result = Database::select(
            '*',
            $obj->table,
            ['order' => "$sidx $sord", 'LIMIT' => "$start , $limit"]
        );
        $new_result = [];
        foreach ($result as $item) {
            if (!$item['status']) {
                $item['name'] = '<font style="color:#AAA">'.$item['name'].'</font>';
            }
            $new_result[] = $item;
        }
        $result = $new_result;
        break;
    case 'get_promotions':
        $columns = ['name', 'career', 'description', 'actions'];
        if (!in_array($sidx, $columns)) {
            $sidx = 'name';
        }

        $result = Database::select(
            'p.id,p.name, p.description, c.name as career, p.status',
            "$obj->table p LEFT JOIN ".Database::get_main_table(TABLE_CAREER)." c  ON c.id = p.career_id ",
            ['order' => "$sidx $sord", 'LIMIT' => "$start , $limit"]
        );

        $new_result = [];
        foreach ($result as $item) {
            if (!$item['status']) {
                $item['name'] = '<font style="color:#AAA">'.$item['name'].'</font>';
            }
            $new_result[] = $item;
        }
        $result = $new_result;
        break;
    case 'get_mail_template':
        $columns = ['name', 'type', 'default_template', 'actions'];
        if (!in_array($sidx, $columns)) {
            $sidx = 'name';
        }

        if (!in_array($sidx, $columns)) {
            $sidx = 'name';
        }

        $result = Database::select(
            '*',
            $obj->table,
            [
                'where' => ['url_id = ? ' => api_get_current_access_url_id()],
                'order' => "$sidx $sord",
                'LIMIT' => "$start , $limit",
            ]
        );

        $new_result = [];
        foreach ($result as $item) {
            $new_result[] = $item;
        }
        $result = $new_result;
        break;
    case 'get_grade_models':
        $columns = ['name', 'description', 'actions'];
        if (!in_array($sidx, $columns)) {
            $sidx = 'name';
        }
        $result = Database::select(
            '*',
            "$obj->table ",
            ['order' => "$sidx $sord", 'LIMIT' => "$start , $limit"]
        );
        $new_result = [];
        foreach ($result as $item) {
            $new_result[] = $item;
        }
        $result = $new_result;
        break;
    case 'get_usergroups':
        $obj->protectScript();
        $columns = ['name', 'users', 'courses', 'sessions', 'group_type', 'actions'];
        $sidx = in_array($sidx, $columns) ? $sidx : 'name';
        $result = $obj->getUsergroupsPagination($sidx, $sord, $start, $limit, $whereCondition);
        break;
    case 'get_extra_fields':
        $obj = new ExtraField($type);
        $columns = [
            'display_text',
            'variable',
            'value_type',
            'changeable',
            'visible_to_self',
            'visible_to_others',
            'filter',
            'field_order',
        ];
        $sidx = in_array($sidx, $columns) ? $sidx : 'display_text';
        $result = $obj->getAllGrid($sidx, $sord, $start, $limit);
        $new_result = [];
        if (!empty($result)) {
            $checkIcon = Display::return_icon(
                'check-circle.png',
                get_lang('Yes')
            );
            $timesIcon = Display::return_icon(
                'closed-circle.png',
                get_lang('No')
            );
            foreach ($result as $item) {
                $item['display_text'] = $item['displayText'];
                $item['value_type'] = $obj->get_field_type_by_id($item['valueType']);
                $item['changeable'] = $item['changeable'] ? $checkIcon : $timesIcon;
                $item['visible_to_self'] = $item['visibleToSelf'] ? $checkIcon : $timesIcon;
                $item['visible_to_others'] = $item['visibleToOthers'] ? $checkIcon : $timesIcon;
                $item['filter'] = $item['filter'] ? $checkIcon : $timesIcon;
                $new_result[] = $item;
            }
            $result = $new_result;
        }
        break;
    case 'get_exercise_grade':
        $objExercise = new Exercise();
        $exercises = $objExercise->getExercisesByCourseSession($_GET['course_id'], $_GET['session_id']);
        $cntExer = 4;
        if (!empty($exercises)) {
            $cntExer += count($exercises);
        }

        $columns = [];
        //Get dynamic column names
        $i = 1;
        $column_names = [];
        foreach (range(1, $cntExer) as $cnt) {
            switch ($cnt) {
                case 1:
                    $columns[] = 'session';
                    $column_names[] = get_lang('Section');
                    break;
                case 2:
                    $columns[] = 'username';
                    $column_names[] = get_lang('Username');
                    break;
                case 3:
                    $columns[] = 'name';
                    $column_names[] = get_lang('Name');
                    break;
                case $cntExer:
                    $columns[] = 'finalScore';
                    $column_names[] = get_lang('Final score');
                    break;
                default:
                    $title = '';
                    if (!empty($exercises[$cnt - 4]['title'])) {
                        $title = ucwords(strtolower(trim($exercises[$cnt - 4]['title'])));
                    }
                    $columns[] = 'exer'.$i;
                    $column_names[] = $title;
                    $i++;
                    break;
            }
        }

        $quizIds = [];
        if (!empty($exercises)) {
            foreach ($exercises as $exercise) {
                $quizIds[] = $exercise['id'];
            }
        }

        $course = api_get_course_info_by_id($_GET['course_id']);
        $listUserSess = CourseManager::get_student_list_from_course_code(
            $course['code'],
            true,
            $_GET['session_id']
        );

        $usersId = array_keys($listUserSess);
        $users = UserManager::get_user_list_by_ids(
            $usersId,
            null,
            "lastname, firstname",
            "$start , $limit"
        );
        $exeResults = $objExercise->getExerciseAndResult(
            $_GET['course_id'],
            $_GET['session_id'],
            $quizIds
        );

        $arrGrade = [];
        foreach ($exeResults as $exeResult) {
            $arrGrade[$exeResult['exe_user_id']][$exeResult['exe_exo_id']] = $exeResult['score'];
        }

        $result = [];
        $i = 0;
        foreach ($users as $user) {
            $sessionInfo = SessionManager::fetch($listUserSess[$user['user_id']]['id_session']);
            $result[$i]['session'] = $sessionInfo['name'];
            $result[$i]['username'] = $user['username'];
            $result[$i]['name'] = $user['lastname']." ".$user['firstname'];
            $j = 1;
            $finalScore = 0;
            foreach ($quizIds as $quizID) {
                $grade = '';
                if (!empty($arrGrade[$user['user_id']][$quizID]) || 0 == $arrGrade[$user['user_id']][$quizID]) {
                    $finalScore += $grade = $arrGrade[$user['user_id']][$quizID];
                }
                $result[$i]['exer'.$j] = $grade;
                $j++;
            }

            if ($finalScore > 20) {
                $finalScore = 20;
            }

            $result[$i]['finalScore'] = number_format($finalScore, 2);

            $i++;
        }
        break;
    case 'get_extra_field_options':
        $obj = new ExtraFieldOption($type);
        $columns = ['display_text', 'option_value', 'option_order'];
        $sidx = in_array($sidx, $columns) ? $sidx : 'display_text';
        $result = $obj->get_all([
            'where' => ['field_id = ? ' => $field_id],
            'order' => "$sidx $sord",
            'LIMIT' => "$start , $limit",
        ]);
        break;
    case 'get_usergroups_teacher':
        $columns = ['name', 'users', 'status', 'group_type', 'actions'];
        $options['order'] = "name $sord";
        $options['limit'] = "$start , $limit";
        $options['session_id'] = $sessionId;
        switch ($type) {
            case 'not_registered':
                if (empty($sessionId)) {
                    $options['where'] = [' (course_id IS NULL OR course_id != ?) ' => $course_id];
                } else {
                    $options['where'] = [' (session_id IS NULL OR session_id != ?) ' => $sessionId];
                }
                if (!empty($keyword)) {
                    $options['where']['AND name like %?% '] = $keyword;
                }
                $result = $obj->getUserGroupNotInCourse(
                    $options,
                    $groupFilter
                );
                break;
            case 'registered':
                $result = $obj->getUserGroupInCourse($options, $groupFilter);
                break;
        }

        $new_result = [];
        $currentUserId = api_get_user_id();
        $isAllow = api_is_allowed_to_edit();
        if (!empty($result)) {
            $urlUserGroup = api_get_path(WEB_CODE_PATH).'admin/usergroup_users.php?'.api_get_cidreq();
            foreach ($result as $group) {
                $countUsers = count($obj->get_users_by_usergroup($group['id']));
                $group['users'] = $countUsers;

                if (!empty($countUsers)) {
                    $group['users'] = Display::url(
                        $countUsers,
                        $urlUserGroup.'&id='.$group['id']
                    );
                }

                if ($obj->usergroup_was_added_in_course(
                    $group['id'],
                    $course_id,
                    api_get_session_id()
                )) {
                    $url = 'class.php?action=remove_class_from_course&id='.$group['id'].'&'.api_get_cidreq(
                        ).'&id_session='.api_get_session_id();
                    $icon = Display::return_icon('delete.png', get_lang('Remove'));
                } else {
                    $url = 'class.php?action=add_class_to_course&id='.$group['id'].'&'.api_get_cidreq(
                        ).'&type=not_registered';
                    $icon = Display::return_icon('add.png', get_lang('Add'));
                }

                switch ($group['group_type']) {
                    case 0:
                        $group['group_type'] = Display::label(get_lang('Class'), 'primary');
                        break;
                    case 1:
                        $group['group_type'] = Display::label(get_lang('Social'), 'success');
                        break;
                }

                $role = $obj->getUserRoleToString(api_get_user_id(), $group['id']);
                $group['status'] = $role;
                $group['actions'] = '';

                if ($isAllow) {
                    if ($obj->allowTeachers() && $group['author_id'] == $currentUserId) {
                        $group['actions'] .= Display::url(
                                Display::return_icon('statistics.png', get_lang('Statistics')),
                                $urlUserGroup.'&id='.$group['id']
                            ).'&nbsp;';
                    }
                    $group['actions'] .= Display::url($icon, $url);
                }
                $new_result[] = $group;
            }
            $result = $new_result;
        }

        if (!in_array($sidx, $columns)) {
            $sidx = 'name';
        }
        // Multidimensional sort
        $result = msort($result, $sidx, $sord);
        break;
    default:
        exit;
}

$allowed_actions = [
    'get_careers',
    'get_promotions',
    'get_mail_template',
    'get_usergroups',
    'get_usergroups_teacher',
    'get_gradebooks',
    'get_sessions',
    'get_session_access_overview',
    'get_sessions_tracking',
    'get_session_lp_progress',
    'get_survey_overview',
    'get_session_progress',
    'get_exercise_progress',
    'get_exercise_results',
    'get_exercise_pending_results',
    'get_exercise_results_report',
    'get_work_student_list_overview',
    'get_work_teacher',
    'get_work_student',
    'get_all_work_student',
    'get_work_user_list',
    'get_work_user_list_others',
    'get_work_user_list_all',
    'get_work_pending_list',
    'get_timelines',
    'get_grade_models',
    'get_user_skill_ranking',
    'get_extra_fields',
    'get_extra_field_options',
    //'get_course_exercise_medias',
    'get_user_course_report',
    'get_user_course_report_resumed',
    'get_exercise_grade',
    'get_group_reporting',
    'get_course_announcements',
    'get_programmed_announcements',
    'course_log_events',
    'get_learning_path_calendars',
    'get_usergroups_users',
    'get_calendar_users',
    'get_exercise_categories',
];

// 5. Creating an obj to return a json
if (in_array($action, $allowed_actions)) {
    $response = new stdClass();
    $response->page = $page;
    $response->total = $total_pages;
    $response->records = $count;

    if ($operation && 'excel' == $operation) {
        $j = 1;
        $array = [];
        if (empty($column_names)) {
            $column_names = $columns;
        }

        // Headers
        foreach ($column_names as $col) {
            // Overwrite titles
            if (isset($overwriteColumnHeaderExport[$col])) {
                $col = $overwriteColumnHeaderExport[$col];
            }
            $array[0][] = $col;
        }

        foreach ($result as $row) {
            foreach ($columns as $col) {
                $array[$j][] = strip_tags($row[$col]);
            }
            $j++;
        }

        $fileName = !empty($action) ? $action : 'company_report';
        if (!empty($exportFilename)) {
            $fileName = $exportFilename;
        }

        switch ($exportFormat) {
            case 'xls':
                Export::arrayToXls($array, $fileName);
                break;
            case 'xls_html':
                //TODO add date if exists
                Export::export_table_xls_html($array, $fileName);
                break;
            case 'csv':
            default:
                Export::arrayToCsv($array, $fileName);
                break;
        }
        exit;
    }
    $i = 0;
    if (!empty($result)) {
        foreach ($result as $row) {
            // if results tab give not id, set id to $i otherwise id="null"
            // for all <tr> of the jqgrid - ref #4235
            if (!isset($row['id']) || isset($row['id']) && '' == $row['id']) {
                $response->rows[$i]['id'] = $i;
            } else {
                $response->rows[$i]['id'] = $row['id'];
            }
            $array = [];
            foreach ($columns as $col) {
                if (in_array($col, ['correction', 'actions'])) {
                    $array[] = isset($row[$col]) ? $row[$col] : '';
                } else {
                    $array[] = isset($row[$col]) ? Security::remove_XSS($row[$col]) : '';
                }
            }
            $response->rows[$i]['cell'] = $array;
            $i++;
        }
    }

    header('Content-Type: application/json;charset=utf-8');
    echo json_encode($response);
}
exit;
