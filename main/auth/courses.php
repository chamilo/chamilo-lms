<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\SequenceResource;

/**
 * Template (front controller in MVC pattern) used for dispatching
 * to the controllers depend on the current action.
 *
 * @author Christian Fasanando <christian1827@gmail.com> - Beeznest
 */
// Delete the globals['_cid'], we don't need it here.
$cidReset = true; // Flag forcing the 'current course' reset

require_once __DIR__.'/../inc/global.inc.php';

$ctok = Security::get_existing_token();

// Get Limit data
$limit = CoursesController::getLimitArray();

// Section for the tabs.
$this_section = SECTION_CATALOG;

if (api_get_setting('course_catalog_published') !== 'true') {
    // Access rights: anonymous users can't do anything useful here.
    api_block_anonymous_users();
}

// For students
$user_can_view_page = true;
if (api_get_setting('allow_students_to_browse_courses') === 'false') {
    $user_can_view_page = false;
}

//For teachers/admins
if (api_is_platform_admin() || api_is_course_admin() || api_is_allowed_to_create_course()) {
    $user_can_view_page = true;
}

// filter actions
$actions = [
    'subscribe',
    'display_courses',
    'display_random_courses',
    'subscribe_user_with_password',
    'display_sessions',
    'subscribe_to_session',
    'search_tag',
    'search_session_title',
    'subscribe_course_validation',
    'subscribe_course',
];

$action = CoursesAndSessionsCatalog::is(CATALOG_SESSIONS) ? 'display_sessions' : 'display_courses';
if (isset($_GET['action']) && in_array($_GET['action'], $actions)) {
    $action = Security::remove_XSS($_GET['action']);
}

$categoryCode = isset($_GET['category_code']) && !empty($_GET['category_code']) ? $_GET['category_code'] : 'ALL';
$searchTerm = isset($_REQUEST['search_term']) ? Security::remove_XSS($_REQUEST['search_term']) : '';

$nameTools = CourseCategory::getCourseCatalogNameTools($action);
if (empty($nameTools)) {
    $nameTools = get_lang('CourseManagement');
} else {
    if (!in_array(
        $action,
        ['display_random_courses', 'display_courses', 'subscribe']
    )) {
        $interbreadcrumb[] = [
            'url' => api_get_path(WEB_CODE_PATH).'auth/courses.php',
            'name' => get_lang('CourseManagement'),
        ];
    }
    $interbreadcrumb[] = ['url' => '#', 'name' => $nameTools];
}

// course description controller object
$courseController = new CoursesController();

// search courses
if (isset($_REQUEST['search_course'])) {
    if (!empty($_REQUEST['sec_token']) && $ctok == $_REQUEST['sec_token']) {
        $courseController->search_courses(
            $searchTerm,
            null,
            null,
            null,
            $limit,
            true
        );
        exit;
    }
}

// We are unsubscribing from a course (=Unsubscribe from course).
if (isset($_GET['unsubscribe'])) {
    if (!empty($_GET['sec_token']) && $ctok == $_GET['sec_token']) {
        $courseController->unsubscribe_user_from_course(
            $_GET['unsubscribe'],
            $searchTerm,
            $categoryCode
        );
    }
}

// We are unsubscribing from a course (=Unsubscribe from course).
if (isset($_POST['unsubscribe'])) {
    if (!empty($_POST['sec_token']) && $ctok == $_POST['sec_token']) {
        $courseController->unsubscribe_user_from_course($_POST['unsubscribe']);
    }
}

switch ($action) {
    case 'subscribe_course':
        if (api_is_anonymous()) {
            header('Location: '.api_get_path(WEB_CODE_PATH).'auth/inscription.php?c='.$courseCodeToSubscribe);
            exit;
        }
        $courseCodeToSubscribe = isset($_GET['subscribe_course']) ? Security::remove_XSS($_GET['subscribe_course']) : '';
        if (Security::check_token('get')) {
            CourseManager::autoSubscribeToCourse($courseCodeToSubscribe);
            header('Location: '.api_get_self());
            exit;
        }
        break;
    case 'subscribe_course_validation':
        $courseCodeToSubscribe = isset($_GET['subscribe_course']) ? Security::remove_XSS($_GET['subscribe_course']) : '';
        $courseInfo = api_get_course_info($courseCodeToSubscribe);
        if (empty($courseInfo)) {
            header('Location: '.api_get_self());
            exit;
        }
        $message = get_lang('CourseRequiresPassword').' ';
        $message .= $courseInfo['title'].' ('.$courseInfo['visual_code'].') ';

        $action = api_get_self().'?action=subscribe_course_validation&sec_token='.
            Security::getTokenFromSession().'&subscribe_course='.$courseCodeToSubscribe;
        $form = new FormValidator(
            'subscribe_user_with_password',
            'post',
            $action
        );
        $form->addHeader($message);
        $form->addElement('hidden', 'sec_token', Security::getTokenFromSession());
        $form->addElement('hidden', 'subscribe_user_with_password', $courseInfo['code']);
        $form->addElement('text', 'course_registration_code');
        $form->addButtonSave(get_lang('SubmitRegistrationCode'));
        $content = $form->returnForm();

        if ($form->validate()) {
            if (sha1($_POST['course_registration_code']) === $courseInfo['registration_code']) {
                CourseManager::autoSubscribeToCourse($_POST['subscribe_user_with_password']);
                header('Location: '.api_get_self());
                exit;
            } else {
                Display::addFlash(Display::return_message(get_lang('CourseRegistrationCodeIncorrect')), 'warning');
                header('Location: '.$action);
                exit;
            }
        }

        $template = new Template(get_lang('Subscribe'), true, true, false, false, false);
        $template->assign('content', $content);
        $template->display_one_col_template();
        break;
    case 'subscribe':
        if (!$user_can_view_page) {
            api_not_allowed(true);
        }
        header('Location: '.api_get_self());
        exit;
        break;
    case 'display_random_courses':
        if (!$user_can_view_page) {
            api_not_allowed(true);
        }

        $courseController->courses_categories($action);
        break;
    case 'display_courses':
        if (!$user_can_view_page) {
            api_not_allowed(true);
        }

        $courseController->courses_categories(
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

        $courseController->sessionList($limit);
        break;
    case 'subscribe_to_session':
        if (!$user_can_view_page) {
            api_not_allowed(true);
        }

        $userId = api_get_user_id();
        $confirmed = isset($_GET['confirm']);
        $sessionId = (int) $_GET['session_id'];

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
                $requirementsData = $repository->checkRequirementsForUser(
                    $sequences,
                    SequenceResource::SESSION_TYPE,
                    $userId
                );

                $continueWithSubscription = $repository->checkSequenceAreCompleted($requirementsData);

                if (!$continueWithSubscription) {
                    header('Location: '.api_get_path(WEB_CODE_PATH).'auth/courses.php');
                    exit;
                }
            }

            SessionManager::subscribeUsersToSession(
                $sessionId,
                [$userId],
                SESSION_VISIBLE_READ_ONLY,
                false
            );

            $coursesList = SessionManager::get_course_list_by_session_id($sessionId);
            $count = count($coursesList);
            $url = '';

            if ($count <= 0) {
                // no course in session -> return to catalog
                $url = api_get_path(WEB_CODE_PATH).'auth/courses.php';
            } elseif ($count == 1) {
                // only one course, so redirect directly to this course
                foreach ($coursesList as $course) {
                    $url = api_get_path(WEB_COURSE_PATH).$course['directory'].'/index.php?id_session='.$sessionId;
                }
            } else {
                $url = api_get_path(WEB_CODE_PATH).'session/index.php?session_id='.$sessionId;
            }
            header('Location: '.$url);
            exit;
        }
        break;
    case 'search_tag':
        if (!$user_can_view_page) {
            api_not_allowed(true);
        }

        $courseController->sessionsListByCoursesTag($limit);
        break;
    case 'search_session_title':
        if (!$user_can_view_page) {
            api_not_allowed(true);
        }

        $courseController->sessionsListByName($limit);
        break;
}
