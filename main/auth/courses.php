<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\SequenceResource;

// Delete the globals['_cid'], we don't need it here.
$cidReset = true;

require_once __DIR__.'/../inc/global.inc.php';

// Section for the tabs.
$this_section = SECTION_CATALOG;

if ('true' !== api_get_setting('course_catalog_published')) {
    // Access rights: anonymous users can't do anything useful here.
    api_block_anonymous_users();
}

$userCanViewPage = CoursesAndSessionsCatalog::userCanView();

$defaultAction = CoursesAndSessionsCatalog::is(CATALOG_SESSIONS) ? 'display_sessions' : 'display_courses';
$action = isset($_REQUEST['action']) ? Security::remove_XSS($_REQUEST['action']) : $defaultAction;
$categoryCode = isset($_REQUEST['category_code']) ? Security::remove_XSS($_REQUEST['category_code']) : '';
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

switch ($action) {
    case 'unsubscribe':
        // We are unsubscribing from a course (=Unsubscribe from course).
        $ctok = Security::get_existing_token();

        if (!empty($_GET['sec_token']) && $ctok == $_GET['sec_token']) {
            $auth = new Auth();
            $result = $auth->remove_user_from_course($_GET['course_code']);
            if ($result) {
                Display::addFlash(
                    Display::return_message(get_lang('YouAreNowUnsubscribed'), 'success')
                );
            }
        }

        $currentUrl = api_get_path(WEB_CODE_PATH).'auth/courses.php?category_code='.$categoryCode.'&search_term='.$searchTerm;

        header('Location: '.$currentUrl);
        exit;
    case 'subscribe_course':
        $courseCodeToSubscribe = isset($_GET['course_code']) ? Security::remove_XSS($_GET['course_code']) : '';
        if (api_is_anonymous()) {
            header('Location: '.api_get_path(WEB_CODE_PATH).'auth/inscription.php?c='.$courseCodeToSubscribe);
            exit;
        }
        if (Security::check_token('get')) {
            $courseInfo = api_get_course_info($courseCodeToSubscribe);
            CourseManager::autoSubscribeToCourse($courseCodeToSubscribe);
            $redirectionTarget = CoursesAndSessionsCatalog::generateRedirectUrlAfterSubscription(
                $courseInfo['course_public_url']
            );

            header("Location: $redirectionTarget");
            exit;
        }
        break;
    case 'subscribe_course_validation':
        $toolTitle = get_lang('Subscribe');
        $courseCodeToSubscribe = isset($_GET['course_code']) ? Security::remove_XSS($_GET['course_code']) : '';
        $courseInfo = api_get_course_info($courseCodeToSubscribe);
        if (empty($courseInfo)) {
            header('Location: '.api_get_self());
            exit;
        }
        $message = get_lang('CourseRequiresPassword').' ';
        $message .= $courseInfo['title'].' ('.$courseInfo['visual_code'].') ';

        $action = api_get_self().'?action=subscribe_course_validation&sec_token='.
            Security::getTokenFromSession().'&course_code='.$courseInfo['code'];
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

                $redirectionTarget = CoursesAndSessionsCatalog::generateRedirectUrlAfterSubscription(
                    $courseInfo['course_public_url']
                );

                header("Location: $redirectionTarget");
            } else {
                Display::addFlash(Display::return_message(get_lang('CourseRegistrationCodeIncorrect'), 'warning'));
                header('Location: '.$action);
            }
            exit;
        }

        $template = new Template($toolTitle, true, true, false, false, false);
        $template->assign('content', $content);
        $template->display_one_col_template();
        break;
    case 'subscribe':
        if (!$userCanViewPage) {
            api_not_allowed(true);
        }
        header('Location: '.api_get_self());
        exit;
    case 'display_random_courses':
    case 'display_courses':
    case 'search_course':
        if (!$userCanViewPage) {
            api_not_allowed(true);
        }

        CoursesAndSessionsCatalog::displayCoursesList($action, $searchTerm, $categoryCode);
        exit;
    case 'display_sessions':
        if (!$userCanViewPage) {
            api_not_allowed(true);
        }

        CoursesAndSessionsCatalog::sessionList();
        exit;
    case 'subscribe_to_session':
        if (!$userCanViewPage) {
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
        if ('true' === $registrationAllowed) {
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
                    $userId,
                    $sessionId
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
            } elseif (1 == $count) {
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
        if (!$userCanViewPage) {
            api_not_allowed(true);
        }

        CoursesAndSessionsCatalog::sessionsListByCoursesTag();
        exit;
    case 'search_session_title':
        if (!$userCanViewPage) {
            api_not_allowed(true);
        }

        CoursesAndSessionsCatalog::sessionsListByName();
        exit;
}
