<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\SequenceResource;

// Delete the globals['_cid'], we don't need it here.
$cidReset = true;

require_once __DIR__.'/../inc/global.inc.php';

$ctok = Security::get_existing_token();

// Get Limit data
$limit = CoursesAndSessionsCatalog::getLimitArray();

// Section for the tabs.
$this_section = SECTION_CATALOG;

if (api_get_setting('course_catalog_published') !== 'true') {
    // Access rights: anonymous users can't do anything useful here.
    api_block_anonymous_users();
}

// For students
$userCanViewPage = true;
if (api_get_setting('allow_students_to_browse_courses') === 'false') {
    $userCanViewPage = false;
}

//For teachers/admins
if (api_is_platform_admin() || api_is_course_admin() || api_is_allowed_to_create_course()) {
    $userCanViewPage = true;
}

$defaultAction = CoursesAndSessionsCatalog::is(CATALOG_SESSIONS) ? 'display_sessions' : 'display_courses';
$action = isset($_REQUEST['action']) ? Security::remove_XSS($_REQUEST['action']) : $defaultAction;
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

$auth = new Auth();
$userId = api_get_user_id();
$currentUrl = api_get_path(WEB_CODE_PATH).'auth/courses.php?category_code='.$categoryCode.'&search_term='.$searchTerm;
$content = '';
$toolTitle = get_lang('CourseCatalog');

switch ($action) {
    case 'unsubscribe':
        // We are unsubscribing from a course (=Unsubscribe from course).
        if (!empty($_GET['sec_token']) && $ctok == $_GET['sec_token']) {
            $result = $auth->remove_user_from_course($_GET['course_code']);
            if ($result) {
                Display::addFlash(
                    Display::return_message(get_lang('YouAreNowUnsubscribed'))
                );
            }
        }

        header('Location: '.$currentUrl);
        exit;
        break;
    case 'subscribe_course':
        if (api_is_anonymous()) {
            header('Location: '.api_get_path(WEB_CODE_PATH).'auth/inscription.php?c='.$courseCodeToSubscribe);
            exit;
        }
        $courseCodeToSubscribe = isset($_GET['course_code']) ? Security::remove_XSS($_GET['course_code']) : '';
        if (Security::check_token('get')) {
            CourseManager::autoSubscribeToCourse($courseCodeToSubscribe);
            header('Location: '.api_get_self());
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
                header('Location: '.api_get_self());
                exit;
            } else {
                Display::addFlash(Display::return_message(get_lang('CourseRegistrationCodeIncorrect')), 'warning');
                header('Location: '.$action);
                exit;
            }
        }

        break;
    case 'subscribe':
        if (!$userCanViewPage) {
            api_not_allowed(true);
        }
        header('Location: '.api_get_self());
        exit;
        break;
    case 'display_random_courses':
    case 'display_courses':
    case 'search_course':
        if (!$userCanViewPage) {
            api_not_allowed(true);
        }
        $listCategories = CoursesAndSessionsCatalog::getCourseCategoriesTree();
        $countCoursesInCategory = CourseCategory::countCoursesInCategory($categoryCode, $searchTerm);
        if ($action === 'display_random_courses') {
            // Random value is used instead limit filter
            $browse_courses_in_category = CoursesAndSessionsCatalog::getCoursesInCategory(null, 12);
            $countCoursesInCategory = count($data['browse_courses_in_category']);
        } elseif($action === 'search_course' && $categoryCode !== 'ALL') {
            $browse_courses_in_category = CoursesAndSessionsCatalog::search_courses(
                $searchTerm,
                $limit
            );
            $countCoursesInCategory = CourseCategory::countCoursesInCategory('ALL', $searchTerm);
        } else {
            if (!isset($categoryCode)) {
                $categoryCode = $listCategories['ALL']['code']; // by default first category
            }
            $browse_courses_in_category = CoursesAndSessionsCatalog::getCoursesInCategory($categoryCode, null, $limit);
        }

        $list_categories = $listCategories;
        $code = Security::remove_XSS($categoryCode);

        // getting all the courses to which the user is subscribed to
        $user_courses = $auth->get_courses_of_user($userId);

        $user_coursecodes = [];
        // we need only the course codes as these will be used to match against the courses of the category
        if ($user_courses != '') {
            foreach ($user_courses as $key => $value) {
                $user_coursecodes[] = $value['code'];
            }
        }

        if (api_is_drh()) {
            $courses = CourseManager::get_courses_followed_by_drh($userId);
            foreach ($courses as $course) {
                $user_coursecodes[] = $course['code'];
            }
        }

        $catalogShowCoursesSessions = 0;
        $showCoursesSessions = (int) api_get_setting('catalog_show_courses_sessions');
        if ($showCoursesSessions > 0) {
            $catalogShowCoursesSessions = $showCoursesSessions;
        }

        $showCourses = CoursesAndSessionsCatalog::showCourses();
        $showSessions = CoursesAndSessionsCatalog::showSessions();
        $pageCurrent = isset($_GET['pageCurrent']) ? (int) $_GET['pageCurrent'] : 1;
        $pageLength = isset($_GET['pageLength']) ? (int) $_GET['pageLength'] : CoursesAndSessionsCatalog::PAGE_LENGTH;
        $pageTotal = (int) ceil($countCoursesInCategory / $pageLength);
        $catalogPagination = '';
        if ($pageTotal > 1) {
            $catalogPagination = CourseCategory::getCatalogPagination(
                $pageCurrent,
                $pageLength,
                $pageTotal,
                $categoryCode,
                $action
            );
        }
        $date = date('Y-m-d');
        if ($showSessions && isset($_POST['date'])) {
            $date = $_POST['date'];
        }
        $userInfo = api_get_user_info();
        $code = isset($code) ? $code : null;

        $extraDate = '';
        if ($showSessions) {
            $extraDate = "
            $('#date').datepicker({
                dateFormat: 'yy-mm-dd'
            });";
        }

        $htmlHeadXtra[] = "
        <script>
            $(function() {
                $('.star-rating li a').on('click', function(event) {
                    var id = $(this).parents('ul').attr('id');
                    $('#vote_label2_' + id).html('".get_lang('Loading')."');
                    $.ajax({
                        url: $(this).attr('data-link'),
                        success: function(data) {
                            $('#rating_wrapper_'+id).html(data);
                        }
                    });
                });

                var getSessionId = function (el) {
                    var parts = el.id.split('_');

                    return parseInt(parts[1], 10);
                };

                $extraDate
            });
        </script>";

        $stok = Security::get_token();
        $content = CoursesAndSessionsCatalog::getTabList(1);
        $content .= '<div class="row">
        <div class="col-md-12">
            <div class="search-courses">
                <div class="row">';
        if ($showCourses) {
            $content .= '<div class="col-md-'.($showSessions ? '4' : '6').'">';
            if (!isset($_GET['hidden_links']) || intval($_GET['hidden_links']) != 1) {
                $content .= '
                <form method="post"
                      action="'.CourseCategory::getCourseCategoryUrl(1, $pageLength, 'ALL', 0, 'search_course').'">
                    <input type="hidden" name="sec_token" value="'.$stok.'">
                    <label>'.get_lang('Search').'</label>
                    <div class="input-group">
                        <input class="form-control" type="text" name="search_term"
                               value="'.(empty($_POST['search_term']) ? '' : api_htmlentities($searchTerm)).'"/>
                        <div class="input-group-btn">
                            <button class="btn btn-default" type="submit">
                                <em class="fa fa-search"></em>'.get_lang('Search').'
                            </button>
                        </div>
                    </div>
                </form>';
            }

            $content .= '</div>';
            $content .= '<div class="col-md-'.($showSessions ? '4' : '6').'">';
            $listCategories = CoursesAndSessionsCatalog::getCourseCategoriesTree();
            $categoriesSelect = CoursesAndSessionsCatalog::getOptionSelect($listCategories, $categoryCode);

            $webAction = api_get_path(WEB_CODE_PATH).'auth/courses.php';
            $form = '<form action="'.$webAction.'" method="GET">';
            $form .= '<input type="hidden" name="action" value="'.$action.'">';
            $form .= '<input type="hidden" name="pageCurrent" value="'.$pageCurrent.'">';
            $form .= '<input type="hidden" name="pageLength" value="'.$pageLength.'">';
            $form .= '<div class="form-group">';
            $form .= '<label>'.get_lang('CourseCategories').'</label>';
            $form .= $categoriesSelect;
            $form .= '</div>';
            $form .= '</form>';
            $content .= $form;
            $content .= '</div>';
        }

        $content .= '</div></div></div></div>';

        if ($showCourses) {
            if (!empty($searchTerm)) {
                $content .= "<p><strong>".get_lang('SearchResultsFor')." ".$searchTerm."</strong><br />";
            }

            $showTeacher = api_get_setting('display_teacher_in_courselist') === 'true';
            $ajax_url = api_get_path(WEB_AJAX_PATH).'course.ajax.php?a=add_course_vote';
            $user_id = api_get_user_id();
            $categoryListFromDatabase = CourseCategory::getAllCategories();

            $categoryList = [];
            if (!empty($categoryListFromDatabase)) {
                foreach ($categoryListFromDatabase as $categoryItem) {
                    $categoryList[$categoryItem['code']] = $categoryItem['name'];
                }
            }

            if (!empty($browse_courses_in_category)) {
                $content .= '<div class="grid-courses row">';
                foreach ($browse_courses_in_category as $course) {
                    $course_hidden = $course['visibility'] == COURSE_VISIBILITY_HIDDEN;

                    if ($course_hidden) {
                        continue;
                    }

                    $userRegisteredInCourse = CourseManager::is_user_subscribed_in_course($user_id, $course['code']);
                    $userRegisteredInCourseAsTeacher = CourseManager::is_course_teacher($user_id, $course['code']);
                    $userRegistered = $userRegisteredInCourse && $userRegisteredInCourseAsTeacher;

                    $course_public = $course['visibility'] == COURSE_VISIBILITY_OPEN_WORLD;
                    $course_open = $course['visibility'] == COURSE_VISIBILITY_OPEN_PLATFORM;
                    $course_private = $course['visibility'] == COURSE_VISIBILITY_REGISTERED;
                    $course_closed = $course['visibility'] == COURSE_VISIBILITY_CLOSED;

                    $course_subscribe_allowed = $course['subscribe'] == 1;
                    $course_unsubscribe_allowed = $course['unsubscribe'] == 1;
                    $count_connections = $course['count_connections'];
                    $creation_date = substr($course['creation_date'], 0, 10);

                    // display the course bloc
                    $html = '<div class="col-xs-12 col-sm-6 col-md-4"><div class="items items-courses">';

                    $course['category_title'] = '';
                    if (isset($course['category'])) {
                        $course['category_title'] = isset($categoryList[$course['category']]) ? $categoryList[$course['category']] : '';
                    }

                    // Display thumbnail
                    $html .= CoursesAndSessionsCatalog::returnThumbnail($course, $userRegistered);

                    $separator = null;
                    $subscribeButton = CoursesAndSessionsCatalog::return_register_button($course, $stok, $code, $searchTerm);
                    // Start buy course validation
                    // display the course price and buy button if the buycourses plugin is enabled and this course is configured
                    $plugin = BuyCoursesPlugin::create();
                    $isThisCourseInSale = $plugin->buyCoursesForGridCatalogValidator(
                        $course['real_id'],
                        BuyCoursesPlugin::PRODUCT_TYPE_COURSE
                    );

                    if ($isThisCourseInSale) {
                        // set the Price label
                        $separator = $isThisCourseInSale['html'];
                        // set the Buy button instead register.
                        if ($isThisCourseInSale['verificator']) {
                            $subscribeButton = $plugin->returnBuyCourseButton(
                                $course['real_id'],
                                BuyCoursesPlugin::PRODUCT_TYPE_COURSE
                            );
                        }
                    }
                    // end buy course validation

                    // display course title and button bloc
                    $html .= '<div class="description">';
                    $html .= CoursesAndSessionsCatalog::return_title($course, $userRegisteredInCourse);

                    if ($showTeacher) {
                        $html .= CoursesAndSessionsCatalog::return_teacher($course);
                    }

                    // display button line
                    $html .= '<div class="toolbar row">';
                    $html .= $separator ? '<div class="col-sm-4">'.$separator.'</div>' : '';
                    $html .= '<div class="col-sm-8">';
                    // if user registered as student
                    if ($userRegisteredInCourse) {
                        $html .= CoursesAndSessionsCatalog::return_already_registered_label('student');
                        if (!$course_closed) {
                            if ($course_unsubscribe_allowed) {
                                $html .= CoursesAndSessionsCatalog::return_unregister_button($course, $stok, $searchTerm, $code);
                            }
                        }
                    } elseif ($userRegisteredInCourseAsTeacher) {
                        // if user registered as teacher
                        if ($course_unsubscribe_allowed) {
                            $html .= CoursesAndSessionsCatalog::return_unregister_button($course, $stok, $searchTerm, $code);
                        }
                    } else {
                        // if user not registered in the course
                        if (!$course_closed) {
                            if (!$course_private) {
                                if ($course_subscribe_allowed) {
                                    $html .= $subscribeButton;
                                }
                            }
                        }
                    }
                    $html .= '</div>';
                    $html .= '</div>';
                    $html .= '</div>';
                    $html .= '</div>';
                    $html .= '</div>';
                    $content .= $html;
                }
                $content .= '</div>';
            } else {
                if (!isset($_REQUEST['subscribe_user_with_password']) &&
                    !isset($_REQUEST['subscribe_course'])
                ) {
                    $content .= Display::return_message(
                        get_lang('ThereAreNoCoursesInThisCategory'),
                        'warning'
                    );
                }
            }
        }

        $content .= '<div class="col-md-12">';
        $content .= $catalogPagination;
        $content .= '</div>';
        break;
    case 'display_sessions':
        if (!$userCanViewPage) {
            api_not_allowed(true);
        }

        CoursesAndSessionsCatalog::sessionList($limit);
        exit;
        break;
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
        if (!$userCanViewPage) {
            api_not_allowed(true);
        }

        CoursesAndSessionsCatalog::sessionsListByCoursesTag($limit);
        exit;
        break;
    case 'search_session_title':
        if (!$userCanViewPage) {
            api_not_allowed(true);
        }

        CoursesAndSessionsCatalog::sessionsListByName($limit);

        break;
}

$template = new Template($toolTitle, true, true, false, false, false);
$template->assign('content', $content);
$template->display_one_col_template();
