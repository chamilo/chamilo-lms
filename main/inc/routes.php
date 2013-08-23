<?php
/* For licensing terms, see /license.txt */

use Symfony\Component\HttpFoundation\Request;
use \ChamiloSession as Session;

/** Setting course session and group global values */
$settingCourseConditions = function (Request $request) use ($cidReset, $app) {

    $cidReq    = $request->get('cidReq');
    $sessionId = $request->get('id_session');
    $groupId   = $request->get('gidReq');

    $tempCourseId  = api_get_course_id();
    $tempGroupId   = api_get_group_id();
    $tempSessionId = api_get_session_id();

    $courseReset = false;
    if ((!empty($cidReq) && $tempCourseId != $cidReq) || empty($tempCourseId) || empty($tempCourseId) == -1) {
        $courseReset = true;
    }

    if (isset($cidReset) && $cidReset == 1) {
        $courseReset = true;
    }

    Session::write('courseReset', $courseReset);

    $groupReset = false;
    if ($tempGroupId != $groupId || empty($tempGroupId)) {
        $groupReset = true;
    }

    $sessionReset = false;
    if ($tempSessionId != $sessionId || empty($tempSessionId)) {
        $sessionReset = true;
    }
    /*
        $app['monolog']->addDebug('Start');
        $app['monolog']->addDebug($courseReset);
        $app['monolog']->addDebug($cidReq);
        $app['monolog']->addDebug($tempCourseId);
        $app['monolog']->addDebug('End');
    */

    if ($courseReset) {
        if (!empty($cidReq) && $cidReq != -1) {
            $courseInfo = api_get_course_info($cidReq, true, true);

            if (!empty($courseInfo)) {
                $courseCode = $courseInfo['code'];
                $courseId   = $courseInfo['real_id'];

                Session::write('_real_cid', $courseId);
                Session::write('_cid', $courseCode);
                Session::write('_course', $courseInfo);

            } else {
                $app->abort(404, 'Course not available');
            }
        } else {
            Session::erase('_real_cid');
            Session::erase('_cid');
            Session::erase('_course');
        }
    }

    $courseCode = api_get_course_id();

    if (!empty($courseCode) && $courseCode != -1) {
        $tbl_course = Database::get_main_table(TABLE_MAIN_COURSE);
        $time       = api_get_utc_datetime();
        $sql        = "UPDATE $tbl_course SET last_visit= '$time' WHERE code='$courseCode'";
        Database::query($sql);
    }

    if ($sessionReset) {
        Session::erase('session_name');
        Session::erase('id_session');

        if (!empty($sessionId)) {
            $sessionInfo = api_get_session_info($sessionId);
            if (empty($sessionInfo)) {
                $app->abort(404, 'Session not available');
            } else {
                Session::write('id_session', $sessionId);
            }
        }
    }

    if ($groupReset) {
        Session::erase('_gid');
        if (!empty($groupId)) {
            Session::write('_gid', $groupId);
        }
    }

    if (!isset($_SESSION['login_as'])) {
        $userId = api_get_user_id();

        // Course login
        if (isset($userId)) {
            event_course_login(api_get_course_int_id(), $userId, api_get_session_id());
        }
    }
};

$userCourseAdmin = function(Request $request) use ($app) {
    if (api_is_allowed_to_edit()) {
        return null;
    } else {
        return $app->abort(401);
    }
};

/** Checks user permissions inside a course teacher? coach? etc */
$userPermissionsInsideACourse = function (Request $request) use ($app) {

    $courseId  = api_get_course_int_id();
    $userId    = api_get_user_id();
    $sessionId = api_get_session_id();

    //If I'm the admin platform i'm a teacher of the course
    $is_platformAdmin = api_is_platform_admin();
    $courseReset      = Session::read('courseReset');

    //$app['monolog']->addDebug($courseReset);
    //$app['monolog']->addDebug($courseId);

    // Course
    $is_courseMember = false;
    $is_courseAdmin  = false;
    $is_courseTutor  = false;
    $is_courseCoach  = false;
    $is_sessionAdmin = false;

    if ($courseReset) {

        if (isset($courseId) && $courseId && $courseId != -1) {

            $courseInfo = api_get_course_info();

            $userId   = isset($userId) ? intval($userId) : 0;
            $variable = 'accept_legal_'.$userId.'_'.$courseInfo['real_id'].'_'.$sessionId;

            $user_pass_open_course = false;
            if (api_check_user_access_to_legal($courseInfo['visibility']) && Session::read($variable)) {
                $user_pass_open_course = true;
            }

            //Checking if the user filled the course legal agreement
            if ($courseInfo['activate_legal'] == 1 && !api_is_platform_admin()) {
                $user_is_subscribed = CourseManager::is_user_accepted_legal(
                    $userId,
                    $courseInfo,
                    $sessionId
                ) || $user_pass_open_course;
                if (!$user_is_subscribed) {
                    $url = api_get_path(WEB_CODE_PATH).'course_info/legal.php?course_code='.$courseInfo['code'].'&session_id='.$sessionId;
                    header('Location: '.$url);
                    exit;
                }
            }

            //Check if user is subscribed in a course
            $course_user_table = Database::get_main_table(TABLE_MAIN_COURSE_USER);
            $sql               = "SELECT * FROM $course_user_table WHERE user_id  = '".$userId."' AND
                                  relation_type <> ".COURSE_RELATION_TYPE_RRHH." AND c_id = ".api_get_course_int_id();

            $result = Database::query($sql);

            $cuData = null;
            if (Database::num_rows($result) > 0) { // this  user have a recorded state for this course
                $cuData          = Database::fetch_array($result, 'ASSOC');
                $is_courseAdmin  = (bool)($cuData['status'] == 1);
                $is_courseTutor  = (bool)($cuData['tutor_id'] == 1);
                $is_courseMember = true;

                $_courseUser['role'] = $cuData['role'];
                Session::write('_courseUser', $_courseUser);
            }

            //We are in a session course? Check session permissions
            if (!empty($session_id)) {
                //I'm not the teacher of the course
                if ($is_courseAdmin == false) {
                    // this user has no status related to this course
                    // The user is subscribed in a session? The user is a Session coach a Session admin ?

                    $tbl_session             = Database :: get_main_table(TABLE_MAIN_SESSION);
                    $tbl_session_course_user = Database :: get_main_table(TABLE_MAIN_SESSION_COURSE_USER);

                    //Session coach, session admin, course coach admin
                    $sql = "SELECT session.id_coach, session_admin_id, session_rcru.id_user
                    FROM $tbl_session session, $tbl_session_course_user session_rcru
                    WHERE  session_rcru.id_session  = session.id AND
                        session_rcru.c_id = ".$courseInfo['real_id']." AND
                        session_rcru.id_user     = $userId AND
                        session_rcru.id_session  = $sessionId AND
                        session_rcru.status      = 2 ";

                    $result = Database::query($sql);
                    $row    = Database::store_result($result);

                    //I'm a session admin?
                    if (isset($row) && isset($row[0]) && $row[0]['session_admin_id'] == $userId) {
                        $_courseUser['role'] = 'Professor';
                        $is_courseMember     = false;
                        $is_courseTutor      = false;
                        $is_courseAdmin      = false;
                        $is_courseCoach      = false;
                        $is_sessionAdmin     = true;
                    } else {
                        //Im a coach or a student?
                        $sql    = "SELECT cu.id_user, cu.status FROM $tbl_session_course_user cu
                                   WHERE   c_id = '$courseId' AND
                                   cu.id_user     = '".$userId."' AND
                                   cu.id_session  = '".$sessionId."'
                                   LIMIT 1";
                        $result = Database::query($sql);

                        if (Database::num_rows($result)) {
                            $row = Database::fetch_array($result, 'ASSOC');

                            $session_course_status = $row['status'];

                            switch ($session_course_status) {
                                case '2': // coach - teacher
                                    $_courseUser['role'] = 'Professor';
                                    $is_courseMember     = true;
                                    $is_courseTutor      = true;
                                    $is_courseCoach      = true;
                                    $is_sessionAdmin     = false;

                                    if (api_get_setting('extend_rights_for_coach') == 'true') {
                                        $is_courseAdmin = true;
                                    } else {
                                        $is_courseAdmin = false;
                                    }
                                    Session::write('_courseUser', $_courseUser);
                                    break;
                                case '0': //Student
                                    $_courseUser['role'] = '';
                                    $is_courseMember     = true;
                                    $is_courseTutor      = false;
                                    $is_courseAdmin      = false;
                                    $is_courseCoach      = false;
                                    $is_sessionAdmin     = false;

                                    Session::write('_courseUser', $_courseUser);
                                    break;
                                default:
                                    // Unregister user
                                    $_courseUser['role'] = '';
                                    $is_courseMember     = false;
                                    $is_courseTutor      = false;
                                    $is_courseAdmin      = false;
                                    $is_sessionAdmin     = false;
                                    $is_courseCoach      = false;
                                    Session::erase('_courseUser');
                                    break;
                            }
                        } else {
                            //Unregister user
                            $is_courseMember = false;
                            $is_courseTutor  = false;
                            $is_courseAdmin  = false;
                            $is_sessionAdmin = false;
                            $is_courseCoach  = false;
                            Session::erase('_courseUser');
                        }
                    }
                }
                if ($is_platformAdmin) {
                    $is_courseAdmin = true;
                }
            }
        }

        // Checking the course access
        $is_allowed_in_course = false;
        $_course =& $courseInfo; //fix issue in code rewriting: TODO: do it properly, renaming $_course where appropriate

        if (isset($_course)) {
            switch ($_course['visibility']) {
                case COURSE_VISIBILITY_OPEN_WORLD: //3
                    $is_allowed_in_course = true;
                    break;
                case COURSE_VISIBILITY_OPEN_PLATFORM: //2
                    if (isset($user_id) && !api_is_anonymous($user_id)) {
                        $is_allowed_in_course = true;
                    }
                    break;
                case COURSE_VISIBILITY_REGISTERED: //1
                    if ($is_platformAdmin || $is_courseMember) {
                        $is_allowed_in_course = true;
                    }
                    break;
                case COURSE_VISIBILITY_CLOSED: //0
                    if ($is_platformAdmin || $is_courseAdmin) {
                        $is_allowed_in_course = true;
                    }
                    break;
            }
        }

        if (!$is_platformAdmin) {
            if (!$is_courseMember && isset($courseInfo['registration_code']) && !empty($courseInfo['registration_code'])) {
                $is_courseMember      = false;
                $is_courseAdmin       = false;
                $is_courseTutor       = false;
                $is_courseCoach       = false;
                $is_sessionAdmin      = false;
                $is_allowed_in_course = false;
            }
        }

        // check the session visibility
        if ($is_allowed_in_course == true) {

            //if I'm in a session
            if ($sessionId != 0) {
                if (!$is_platformAdmin) {
                    // admin is not affected to the invisible session mode
                    $session_visibility = api_get_session_visibility($sessionId);

                    switch ($session_visibility) {
                        case SESSION_INVISIBLE:
                            $is_allowed_in_course = false;
                            break;
                    }
                }
            }
        }

        // save the states
        Session::write('is_courseAdmin', $is_courseAdmin);
        Session::write('is_courseMember', $is_courseMember);
        Session::write('is_courseTutor', $is_courseTutor);
        Session::write('is_courseCoach', $is_courseCoach);
        Session::write('is_allowed_in_course', $is_allowed_in_course);
        Session::write('is_sessionAdmin', $is_sessionAdmin);

    } else {
        // continue with the previous values
        /*
        $_courseUser          = Session::read('_courseUser');
        $is_courseAdmin       = Session::read('is_courseAdmin');
        $is_courseTutor       = Session::read('is_courseTutor');
        $is_courseCoach       = Session::read('is_courseCoach');
        $is_courseMember      = Session::read('is_courseMember');
        $is_allowed_in_course = Session::read('is_allowed_in_course');*/
    }
};

/**
 * Deletes the exam_password user extra field *only* to students
 * @todo improve the login hook system
 * @param Request $request
 */
$afterLogin = function (Request $request) use ($app) {
    if (isset($app['current_user']) && isset($app['current_user']['user_id']) && $app['current_user']['status'] == STUDENT) {
        $extraField = new ExtraField('user');
        $extraFieldData = $extraField->get_handler_field_info_by_field_variable('exam_password');
        if ($extraFieldData && !empty($extraFieldData)) {
            $extraField = new ExtraFieldValue('user');
            $extraFieldValue = $extraField->get_values_by_handler_and_field_variable($app['current_user']['user_id'], 'exam_password');
            if (!empty($extraFieldValue)) {
                $extraField->delete_values_by_handler_and_field_id($app['current_user']['user_id'], $extraFieldValue['id']);
            }
        }
    }
};

$removeCidReset = function (Request $request) use ($app) {
    // Deleting course info.
    Session::erase('_cid');
    Session::erase('_real_cid');
    Session::erase('_course');

    if (!empty($_SESSION)) {
        foreach ($_SESSION as $key => $item) {
            if (strpos($key, 'lp_autolunch_') === false) {
                continue;
            } else {
                if (isset($_SESSION[$key])) {
                    Session::erase($key);
                }
            }
        }
    }

    // Deleting session info.
    Session::erase('id_session');
    Session::erase('session_name');

    // Deleting group info.
    Session::erase('_gid');
};

$removeCidResetDependingOfSection = function (Request $request) use ($app, $removeCidReset) {
    $file = $request->get('file');
    if (!empty($file)) {
        $info = pathinfo($file);
        $section = $info['dirname'];

        if ($section == 'admin') {
            $removeCidReset($request);
        }
    }
};

/** / and /index paths */
$app->match('/', 'index.controller:indexAction', 'GET')
    ->assert('type', '.+') //allowing slash "/"
    ->before($removeCidReset)
    ->after($afterLogin);

$app->match('/index', 'index.controller:indexAction', 'GET')
    ->before($removeCidReset)
    ->after($afterLogin)
    ->bind('index');

/** Userportal */
$app->get('/userportal', 'userPortal.controller:indexAction')
    ->before($removeCidReset);

$app->get('/userportal/{type}/{filter}/{page}', 'userPortal.controller:indexAction')
    ->before($removeCidReset)
    ->value('type', 'courses') //default values
    ->value('filter', 'current')
    ->value('page', '1')
    ->bind('userportal');

/** main files */
$app->match('/main/{file}', 'legacy.controller:classicAction', 'GET|POST')
    ->before($removeCidResetDependingOfSection)
    ->before($settingCourseConditions)
    ->before(
        function() use ($app) {
            // Do not load breadcrumbs
            $app['template']->loadBreadcrumb = false;
        })
    ->assert('file', '.+')
    ->assert('type', '.+');


/** Login form */
$app->match('/login', 'index.controller:loginAction', 'GET|POST')
    ->bind('login');


/** Course home instead of courses/MATHS the new URL is web/courses/MATHS  */
$app->match('/courses/{cidReq}/{id_session}/', 'course_home.controller:indexAction', 'GET|POST')
    ->assert('id_session', '\d+')
    ->assert('type', '.+')
    ->before($settingCourseConditions)
    ->before($userPermissionsInsideACourse)
    ->bind('course');

$app->match('/courses/{cidReq}', 'course_home.controller:indexAction', 'GET|POST')
    ->assert('type', '.+')
    ->before($settingCourseConditions)
    ->before($userPermissionsInsideACourse);

// @todo this is the same as above but with out slash (otherwise we will have an httpexception)
$app->match('/courses/{cidReq}/', 'course_home.controller:indexAction', 'GET|POST')
    ->assert('type', '.+')
    ->before($settingCourseConditions)
    ->before($userPermissionsInsideACourse);

// Introduction
$app->match('/introduction/edit/{tool}', 'introduction_tool.controller:editAction', 'GET|POST')
    ->assert('type', '.+')
    ->before($settingCourseConditions)
    ->before($userPermissionsInsideACourse)
     ->bind('introduction_edit');

$app->match('/introduction/delete/{tool}', 'introduction_tool.controller:deleteAction', 'GET|POST')
    ->assert('type', '.+')
    ->before($settingCourseConditions)
    ->before($userPermissionsInsideACourse)
    ->bind('introduction_delete');

/** Course documents */
$app->get('/data/courses/{courseCode}/document/{file}', 'index.controller:getDocumentAction')
    ->assert('file', '.+')
    ->assert('type', '.+');

/** Scorm documents */
$app->get('/data/courses/{courseCode}/scorm/{file}', 'index.controller:getScormDocumentAction')
    ->assert('file', '.+')
    ->assert('type', '.+');

/** Certificates */
$app->match('/certificates/{id}', 'certificate.controller:indexAction', 'GET');

/** Username */
$app->match('/user/{username}', 'user.controller:indexAction', 'GET');

/** Who is online */
/*$app->match('/users/online', 'user.controller:onlineAction', 'GET');
$app->match('/users/online-in-course', 'user.controller:onlineInCourseAction', 'GET');
$app->match('/users/online-in-session', 'user.controller:onlineInSessionAction', 'GET');*/

/** Portal news */
$app->match('/news/{id}', 'news.controller:indexAction', 'GET')
    ->bind('portal_news');

/** LP controller (subscribe users to a LP) */
$app->match('/learnpath/subscribe_users/{lpId}', 'learnpath.controller:indexAction', 'GET|POST')
    ->bind('subscribe_users');

/** Data document_templates files */
$app->get('/data/document_templates/{file}', 'index.controller:getDocumentTemplateAction')
    ->bind('data');

/** Data default_platform_document files */
$app->get('/data/default_platform_document/{file}', 'index.controller:getDefaultPlatformDocumentAction')
    ->assert('file', '.+')
    ->assert('type', '.+');

/** User files */
$app->match('/data/upload/users/{file}', 'index.controller:getUserFile', 'GET|POST')
    ->assert('file', '.+');

/** Group files */
$app->get('/data/upload/groups/{groupId}/{file}', 'index.controller:getGroupFile')
    ->assert('file', '.+')
    ->assert('type', '.+');

/** Admin */

$app->get('/admin/dashboard', 'index.controller:dashboardAction')
    ->assert('type', '.+')
    ->bind('admin_dashboard');

/** Question manager - admin */

$app->get('/admin/questionmanager', 'question_manager.controller:questionManagerIndexAction')
    ->assert('type', '.+')
    ->bind('admin_questionmanager');

$app->match('/admin/questionmanager/questions', 'question_manager.controller:questionsAction', 'GET|POST')
    ->assert('type', '.+')
    ->bind('admin_questions');

$app->match('/admin/questionmanager/questions/{id}/edit', 'question_manager.controller:editQuestionAction', 'GET|POST')
    ->assert('type', '.+')
    ->bind('admin_questions_edit');

$app->match('/admin/questionmanager/questions/{id}', 'exercise_manager.controller:getQuestionAction', 'GET|POST')
    ->assert('type', '.+')
    ->bind('admin_questions_show');

$app->get('/admin/questionmanager/questions/get-categories/{id}', 'question_manager.controller:getCategoriesAction')
    ->bind('admin_questions_get_categories');

$app->get('/admin/questionmanager/questions/get-questions-by-category/{categoryId}', 'question_manager.controller:getQuestionsByCategoryAction')
    ->bind('admin_get_questions_by_category');

$app->match('/admin/questionmanager/categories/{id}/edit', 'question_manager.controller:editCategoryAction', 'GET|POST')
    ->assert('type', '.+')
    ->bind('admin_category_edit');

$app->match('/admin/questionmanager/categories/{id}', 'question_manager.controller:showCategoryAction', 'GET')
    ->assert('id', '\d+')
    ->assert('type', '.+')
    ->bind('admin_category_show');

$app->match('/admin/questionmanager/categories/new', 'question_manager.controller:newCategoryAction', 'GET|POST')
    ->bind('admin_category_new');

$app->match('/admin/questionmanager/categories/{id}/delete', 'question_manager.controller:deleteCategoryAction', 'POST')
    ->bind('admin_category_delete');

/** Editor */
$app->match('/editor/filemanager', 'editor.controller:filemanagerAction', 'GET|POST')
    ->assert('type', '.+')
    ->bind('filemanager');

$app->match('/editor/connector', 'editor.controller:connectorAction', 'GET|POST')
    ->assert('type', '.+')
    ->bind('editor_connector');

/** Exercises */
$app->match('courses/{cidReq}/{id_session}/exercise/question-pool', 'exercise_manager.controller:questionPoolAction', 'POST')
    ->before($settingCourseConditions)
    ->before($userPermissionsInsideACourse)
    ->bind('exercise_question_pool_global');

$app->match('courses/{cidReq}/{id_session}/exercise/{exerciseId}/question-pool', 'exercise_manager.controller:questionPoolAction', 'GET|POST')
    ->assert('exerciseId', '\d+')
    ->before($settingCourseConditions)
    ->before($userCourseAdmin)
    ->before($userPermissionsInsideACourse)
    ->bind('exercise_question_pool');

$app->match('courses/{cidReq}/{id_session}/exercise/{exerciseId}/copy-question/{questionId}', 'exercise_manager.controller:copyQuestionAction', 'GET|POST')
    ->assert('questionId', '\d+')
    ->assert('exerciseId', '\d+')
    ->before($settingCourseConditions)
    ->before($userCourseAdmin)
    ->before($userPermissionsInsideACourse)
    ->bind('exercise_copy_question');

$app->match('courses/{cidReq}/{id_session}/exercise/{exerciseId}/reuse-question/{questionId}', 'exercise_manager.controller:reuseQuestionAction', 'GET|POST')
    ->assert('questionId', '\d+')
    ->assert('exerciseId', '\d+')
    ->before($settingCourseConditions)
    ->before($userCourseAdmin)
    ->before($userPermissionsInsideACourse)
    ->bind('exercise_reuse_question');

/** Course home instead of courses/MATHS the new URL is web/courses/MATHS  */
$app->match('/courses/{cidReq}/{id_session}/exercise/question/{id}', 'exercise_manager.controller:getQuestionAction', 'GET')
    ->assert('id_session', '\d+')
    ->assert('id', '\d+')
    ->assert('type', '.+')
    ->before($settingCourseConditions)
    ->before($userPermissionsInsideACourse)
    ->before($userCourseAdmin)
    ->bind('question_show');

$app->match('/courses/{cidReq}/{id_session}/exercise/{exerciseId}/question/{id}', 'exercise_manager.controller:getQuestionAction', 'GET')
    ->assert('id_session', '\d+')
    ->assert('exerciseId', '\d+')
    ->assert('id', '\d+')
    ->assert('type', '.+')
    ->before($settingCourseConditions)
    ->before($userPermissionsInsideACourse)
    ->before($userCourseAdmin)
    ->bind('exercise_question_show');

$app->match('/courses/{cidReq}/{id_session}/exercise/{exerciseId}/dashboard', 'exercise_manager.controller:dashboardAction', 'GET')
    ->assert('id_session', '\d+')
    ->assert('exerciseId', '\d+')
    ->assert('type', '.+')
    ->before($settingCourseConditions)
    ->before($userPermissionsInsideACourse)
    ->before($userCourseAdmin)
    ->bind('exercise_dashboard');

$app->match('/courses/{cidReq}/{id_session}/exercise/question/{id}/edit', 'exercise_manager.controller:editQuestionAction', 'GET|POST')
    ->assert('type', '.+')
    ->before($settingCourseConditions)
    ->before($userPermissionsInsideACourse)
    ->before($userCourseAdmin)
    ->bind('exercise_question_edit');

$app->match('/admin/administrator/', 'admin.controller:indexAction', 'GET')
    ->assert('type', '.+')
    ->bind('admin_administrator');

$app->match('/ajax', 'model_ajax.controller:indexAction', 'GET')
    ->assert('type', '.+')
    ->bind('model_ajax');

if ($alreadyInstalled) {
    $app->mount('/admin/administrator/roles', new ChamiloLMS\Provider\ReflectionControllerProvider('role.controller'));
    $app->mount('/admin/administrator/question_scores', new ChamiloLMS\Provider\ReflectionControllerProvider('question_score.controller'));
    $app->mount('/admin/administrator/question_score_names', new ChamiloLMS\Provider\ReflectionControllerProvider('question_score_name.controller'));

    $app->mount('/courses/{course}/curriculum/category', new ChamiloLMS\Provider\ReflectionControllerProvider('curriculum_category.controller'));
    $app->mount('/courses/{course}/curriculum/item', new ChamiloLMS\Provider\ReflectionControllerProvider('curriculum_item.controller'));
    $app->mount('/courses/{course}/curriculum/user', new ChamiloLMS\Provider\ReflectionControllerProvider('curriculum_user.controller'));
    $app->mount('/courses/{course}/curriculum', new ChamiloLMS\Provider\ReflectionControllerProvider('curriculum.controller'));
}

