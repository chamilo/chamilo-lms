<?php
/* For licensing terms, see /license.txt */

//@todo this could be integrated in the inc/lib/model.lib.php + try to clean this file
require_once __DIR__.'/../global.inc.php';

$libpath = api_get_path(LIBRARY_PATH);

// 1. Setting variables needed by jqgrid

$action = $_GET['a'];
$page = intval($_REQUEST['page']); //page
$limit = intval($_REQUEST['rows']); //quantity of rows
$sidx = $_REQUEST['sidx']; //index (field) to filter
$sord = $_REQUEST['sord']; //asc or desc

if (strpos(strtolower($sidx), 'asc') !== false) {
    $sidx = str_replace(array('asc', ','), '', $sidx);
    $sord = 'asc';
}

if (strpos(strtolower($sidx), 'desc') !== false) {
    $sidx = str_replace(array('desc', ','), '', $sidx);
    $sord = 'desc';
}

if (!in_array($sord, array('asc', 'desc'))) {
    $sord = 'desc';
}

// Actions allowed to other roles.
if (!in_array(
    $action,
    array(
        'get_exercise_results',
        'get_work_student_list_overview',
        'get_hotpotatoes_exercise_results',
        'get_work_teacher',
        'get_work_student',
        'get_work_user_list',
        'get_work_user_list_others',
        'get_work_user_list_all',
        'get_timelines',
        'get_user_skill_ranking',
        'get_usergroups_teacher',
        'get_user_course_report_resumed',
        'get_user_course_report',
        'get_sessions_tracking',
        'get_sessions',
        'get_course_announcements'
    )
) && !isset($_REQUEST['from_course_session'])) {
    api_protect_admin_script(true);
} elseif (isset($_REQUEST['from_course_session']) &&
    $_REQUEST['from_course_session'] == 1
) {
    api_protect_teacher_script(true);
}

// Search features

//@todo move this in the display_class or somewhere else

function getWhereClause($col, $oper, $val)
{
    $ops = array(
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
        'nc' => 'NOT LIKE'  //doesn't contain
    );

    if (empty($col)) {
        return '';
    }

    if ($oper == 'bw' || $oper == 'bn') {
        $val .= '%';
    }
    if ($oper == 'ew' || $oper == 'en') {
        $val = '%'.$val;
    }
    if ($oper == 'cn' || $oper == 'nc' || $oper == 'in' || $oper == 'ni') {
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
$extra_fields = array();

if (!empty($searchString)) {
    $search = 'true';
}

if (($search || $forceSearch) && ($search !== 'false')) {
    $whereCondition = ' 1 = 1 ';
    $whereConditionInForm = getWhereClause($searchField, $searchOperator, $searchString);

    if (!empty($whereConditionInForm)) {
        $whereCondition .= ' AND '.$whereConditionInForm;
    }
    $filters = isset($_REQUEST['filters']) && !is_array($_REQUEST['filters']) ? json_decode($_REQUEST['filters']) : false;

    if (!empty($filters)) {
        if (in_array($action, ['get_questions', 'get_sessions'])) {
            switch ($action) {
                case 'get_questions':
                    $type = 'question';
                    break;
                case 'get_sessions':
                    $type = 'session';
                    break;
            }

            if (!empty($type)) {
                // Extra field.
                $extraField = new ExtraField($type);
                $result = $extraField->getExtraFieldRules($filters, 'extra_');
                $extra_fields = $result['extra_fields'];
                $condition_array = $result['condition_array'];

                $extraCondition = '';
                if (!empty($condition_array)) {
                    $extraCondition = ' AND ( ';
                    $extraCondition .= implode($filters->groupOp, $condition_array);
                    $extraCondition .= ' ) ';
                }

                $whereCondition .= $extraCondition;

                // Question field

                $resultQuestion = $extraField->getExtraFieldRules($filters, 'question_');
                $questionFields = $resultQuestion['extra_fields'];
                $condition_array = $resultQuestion['condition_array'];

                if (!empty($condition_array)) {
                    $extraQuestionCondition = ' AND ( ';
                    $extraQuestionCondition .= implode($filters->groupOp, $condition_array);
                    $extraQuestionCondition .= ' ) ';
                    // Remove conditions already added
                    $extraQuestionCondition = str_replace($extraCondition, '', $extraQuestionCondition);
                }

                $whereCondition .= $extraQuestionCondition;
            }
        } elseif (!empty($filters->rules)) {
            $whereCondition .= ' AND ( ';
            $counter = 0;
            foreach ($filters->rules as $key => $rule) {
                $whereCondition .= getWhereClause($rule->field, $rule->op, $rule->data);

                if ($counter < count($filters->rules) - 1) {
                    $whereCondition .= $filters->groupOp;
                }
                $counter++;
            }
            $whereCondition .= ' ) ';
        }
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
    case 'get_programmed_announcements':
        $object = new ScheduledAnnouncement();
        $count = $object->get_count();
        break;
    case 'get_group_reporting':
        $course_id = isset($_REQUEST['course_id']) ? $_REQUEST['course_id'] : null;
        $group_id = isset($_REQUEST['gidReq']) ? $_REQUEST['gidReq'] : null;
        $sessionId = isset($_REQUEST['session_id']) ? $_REQUEST['session_id'] : null;
        $count = Tracking::get_group_reporting($course_id, $sessionId, $group_id, 'count');
        break;
    case 'get_user_course_report':
    case 'get_user_course_report_resumed':
        $userId = api_get_user_id();
        $sessionId = isset($_GET['session_id']) ? intval($_GET['session_id']) : 0;
        $courseCodeList = array();
        $userIdList = array();
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
                0,
                null,
                0,
                'ASC',
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
                1,
                'asc',
                null,
                null,
                null,
                array(),
                $supervisorStudents,
                5
            );
            $userIdList = array_column($userIdList, 'user_id');

            //get students session courses
            if ($sessionId == -1) {
                $sessionList = SessionManager::get_sessions_list();
                $sessionIdList = array_column($sessionList, 'id');

                $courseCodeList = array();
                foreach ($sessionList as $session) {
                    $courses = SessionManager::get_course_list_by_session_id($session['id']);
                    $courseCodeList = array_merge($courseCodeList, array_column($courses, 'code'));
                }
            }

            $searchByGroups = true;
        } elseif (api_is_platform_admin()) {
            //get students with course or session
            $userIdList = SessionManager::getAllUsersFromCoursesFromAllSessionFromStatus(
                'admin',
                null,
                false,
                null,
                null,
                1,
                'asc',
                null,
                null,
                null,
                array(),
                array(),
                5
            );
            $userIdList = array_column($userIdList, 'user_id');

            //get students session courses
            if ($sessionId == -1) {
                $sessionList = SessionManager::get_sessions_list();
                $sessionIdList = array_column($sessionList, 'id');

                $courseCodeList = array();
                foreach ($sessionList as $session) {
                    $courses = SessionManager::get_course_list_by_session_id($session['id']);
                    $courseCodeList = array_merge($courseCodeList, array_column($courses, 'code'));
                }
            }
            $searchByGroups = true;
        }

        if ($searchByGroups) {
            $userGroup = new UserGroup();
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

        if ($action == 'get_user_course_report') {
            $count = CourseManager::get_count_user_list_from_course_code(
                false,
                null,
                $courseCodeList,
                $userIdList,
                $sessionIdList
            );
        } else {
            $count = CourseManager::get_count_user_list_from_course_code(
                true,
                array('ruc'),
                $courseCodeList,
                $userIdList,
                $sessionIdList
            );
        }
        break;
    case 'get_course_exercise_medias':
        $course_id = api_get_course_int_id();
        $count = Question::get_count_course_medias($course_id);
        break;
    case 'get_user_skill_ranking':
        $skill = new Skill();
        $count = $skill->get_user_list_skill_ranking_count();
        break;
    case 'get_course_announcements':
        $count = AnnouncementManager::getAnnouncements(null, null, true);
        break;
    case 'get_work_teacher':
        require_once api_get_path(SYS_CODE_PATH).'work/work.lib.php';
        $count = getWorkListTeacher(0, $limit, $sidx, $sord, $whereCondition, true);
        break;
    case 'get_work_student':
        require_once api_get_path(SYS_CODE_PATH).'work/work.lib.php';
        $count = getWorkListStudent(0, $limit, $sidx, $sord, $whereCondition, true);
        break;
    case 'get_work_user_list_all':
        require_once api_get_path(SYS_CODE_PATH).'work/work.lib.php';
        $work_id = $_REQUEST['work_id'];
        $count = get_count_work($work_id);
        break;
    case 'get_work_user_list_others':
        require_once api_get_path(SYS_CODE_PATH).'work/work.lib.php';
        $work_id = $_REQUEST['work_id'];
        $count = get_count_work($work_id, api_get_user_id());
        break;
    case 'get_work_user_list':
        require_once api_get_path(SYS_CODE_PATH).'work/work.lib.php';
        $work_id = $_REQUEST['work_id'];
        $courseInfo = api_get_course_info();
        $documents = getAllDocumentToWork($work_id, api_get_course_int_id());

        if (empty($documents)) {
            $whereCondition .= " AND u.user_id = ".api_get_user_id();
            $count = get_work_user_list(
                0,
                $limit,
                $sidx,
                $sord,
                $work_id,
                $whereCondition,
                null,
                true
            );
        } else {
            $count = get_work_user_list_from_documents(
                0,
                $limit,
                $sidx,
                $sord,
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
        require_once api_get_path(SYS_CODE_PATH).'work/work.lib.php';
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
    case 'get_exercise_results':
        $exercise_id = $_REQUEST['exerciseId'];

        if (isset($_GET['filter_by_user']) && !empty($_GET['filter_by_user'])) {
            $filter_user = (int) $_GET['filter_by_user'];
            if (empty($whereCondition)) {
                $whereCondition .= " te.exe_user_id  = '$filter_user'";
            } else {
                $whereCondition .= " AND te.exe_user_id  = '$filter_user'";
            }
        }

        if (!empty($whereCondition)) {
            $whereCondition = " AND $whereCondition";
        }

        $count = ExerciseLib::get_count_exam_results($exercise_id, $whereCondition);
        break;
    case 'get_hotpotatoes_exercise_results':
        $hotpot_path = $_REQUEST['path'];
        $count = ExerciseLib::get_count_exam_hotpotatoes_results($hotpot_path);
        break;
    case 'get_sessions_tracking':
        $keyword = isset($_REQUEST['keyword']) ? $_REQUEST['keyword'] : '';

        $description = '';
        $setting = api_get_setting('show_session_description');
        if ($setting === 'true') {
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
                $description
            );
        } else {
            // Sessions for the coach
            $count = Tracking::get_sessions_coached_by_user(
                api_get_user_id(),
                null,
                null,
                true,
                $keyword,
                $description
            );
        }
        break;
    case 'get_sessions':
        $list_type = isset($_REQUEST['list_type']) ? $_REQUEST['list_type'] : 'simple';
        if ($list_type === 'simple') {
            $count = SessionManager::get_sessions_admin(
                array('where' => $whereCondition, 'extra' => $extra_fields),
                true
            );
        } else {
            $count = SessionManager::get_count_admin_complete(
                array('where' => $whereCondition, 'extra' => $extra_fields)
            );
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
        $users = CourseManager::get_student_list_from_course_code($course['code'], true, $_GET['session_id']);

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
    case 'get_timelines':
        require_once $libpath.'timeline.lib.php';
        $obj = new Timeline();
        $count = $obj->get_count();
        break;
    case 'get_gradebooks':
        require_once $libpath.'gradebook.lib.php';
        $obj = new Gradebook();
        $count = $obj->get_count();
        break;
    case 'get_event_email_template':
        $obj = new EventEmailTemplate();
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
    case 'get_grade_models':
        $obj = new GradeModel();
        $count = $obj->get_count();
        break;
    case 'get_usergroups':
        $obj = new UserGroup();
        $count = $obj->get_count();
        break;
    case 'get_usergroups_teacher':
        $obj = new UserGroup();
        $type = isset($_REQUEST['type']) ? $_REQUEST['type'] : 'registered';
        $groupFilter = isset($_REQUEST['group_filter']) ? intval($_REQUEST['group_filter']) : 0;

        $course_id = api_get_course_int_id();
        if ($type == 'registered') {
            $count = $obj->getUserGroupByCourseWithDataCount($course_id, $groupFilter);
        } else {
            $count = $obj->get_count($groupFilter);
        }
        break;
    default:
        exit;
}

//3. Calculating first, end, etc
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
if (isset($_REQUEST['oper']) && $_REQUEST['oper'] == 'del') {
    $obj->delete($_REQUEST['id']);
}

$is_allowedToEdit = api_is_allowed_to_edit(null, true) || api_is_allowed_to_edit(true) || api_is_drh();

//5. Querying the DB for the elements
$columns = array();

switch ($action) {
    case 'get_programmed_announcements':
        $columns = array('subject', 'date', 'sent', 'actions');
        $sessionId = isset($_REQUEST['session_id']) ? (int) $_REQUEST['session_id'] : 0;

        $result = Database::select(
            '*',
            $object->table,
            array(
                'where' => array("session_id = ? " => $sessionId),
                'order' => "$sidx $sord",
                'LIMIT' => "$start , $limit")
        );
        if ($result) {
            foreach ($result as &$item) {
                $item['date'] = api_get_local_time($item['date']);
            }
        }
        break;
    case 'get_group_reporting':
        $columns = array('name', 'time', 'progress', 'score', 'works', 'messages', 'actions');

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
        $columns = array('question');
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
        $columns = array(
            'extra_ruc',
            'training_hours',
            'count_users',
            'count_users_registered',
            'average_hours_per_user',
            'count_certificates'
        );

        $column_names = array(
            get_lang('Company'),
            get_lang('TrainingHoursAccumulated'),
            get_lang('CountOfSubscriptions'),
            get_lang('CountOfUsers'),
            get_lang('AverageHoursPerStudent'),
            get_lang('CountCertificates')
        );

        $extra_fields = UserManager::get_extra_fields(
            0,
            100,
            null,
            null,
            true,
            true
        );

        if (!empty($extra_fields)) {
            foreach ($extra_fields as $extra) {
                if ($extra['1'] == 'ruc') {
                    continue;
                }
                $columns[] = $extra['1'];
                $column_names[] = $extra['3'];
            }
        }

        if (!in_array($sidx, array('training_hours'))) {
            //$sidx = 'training_hours';
        }

        if (api_is_student_boss() && empty($userIdList)) {
            $result = [];
            break;
        }

        $result = CourseManager::get_user_list_from_course_code(
            null,
            null,
            "LIMIT $start, $limit",
            null, //" $sidx $sord",
            null,
            null,
            true,
            true,
            array('ruc'),
            $courseCodeList,
            $userIdList,
            null,
            $sessionIdList
        );

        $new_result = array();
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
        $columns = array(
            'course',
            'user',
            'email',
            'time',
            'certificate',
            'progress_100',
            'progress',
        );
        $column_names = array(
            get_lang('Course'),
            get_lang('User'),
            get_lang('Email'),
            get_lang('ManHours'),
            get_lang('CertificateGenerated'),
            get_lang('Approved'),
            get_lang('CourseAdvance')
        );

        $extra_fields = UserManager::get_extra_fields(
            0,
            100,
            null,
            null,
            true,
            true
        );
        if (!empty($extra_fields)) {
            foreach ($extra_fields as $extra) {
                $columns[] = $extra['1'];
                $column_names[] = $extra['3'];
            }
        }

        if (api_is_student_boss()) {
            $columns[] = 'group';
            $column_names[] = get_lang('Group');
        }

        if (!in_array($sidx, array('title'))) {
            $sidx = 'title';
        }

        if (api_is_student_boss() && empty($userIdList)) {
            $result = [];
            break;
        }

        //get sessions
        $arrSessions = array();
        if (count($sessionIdList) > 0) {
            $arrSessions = CourseManager::get_user_list_from_course_code(
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
                $sessionIdList
            );
        }

        //get courses
        $arrCourses = CourseManager::get_user_list_from_course_code(
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
            null
        );

        //merge courses and sessions
        $result = array_merge($arrSessions, $arrCourses);

        if (api_is_student_boss()) {
            $userGroup = new UserGroup();
            foreach ($result as &$item) {
                $userGroups = $userGroup->get_groups_by_user($item['user_id']);
                $item['group'] = implode(", ", array_column($userGroups, 'name'));
                unset($item['user_id']);
            }
        }

        break;
	case 'get_user_skill_ranking':
        $columns = array('photo', 'firstname', 'lastname', 'skills_acquired', 'currently_learning', 'rank');
        $result = $skill->get_user_list_skill_ranking($start, $limit, $sidx, $sord, $whereCondition);
        $result = msort($result, 'skills_acquired', 'asc');

        $skills_in_course = array();
        if (!empty($result)) {
            foreach ($result as &$item) {
                $user_info = api_get_user_info($item['user_id']);
                $personal_course_list = UserManager::get_personal_session_course_list($item['user_id']);
                $count_skill_by_course = array();
                foreach ($personal_course_list as $course_item) {
                    if (!isset($skills_in_course[$course_item['code']])) {
                        $count_skill_by_course[$course_item['code']] = $skill->get_count_skills_by_course($course_item['code']);
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
        $columns = array(
            'title',
            'username',
            'insert_date',
            'actions'
        );

        $titleToSearch = isset($_REQUEST['title_to_search']) ? $_REQUEST['title_to_search'] : '';
        $userIdToSearch = isset($_REQUEST['user_id_to_search']) ? $_REQUEST['user_id_to_search'] : 0;

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
        $columns = array(
            'type',
            'title',
            'sent_date',
            'expires_on',
            'amount',
            'actions'
        );
        $result = getWorkListTeacher($start, $limit, $sidx, $sord, $whereCondition);
        break;
    case 'get_work_student':
        $columns = array(
            'type',
            'title',
            'expires_on',
            'feedback',
            'last_upload',
            'others'
        );
        $result = getWorkListStudent($start, $limit, $sidx, $sord, $whereCondition);
        break;
    case 'get_work_user_list_all':
        if (isset($_GET['type']) && $_GET['type'] === 'simple') {
            $columns = array(
                'fullname',
                'title',
                'qualification',
                'sent_date',
                'qualificator_id',
                'correction',
                'actions'
            );
        } else {
            $columns = array(
                'fullname',
                'title',
                'qualification',
                'sent_date',
                //'status',
                //'has_correction',
                'correction',
                'actions'
            );
        }
        $result = get_work_user_list($start, $limit, $sidx, $sord, $work_id, $whereCondition);

        break;
    case 'get_work_user_list_others':
        if (isset($_GET['type']) && $_GET['type'] === 'simple') {
            $columns = array(
                'type', 'firstname', 'lastname', 'title', 'qualification', 'sent_date', 'qualificator_id', 'actions'
            );
        } else {
            $columns = array('type', 'firstname', 'lastname', 'title', 'sent_date', 'actions');
        }
        $whereCondition .= " AND u.user_id <> ".api_get_user_id();
        $result = get_work_user_list($start, $limit, $sidx, $sord, $work_id, $whereCondition);
        break;
    case 'get_work_user_list':
        if (isset($_GET['type']) && $_GET['type'] == 'simple') {
            $columns = array(
                'type', 'title', 'qualification', 'sent_date', 'qualificator_id', 'actions'
            );
        } else {
            $columns = array('type', 'title', 'qualification', 'sent_date', 'actions');
        }

        $documents = getAllDocumentToWork($work_id, api_get_course_int_id());

        if (empty($documents)) {
            $whereCondition .= " AND u.user_id = ".api_get_user_id();
            $result = get_work_user_list($start, $limit, $sidx, $sord, $work_id, $whereCondition);
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
    case 'get_exercise_results':
        $course = api_get_course_info();
        // Used inside ExerciseLib::get_exam_results_data()
        $documentPath = api_get_path(SYS_COURSE_PATH).$course['path']."/document";
        if ($is_allowedToEdit || api_is_student_boss()) {
            $columns = array(
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
            );
            $officialCodeInList = api_get_setting('show_official_code_exercise_result_list');
            if ($officialCodeInList === 'true') {
                $columns = array_merge(array('official_code'), $columns);
            }
        }
        $result = ExerciseLib::get_exam_results_data(
            $start,
            $limit,
            $sidx,
            $sord,
            $exercise_id,
            $whereCondition
        );
        break;
    case 'get_hotpotatoes_exercise_results':
        $course = api_get_course_info();
        $documentPath = api_get_path(SYS_COURSE_PATH).$course['path']."/document";
        if (api_is_allowed_to_edit()) {
            $columns = array('firstname', 'lastname', 'username', 'group_name', 'exe_date', 'score', 'actions');
        } else {
            $columns = array('exe_date', 'score', 'actions');
        }
        $result = ExerciseLib::get_exam_results_hotpotatoes_data(
            $start,
            $limit,
            $sidx,
            $sord,
            $hotpot_path,
            $whereCondition
        );
        break;
    case 'get_work_student_list_overview':
        if (!(api_is_allowed_to_edit() || api_is_coach())) {
            return array();
        }
        require_once api_get_path(SYS_CODE_PATH).'work/work.lib.php';
        $columns = array(
            'student', 'works'
        );

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
    case 'get_hotpotatoes_exercise_results':
        $course = api_get_course_info();
        $documentPath = api_get_path(SYS_COURSE_PATH).$course['path']."/document";

        if (api_is_allowed_to_edit(null, true) || api_is_drh()) {
            $columns = array('firstname', 'lastname', 'username', 'group_name', 'exe_date', 'score', 'actions');
        } else {
            $columns = array('exe_date', 'score', 'actions');
        }
        $result = ExerciseLib::get_exam_results_hotpotatoes_data(
            $start,
            $limit,
            $sidx,
            $sord,
            $hotpot_path,
            $whereCondition
        );
        break;
    case 'get_sessions_tracking':
        if (api_is_drh() || api_is_session_admin()) {
            $sessions = SessionManager::get_sessions_followed_by_drh(
                api_get_user_id(),
                $start,
                $limit,
                false,
                false,
                false,
                null,
                $keyword,
                $description
            );
        } else {
            // Sessions for the coach
            $sessions = Tracking::get_sessions_coached_by_user(
                api_get_user_id(),
                $start,
                $limit,
                false,
                $keyword,
                $description
            );
        }

        $columns = array(
            'name',
            'date',
            'course_per_session',
            'student_per_session',
            'details'
        );

        $result = array();
        if (!empty($sessions)) {
            foreach ($sessions as $session) {
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
                            $user_id,
                            $session['id']
                        )
                    );
                }

                $count_users_in_session = SessionManager::get_users_by_session($session['id'], 0, true);
                $session_date = array();
                if (!empty($session['access_start_date'])) {
                    $session_date[] = get_lang('From').' '.api_format_date($session['access_start_date'], DATE_FORMAT_SHORT);
                }

                if (!empty($session['access_end_date'])) {
                    $session_date[] = get_lang('Until').' '.api_format_date($session['access_end_date'], DATE_FORMAT_SHORT);
                }

                if (empty($session_date)) {
                    $session_date_string = '-';
                } else {
                    $session_date_string = implode(' ', $session_date);
                }

                $detailButtons = [];
                $detailButtons[] = Display::url(
                    Display::return_icon('works.png', get_lang('WorksReport')),
                    api_get_path(WEB_CODE_PATH).'mySpace/works_in_session_report.php?session='.$session['id']
                );
                $detailButtons[] = Display::url(
                    Display::return_icon('2rightarrow.png'),
                    api_get_path(WEB_CODE_PATH).'mySpace/course.php?session_id='.$session['id']
                );

                $result[] = array(
                    'name' => Display::url(
                        $session['name'],
                        api_get_path(WEB_CODE_PATH).'mySpace/course.php?session_id='.$session['id']
                    ),
                    'date' => $session_date_string,
                    'course_per_session' => $count_courses_in_session,
                    'student_per_session' => $count_users_in_session,
                    'details' => implode(' ', $detailButtons)
                );
            }
        }
        break;
    case 'get_sessions':
        $session_columns = SessionManager::getGridColumns($list_type);
        $columns = $session_columns['simple_column_name'];

        if ($list_type == 'simple') {
            $result = SessionManager::get_sessions_admin(
                array(
                    'where' => $whereCondition,
                    'order' => "$sidx $sord",
                    'extra' => $extra_fields,
                    'limit' => "$start , $limit",
                ),
                false,
                $session_columns
            );
        } else {
            $result = SessionManager::get_sessions_admin_complete(
                array(
                    'where' => $whereCondition,
                    'order' => "$sidx $sord",
                    'extra' => $extra_fields,
                    'limit' => "$start , $limit",
                )
            );
        }
        break;
        /*
        $columns = array(
            'name',
            'nbr_courses',
            'nbr_users',
            'category_name',
            'access_start_date',
            'access_end_date',
            'coach_name',
            'session_active',
            'visibility'
        );

        if (SessionManager::allowToManageSessions()) {
            if (SessionManager::allowOnlyMySessions()) {
                $whereCondition .= ' AND s.id_coach = '.api_get_user_id();
            }

            // Rename Category_name
            $whereCondition = str_replace(
                'category_name',
                'sc.name',
                $whereCondition
            );

            $result = SessionManager::get_sessions_admin(
                array(
                    'where' => $whereCondition,
                    'order' => "$sidx $sord",
                    'limit' => "$start , $limit"
                )
            );
        }
        */
        break;
    case 'get_exercise_progress':
        $sessionId  = intval($_GET['session_id']);
        $courseId   = intval($_GET['course_id']);
        $exerciseId = intval($_GET['exercise_id']);
        $date_from  = $_GET['date_from'];
        $date_to    = $_GET['date_to'];

        $columns = array(
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
        );

        $result = Tracking::get_exercise_progress(
            $sessionId,
            $courseId,
            $exerciseId,
            $date_from,
            $date_to,
            array(
                'where' => $whereCondition,
                'order' => "$sidx $sord",
                'limit'=> "$start , $limit"
            )
        );
        break;
    case 'get_session_lp_progress':
        $sessionId = 0;
        if (!empty($_GET['session_id']) && !empty($_GET['course_id'])) {
            $sessionId = intval($_GET['session_id']);
            $courseId = intval($_GET['course_id']);
            $course = api_get_course_info_by_id($courseId);
        }

        /**
         * Add lessons of course
         *
         */
        $columns = array(
            'username',
            'firstname',
            'lastname',
        );
        $lessons = LearnpathList::get_course_lessons($course['code'], $sessionId);
        foreach ($lessons as $lesson_id => $lesson) {
            $columns[] = $lesson_id;
        }
        $columns[] = 'total';

        $result = SessionManager::get_session_lp_progress(
            $sessionId,
            $courseId,
            $date_from,
            $date_to,
            array(
                'where' => $whereCondition,
                'order' => "$sidx $sord",
                'limit' => "$start , $limit",
            )
        );
        break;
    case 'get_survey_overview':
        $sessionId = 0;
        if (!empty($_GET['session_id']) &&
            !empty($_GET['course_id']) &&
            !empty($_GET['survey_id'])
        ) {
            $sessionId = intval($_GET['session_id']);
            $courseId  = intval($_GET['course_id']);
            $surveyId  = intval($_GET['survey_id']);
            $date_from  = $_GET['date_from'];
            $date_to    = $_GET['date_to'];
            //$course    = api_get_course_info_by_id($courseId);
        }
        /**
         * Add lessons of course
         */
        $columns = array(
            'username',
            'firstname',
            'lastname',
        );

        $questions = SurveyManager::get_questions($surveyId, $courseId);

        foreach ($questions as $question_id => $question) {
            $columns[] = $question_id;
        }

        $result = SessionManager::get_survey_overview(
            $sessionId,
            $courseId,
            $surveyId,
            $date_from,
            $date_to,
            array(
                'where' => $whereCondition,
                'order' => "$sidx $sord",
                'limit' => "$start , $limit",
            )
        );
        break;
    case 'get_session_progress':
        $columns = array(
            'lastname',
            'firstname',
            'username',
            #'profile',
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
        );
        $sessionId = 0;
        if (!empty($_GET['course_id']) && !empty($_GET['session_id'])) {
            $sessionId = intval($_GET['session_id']);
            $courseId = intval($_GET['course_id']);
        }
        $result = SessionManager::get_session_progress(
            $sessionId,
            $courseId,
            null,
            null,
            array(
                'where' => $whereCondition,
                'order' => "$sidx $sord",
                'limit'=> "$start , $limit"
            )
        );
        break;
    case 'get_session_access_overview':
        $columns = array(
            'logindate',
            'username',
            'lastname',
            'firstname',
            'clicks',
            'ip',
            'timeLoggedIn',
            'session'
        );
        $sessionId = 0;
        if (!empty($_GET['course_id']) && !empty($_GET['session_id'])) {
            $sessionId = intval($_GET['session_id']);
            $courseId = intval($_GET['course_id']);
            $studentId = intval($_GET['student_id']);
            $profile = intval($_GET['profile']);
            $date_from = intval($_GET['date_from']);
            $date_to = intval($_GET['date_to']);
        }

        $result = SessionManager::get_user_data_access_tracking_overview(
            $sessionId,
            $courseId,
            $studentId,
            $profile,
            $date_to,
            $date_from,
            array(
                'where' => $whereCondition,
                'order' => "$sidx $sord",
                'limit'=> "$start , $limit"
            )
        );
        break;
    case 'get_timelines':
        $columns = array('headline', 'actions');

        if (!in_array($sidx, $columns)) {
            $sidx = 'headline';
        }
        $course_id = api_get_course_int_id();
        $result = Database::select(
            '*',
            $obj->table,
            array(
                'where' => array(
                    'parent_id = ? AND c_id = ?' => array('0', $course_id)
                ),
                'order'=>"$sidx $sord",
                'LIMIT'=> "$start , $limit"
            )
        );
        $new_result = array();
        foreach ($result as $item) {
            if (!$item['status']) {
                $item['name'] = '<font style="color:#AAA">'.$item['name'].'</font>';
            }
            $item['headline'] = Display::url($item['headline'], api_get_path(WEB_CODE_PATH).'timeline/view.php?id='.$item['id']);
            $item['actions'] = Display::url(Display::return_icon('add.png', get_lang('AddItems')), api_get_path(WEB_CODE_PATH).'timeline/?action=add_item&parent_id='.$item['id']);
            $item['actions'] .= Display::url(Display::return_icon('edit.png', get_lang('Edit')), api_get_path(WEB_CODE_PATH).'timeline/?action=edit&id='.$item['id']);
            $item['actions'] .= Display::url(Display::return_icon('delete.png', get_lang('Delete')), api_get_path(WEB_CODE_PATH).'timeline/?action=delete&id='.$item['id']);

            $new_result[] = $item;
        }
        $result = $new_result;
        break;
    case 'get_gradebooks':
        $columns = array('name', 'certificates', 'skills', 'actions', 'has_certificates');
        if (!in_array($sidx, $columns)) {
            $sidx = 'name';
        }
        $result = Database::select(
            '*',
            $obj->table,
            array('order' => "$sidx $sord", 'LIMIT' => "$start , $limit")
        );
        $new_result = array();
        foreach ($result as $item) {
            if ($item['parent_id'] != 0) {
                continue;
            }
            $skills = $obj->get_skills_by_gradebook($item['id']);

            //Fixes bug when gradebook doesn't have names
            if (empty($item['name'])) {
                $item['name'] = $item['course_code'];
            }

            $item['name'] = Display::url($item['name'], api_get_path(WEB_CODE_PATH).'gradebook/index.php?id_session=0&cidReq='.$item['course_code']);

            if (!empty($item['certif_min_score']) && !empty($item['document_id'])) {
                $item['certificates'] = Display::return_icon('accept.png', get_lang('WithCertificate'), array(), ICON_SIZE_SMALL);
                 $item['has_certificates'] = '1';
            } else {
                $item['certificates'] = Display::return_icon('warning.png', get_lang('NoCertificate'), array(), ICON_SIZE_SMALL);
                $item['has_certificates'] = '0';
            }

            if (!empty($skills)) {
                $item['skills'] = '';
                foreach ($skills as $skill) {
                    $item['skills'] .= Display::span($skill['name'], array('class' => 'label_tag skill'));
                }
            }
            $new_result[] = $item;
        }
        $result = $new_result;
        break;
    case 'get_event_email_template':
        $columns = array('subject', 'event_type_name', 'language_id', 'activated', 'actions');
        if (!in_array($sidx, $columns)) {
            $sidx = 'subject';
        }
        $result = Database::select(
            '*',
            $obj->table,
            array('order' => "$sidx $sord", 'LIMIT' => "$start , $limit")
        );
        $new_result = array();
        foreach ($result as $item) {
            $language_info = api_get_language_info($item['language_id']);
            $item['language_id'] = $language_info['english_name'];
            $item['actions'] = Display::url(Display::return_icon('edit.png', get_lang('Edit')), api_get_path(WEB_CODE_PATH).'admin/event_type.php?action=edit&event_type_name='.$item['event_type_name']);
            $item['actions'] .= Display::url(Display::return_icon('delete.png', get_lang('Delete')), api_get_path(WEB_CODE_PATH).'admin/event_controller.php?action=delete&id='.$item['id']);
            $new_result[] = $item;
        }
        $result = $new_result;
        break;
    case 'get_careers':
        $columns = array('name', 'description', 'actions');
        if (!in_array($sidx, $columns)) {
            $sidx = 'name';
        }
        $result = Database::select(
            '*',
            $obj->table,
            array('order' => "$sidx $sord", 'LIMIT' => "$start , $limit")
        );
        $new_result = array();
        foreach ($result as $item) {
            if (!$item['status']) {
                $item['name'] = '<font style="color:#AAA">'.$item['name'].'</font>';
            }
            $new_result[] = $item;
        }
        $result = $new_result;
        break;
    case 'get_promotions':
        $columns = array('name', 'career', 'description', 'actions');
        if (!in_array($sidx, $columns)) {
            $sidx = 'name';
        }

        $result = Database::select(
            'p.id,p.name, p.description, c.name as career, p.status',
            "$obj->table p LEFT JOIN ".Database::get_main_table(TABLE_CAREER)." c  ON c.id = p.career_id ",
            array('order' => "$sidx $sord", 'LIMIT'=> "$start , $limit")
        );

        $new_result = array();
        foreach ($result as $item) {
            if (!$item['status']) {
                $item['name'] = '<font style="color:#AAA">'.$item['name'].'</font>';
            }
            $new_result[] = $item;
        }
        $result = $new_result;
        break;
    case 'get_grade_models':
        $columns = array('name', 'description', 'actions');
        if (!in_array($sidx, $columns)) {
            $sidx = 'name';
        }
        $result = Database::select(
            '*',
            "$obj->table ",
            array('order' => "$sidx $sord", 'LIMIT' => "$start , $limit")
        );
        $new_result = array();
        foreach ($result as $item) {
            $new_result[] = $item;
        }
        $result = $new_result;
        break;
    case 'get_usergroups':
        $columns = array('name', 'users', 'courses', 'sessions', 'group_type', 'actions');
        $result = $obj->getUsergroupsPagination($sidx, $sord, $start, $limit);
        break;
    case 'get_extra_fields':
        $obj = new ExtraField($type);
        $columns = array(
            'display_text',
            'variable',
            'field_type',
            'changeable',
            'visible_to_self',
            'visible_to_others',
            'filter',
            'field_order',
        );
        $result = $obj->getAllGrid($sidx, $sord, $start, $limit);
        $new_result = array();
        if (!empty($result)) {
            $checkIcon = Display::return_icon('check-circle.png', get_lang('Yes'));
            $timesIcon = Display::return_icon('closed-circle.png', get_lang('No'));
            foreach ($result as $item) {
                $item['display_text'] = ExtraField::translateDisplayName($item['variable'], $item['displayText']);
                $item['field_type'] = $obj->get_field_type_by_id($item['fieldType']);
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
        $exercises = $objExercise->getExercisesByCourseSession(
            $_GET['course_id'],
            $_GET['session_id']
        );
        $cntExer = 4;
        if (!empty($exercises)) {
            $cntExer += count($exercises);
        }

        $columns = array();
        //Get dynamic column names
        $i = 1;
        $column_names = array();
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
                    $column_names[] = get_lang('FinalScore');
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

        $quizIds = array();
        if (!empty($exercises)) {
            foreach ($exercises as $exercise) {
                $quizIds[] = $exercise['id'];
            }
        }

        $course = api_get_course_info_by_id($_GET['course_id']);
        $listUserSess = CourseManager::get_student_list_from_course_code($course['code'], true, $_GET['session_id']);

        $usersId = array_keys($listUserSess);

        $users = UserManager::get_user_list_by_ids($usersId, null, "lastname, firstname", "$start , $limit");
        $exeResults = $objExercise->getExerciseAndResult($_GET['course_id'], $_GET['session_id'], $quizIds);

        $arrGrade = array();
        foreach ($exeResults as $exeResult) {
            $arrGrade[$exeResult['exe_user_id']][$exeResult['exe_exo_id']] = $exeResult['exe_result'];
        }

        $result = array();
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
                if (!empty($arrGrade [$user['user_id']][$quizID]) || $arrGrade [$user['user_id']][$quizID] == 0) {
                    $finalScore += $grade = $arrGrade [$user['user_id']][$quizID];
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
        $columns = array('display_text', 'option_value', 'option_order');
        $result = $obj->get_all([
            'where' => array("field_id = ? " => $field_id),
            'order' => "$sidx $sord",
            'LIMIT' => "$start , $limit"
        ]);
        break;
    case 'get_usergroups_teacher':
        $columns = array('name', 'users', 'status', 'group_type', 'actions');
        $options = array('order'=>"name $sord", 'LIMIT'=> "$start , $limit");
        $options['course_id'] = $course_id;

        switch ($type) {
            case 'not_registered':
                $options['where'] = array(" (course_id IS NULL OR course_id != ?) " => $course_id);
                $result = $obj->getUserGroupNotInCourse($options, $groupFilter);
                break;
            case 'registered':
                $options['where'] = array(" usergroup.course_id = ? " =>  $course_id);
                $result = $obj->getUserGroupInCourse($options, $groupFilter);
                break;
        }

        $new_result = array();
        if (!empty($result)) {
            foreach ($result as $group) {
                $group['users'] = count($obj->get_users_by_usergroup($group['id']));
                if ($obj->usergroup_was_added_in_course($group['id'], $course_id)) {
                    $url  = 'class.php?action=remove_class_from_course&id='.$group['id'].'&'.api_get_cidreq();
                    $icon = Display::return_icon('delete.png', get_lang('Remove'));
                    //$class = 'btn btn-danger';
                    //$text = get_lang('Remove');
                } else {
                    $url = 'class.php?action=add_class_to_course&id='.$group['id'].'&'.api_get_cidreq().'&type=not_registered';
                    //$class = 'btn btn-primary';
                    $icon = Display::return_icon('add.png', get_lang('Add'));
                    //$text = get_lang('Add');
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
                $group['actions'] = Display::url($icon, $url);
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

$allowed_actions = array(
    'get_careers',
    'get_promotions',
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
    'get_work_student_list_overview',
    'get_hotpotatoes_exercise_results',
    'get_work_teacher',
    'get_work_student',
    'get_work_user_list',
    'get_work_user_list_others',
    'get_work_user_list_all',
    'get_timelines',
    'get_grade_models',
    'get_event_email_template',
    'get_user_skill_ranking',
    'get_extra_fields',
    'get_extra_field_options',
    //'get_course_exercise_medias',
    'get_user_course_report',
    'get_user_course_report_resumed',
    'get_exercise_grade',
    'get_group_reporting',
    'get_course_announcements',
    'get_programmed_announcements'
);

//5. Creating an obj to return a json
if (in_array($action, $allowed_actions)) {
    $response = new stdClass();
    $response->page = $page;
    $response->total = $total_pages;
    $response->records = $count;

    if ($operation && $operation == 'excel') {
        $j = 1;

        $array = array();
        if (empty($column_names)) {
            $column_names = $columns;
        }

        //Headers
        foreach ($column_names as $col) {
            $array[0][] = $col;
        }
        foreach ($result as $row) {
            foreach ($columns as $col) {
                $array[$j][] = strip_tags($row[$col]);
            }
            $j++;
        }
        switch ($exportFormat) {
            case 'xls':
                //TODO add date if exists
                $file_name = (!empty($action)) ? $action : 'company_report';
                $browser = new Browser();
                if ($browser->getPlatform() == Browser::PLATFORM_WINDOWS) {
                    Export::export_table_xls_html($array, $file_name, 'ISO-8859-15');
                } else {
                    Export::export_table_xls_html($array, $file_name);
                }
                break;
            case 'csv':
            default:
                //TODO add date if exists
                $file_name = (!empty($action)) ? $action : 'company_report';
                Export::arrayToCsv($array, $file_name);
                break;
        }
        exit;
    }

    $i = 0;

    if (!empty($result)) {
        foreach ($result as $row) {
            // if results tab give not id, set id to $i otherwise id="null" for all <tr> of the jqgrid - ref #4235
            if (!isset($row['id']) || isset($row['id']) && $row['id'] == '') {
                $response->rows[$i]['id'] = $i;
            } else {
                $response->rows[$i]['id'] = $row['id'];
            }
            $array = array();
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
