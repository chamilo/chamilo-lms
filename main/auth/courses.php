<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\SequenceResource;

/**
* Template (front controller in MVC pattern) used for distpaching
 * to the controllers depend on the current action
* @author Christian Fasanando <christian1827@gmail.com> - Beeznest
* @package chamilo.auth
*/
// Delete the globals['_cid'], we don't need it here.
$cidReset = true; // Flag forcing the 'current course' reset

require_once __DIR__.'/../inc/global.inc.php';

$ctok = Security::get_existing_token();

// Get Limit data
$limit = CourseCategory::getLimitArray();

// Section for the tabs.
$this_section = SECTION_CATALOG;

if (api_get_setting('course_catalog_published') !== 'true') {
    // Access rights: anonymous users can't do anything useful here.
    api_block_anonymous_users();
}

$user_can_view_page = false;

//For students
if (api_get_setting('allow_students_to_browse_courses') === 'false') {
    $user_can_view_page = false;
} else {
    $user_can_view_page = true;
}

//For teachers/admins
if (api_is_platform_admin() || api_is_course_admin() || api_is_allowed_to_create_course()) {
    $user_can_view_page = true;
}

// filter actions
$actions = array(
    'sortmycourses',
    'createcoursecategory',
    'subscribe',
    'deletecoursecategory',
    'display_courses',
    'display_random_courses',
    'subscribe_user_with_password',
    'display_sessions',
    'subscribe_to_session',
    'search_tag',
    'search_session'
);

$action = CoursesAndSessionsCatalog::is(CATALOG_SESSIONS) ? 'display_sessions' : 'display_courses';
if (isset($_GET['action']) && in_array($_GET['action'], $actions)) {
    $action = Security::remove_XSS($_GET['action']);
}

$categoryCode = isset($_GET['category_code']) && !empty($_GET['category_code']) ? $_GET['category_code'] : 'ALL';

$nameTools = CourseCategory::getCourseCatalogNameTools($action);
if (empty($nameTools)) {
    $nameTools = get_lang('CourseManagement');
} else {
    if (!in_array($action, array('sortmycourses', 'createcoursecategory', 'display_random_courses', 'display_courses', 'subscribe'))) {
        $interbreadcrumb[] = array(
            'url' => api_get_path(WEB_CODE_PATH).'auth/courses.php',
            'name' => get_lang('CourseManagement'),
        );
    }

    if ($action === 'createcoursecategory') {
        $interbreadcrumb[] = array(
            'url' => api_get_path(WEB_CODE_PATH).'auth/courses.php?action=sortmycourses',
            'name' => get_lang('SortMyCourses'),
        );
    }
    $interbreadcrumb[] = array('url' => '#', 'name' => $nameTools);
}

// course description controller object
$courses_controller = new CoursesController();

// We are moving a course or category of the user up/down the list (=Sort My Courses).
if (isset($_GET['move'])) {
    if (isset($_GET['course'])) {
        $courses_controller->move_course(
            $_GET['move'],
            $_GET['course'],
            $_GET['category']
        );
    }
    if (isset($_GET['category']) && !isset($_GET['course'])) {
        $courses_controller->move_category($_GET['move'], $_GET['category']);
    }
}

// We are moving the course of the user to a different user defined course category (=Sort My Courses).
if (isset($_POST['submit_change_course_category'])) {
    if ($ctok == $_POST['sec_token']) {
        $courses_controller->change_course_category(
            $_POST['course_2_edit_category'],
            $_POST['course_categories']
        );
    }
}

// We edit course category
if (isset($_POST['submit_edit_course_category']) &&
    isset($_POST['title_course_category']) &&
    strlen(trim($_POST['title_course_category'])) > 0
) {
    if ($ctok == $_POST['sec_token']) {
        $courses_controller->edit_course_category(
            $_POST['title_course_category'],
            $_POST['edit_course_category']
        );
    }
}

// we are deleting a course category
if ($action == 'deletecoursecategory' && isset($_GET['id'])) {
    if ($ctok == $_GET['sec_token']) {
        $get_id_cat = intval($_GET['id']);
        $courses_controller->delete_course_category($get_id_cat);
    }
}

// We are creating a new user defined course category (= Create Course Category).
if (isset($_POST['create_course_category']) &&
    isset($_POST['title_course_category']) &&
    strlen(trim($_POST['title_course_category'])) > 0
) {
    if ($ctok == $_POST['sec_token']) {
        $courses_controller->add_course_category($_POST['title_course_category']);
    }
}

// search courses
if (isset($_REQUEST['search_course'])) {
    if ($ctok == $_REQUEST['sec_token']) {
        $courses_controller->search_courses(
            $_REQUEST['search_term'],
            null,
            null,
            null,
            $limit,
            true
        );
    }
}

// Subscribe user to course
if (isset($_REQUEST['subscribe_course'])) {
    if ($ctok == $_GET['sec_token']) {
        $courses_controller->subscribe_user(
            $_GET['subscribe_course'],
            $_GET['search_term'],
            $categoryCode
        );
    }
}

// We are unsubscribing from a course (=Unsubscribe from course).
if (isset($_GET['unsubscribe'])) {
    $search_term = isset($_GET['search_term']) ? $_GET['search_term'] : null;
    if ($ctok == $_GET['sec_token']) {
        $courses_controller->unsubscribe_user_from_course(
            $_GET['unsubscribe'],
            $search_term,
            $categoryCode
        );
    }
}

// We are unsubscribing from a course (=Unsubscribe from course).
if (isset($_POST['unsubscribe'])) {
    if ($ctok == $_POST['sec_token']) {
        $courses_controller->unsubscribe_user_from_course($_POST['unsubscribe']);
    }
}
switch ($action) {
    case 'subscribe_user_with_password':
        $courses_controller->subscribe_user(
            isset($_POST['subscribe_user_with_password']) ? $_POST['subscribe_user_with_password'] : '',
            isset($_POST['search_term']) ? $_POST['search_term'] : '',
            isset($_POST['category_code']) ? $_POST['category_code'] : ''
        );
        break;
    case 'createcoursecategory':
        $courses_controller->categories_list($action);
        break;
    case 'deletecoursecategory':
        $courses_controller->courses_list($action);
        break;
    case 'sortmycourses':
        $courses_controller->courses_list($action);
        break;
    case 'subscribe':
        if (!$user_can_view_page) {
            api_not_allowed(true);
        }
        header('Location: '.api_get_self());
        exit;
        /* if (!CoursesAndSessionsCatalog::is(CATALOG_SESSIONS)) {
            $courses_controller->courses_categories(
                $action,
                $categoryCode,
                null,
                null,
                null,
                $limit
            );
        } else {
            header('Location: ' . api_get_self());
            exit;
        }*/
        break;
    case 'display_random_courses':
        if (!$user_can_view_page) {
            api_not_allowed(true);
        }

        $courses_controller->courses_categories($action);
        break;
    case 'display_courses':
        if (!$user_can_view_page) {
            api_not_allowed(true);
        }

        $courses_controller->courses_categories(
            $action,
            $categoryCode,
            null,
            null,
            null,
            $limit
        );
        break;
    case 'display_sessions':
        if (!$user_can_view_page) {
            api_not_allowed(true);
        }

        $courses_controller->sessionsList($action, $nameTools, $limit);
        break;
    case 'subscribe_to_session':
        if (!$user_can_view_page) {
            api_not_allowed(true);
        }

        $userId = api_get_user_id();
        $confirmed = isset($_GET['confirm']);
        $sessionId = intval($_GET['session_id']);

        if (empty($userId)) {
            api_not_allowed();
            exit;
        }

        if (!$confirmed) {
            $template = new Template(null, false, false, false, false, false);
            $template->assign('session_id', $sessionId);

            $layout = $template->get_template('auth/confirm_session_subscription.tpl');

            echo $template->fetch($layout);
            exit;
        }

        $registrationAllowed = api_get_setting('catalog_allow_session_auto_subscription');
        if ($registrationAllowed === 'true') {
            $entityManager = Database::getManager();
            $repository = $entityManager->getRepository('ChamiloCoreBundle:SequenceResource');

            $sequences = $repository->getRequirements(
                $sessionId,
                SequenceResource::SESSION_TYPE
            );

            if (count($sequences) > 0) {
                $requirementsData = SequenceResourceManager::checkRequirementsForUser(
                    $sequences,
                    SequenceResource::SESSION_TYPE,
                    $userId
                );

                $continueWithSubscription = SequenceResourceManager::checkSequenceAreCompleted($requirementsData);

                if (!$continueWithSubscription) {
                    header('Location: '.api_get_path(WEB_CODE_PATH).'auth/courses.php');
                    exit;
                }
            }

            SessionManager::subscribe_users_to_session(
                $_GET['session_id'],
                array($userId),
                SESSION_VISIBLE_READ_ONLY,
                false
            );

            $coursesList = SessionManager::get_course_list_by_session_id($_GET['session_id']);
            $count = count($coursesList);
            $url = '';

            if ($count <= 0) {
                // no course in session -> return to catalog
                $url = api_get_path(WEB_CODE_PATH).'auth/courses.php';
            } elseif ($count == 1) {
                // only one course, so redirect directly to this course
                foreach ($coursesList as $course) {
                    $url = api_get_path(WEB_COURSE_PATH).$course['directory'].'/index.php?id_session='.intval($_GET['session_id']);
                }
            } else {
                $url = api_get_path(WEB_CODE_PATH).'session/index.php?session_id='.intval($_GET['session_id']);
            }
            header('Location: '.$url);
            exit;
        }
        //else show error message?
        break;
    case 'search_tag':
        if (!$user_can_view_page) {
            api_not_allowed(true);
        }

        $courses_controller->sessionsListByCoursesTag($limit);
        break;
    case 'search_session':
        if (!$user_can_view_page) {
            api_not_allowed(true);
        }

        $courses_controller->sessionListBySearch($limit);
        break;
}
