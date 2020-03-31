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

if ('true' !== api_get_setting('course_catalog_published')) {
    // Access rights: anonymous users can't do anything useful here.
    api_block_anonymous_users();
}

$allowExtraFields = api_get_configuration_value('allow_course_extra_field_in_catalog');

// For students
$userCanViewPage = true;
if ('false' === api_get_setting('allow_students_to_browse_courses')) {
    $userCanViewPage = false;
}

//For teachers/admins
if (api_is_platform_admin() || api_is_course_admin() || api_is_allowed_to_create_course()) {
    $userCanViewPage = true;
}

$defaultAction = CoursesAndSessionsCatalog::is(CATALOG_SESSIONS) ? 'display_sessions' : 'display_courses';
$action = isset($_REQUEST['action']) ? Security::remove_XSS($_REQUEST['action']) : $defaultAction;
$categoryCode = isset($_REQUEST['category_code']) && !empty($_REQUEST['category_code']) ? Security::remove_XSS(
    $_REQUEST['category_code']
) : 'ALL';
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

        $form = new FormValidator('search', 'get', '', null, null, FormValidator::LAYOUT_BOX);
        $form->addHidden('action', 'search_course');
        $form->addText('search_term', get_lang('Title'));

        $jqueryReadyContent = '';
        if ($allowExtraFields) {
            $extraField = new ExtraField('course');
            $returnParams = $extraField->addElements($form, null, [], true);
            $jqueryReadyContent = $returnParams['jquery_ready_content'];
        }

        if ('display_random_courses' === $action) {
            // Random value is used instead limit filter
            $courses = CoursesAndSessionsCatalog::getCoursesInCategory(null, 12);
            $countCoursesInCategory = count($courses);
        } elseif ('search_course' === $action) {
            $values = $_REQUEST;

            if (!empty($values) && $form->hasElement('extra_tags')) {
                $tagElement = $form->getElement('extra_tags');
                if (isset($values['extra_tags']) && !empty($values['extra_tags'])) {

                    $tags = [];
                    foreach ($values['extra_tags'] as $tag) {
                        $tag = Security::remove_XSS($tag);
                        $tags[] = $tag;
                        $tagElement->addOption(
                            $tag,
                            $tag
                        );
                    }
                    $form->setDefaults(
                        [
                            'extra_tags' => $tags,
                        ]
                    );
                }
            }

            $conditions = [];
            $fields = [];

            if ($allowExtraFields) {
                // Parse params.
                foreach ($values as $key => $value) {
                    if (substr($key, 0, 6) !== 'extra_' && substr($key, 0, 7) !== '_extra_') {
                        continue;
                    }
                    if (!empty($value)) {
                        $fields[$key] = $value;
                    }
                }

                $extraFields = $extraField->get_all(['visible_to_self = ? AND filter = ?' => [1, 1]], 'option_order');
                $extraFields = array_column($extraFields, 'variable');
                $filter = new stdClass();
                foreach ($fields as $variable => $col) {
                    if (isset($values[$variable]) && !empty($values[$variable]) &&
                        in_array(str_replace('extra_', '', $variable), $extraFields)
                    ) {
                        $rule = new stdClass();
                        $rule->field = $variable;
                        $rule->op = 'in';
                        $data = $col;
                        if (is_array($data) && array_key_exists($variable, $data)) {
                            $data = $col;
                        }
                        $rule->data = $data;
                        $filter->rules[] = $rule;
                        $filter->groupOp = 'AND';
                    }
                }
                $result = $extraField->getExtraFieldRules($filter);
                $conditionArray = $result['condition_array'];

                $whereCondition = '';
                $extraCondition = '';
                if (!empty($conditionArray)) {
                    $extraCondition = ' ( ';
                    $extraCondition .= implode(' AND ', $conditionArray);
                    $extraCondition .= ' ) ';
                }
                $whereCondition .= $extraCondition;
                $options = ['where' => $whereCondition, 'extra' => $result['extra_fields']];
                $conditions = $extraField->parseConditions($options, 'course');
            }

            $courses = CoursesAndSessionsCatalog::searchCourses($searchTerm, $limit, false, $conditions);
            $countCoursesInCategory = CourseCategory::countCoursesInCategory('ALL', $searchTerm, true, $conditions);
        } else {
            if (!isset($categoryCode)) {
                $categoryCode = $listCategories['ALL']['code']; // by default first category
            }
            $courses = CoursesAndSessionsCatalog::getCoursesInCategory($categoryCode, null, $limit);
            $countCoursesInCategory = CourseCategory::countCoursesInCategory($categoryCode, $searchTerm);
        }

        $showCourses = CoursesAndSessionsCatalog::showCourses();
        $showSessions = CoursesAndSessionsCatalog::showSessions();
        $pageCurrent = isset($_GET['pageCurrent']) ? (int) $_GET['pageCurrent'] : 1;
        $pageLength = isset($_GET['pageLength']) ? (int) $_GET['pageLength'] : CoursesAndSessionsCatalog::PAGE_LENGTH;
        $pageTotal = (int) ceil($countCoursesInCategory / $pageLength);

        $url = CoursesAndSessionsCatalog::getCatalogUrl(1, $pageLength, 'ALL', 0, 'search_course', $fields);
        $form->setAttribute('action', $url);

        // getting all the courses to which the user is subscribed to
        $user_courses = CourseManager::getCoursesByUserCourseCategory($userId);

        $user_coursecodes = [];
        // we need only the course codes as these will be used to match against the courses of the category
        if ('' != $user_courses) {
            foreach ($user_courses as $key => $value) {
                $user_coursecodes[] = $value['code'];
            }
        }

        if (api_is_drh()) {
            $coursesDrh = CourseManager::get_courses_followed_by_drh($userId);
            foreach ($coursesDrh as $course) {
                $user_coursecodes[] = $course['code'];
            }
        }

        $catalogShowCoursesSessions = 0;
        $showCoursesSessions = (int) api_get_setting('catalog_show_courses_sessions');
        if ($showCoursesSessions > 0) {
            $catalogShowCoursesSessions = $showCoursesSessions;
        }

        $catalogPagination = '';
        if ($pageTotal > 1) {
            $catalogPagination = CoursesAndSessionsCatalog::getCatalogPagination(
                $pageCurrent,
                $pageLength,
                $pageTotal,
                $categoryCode,
                $action,
                $fields
            );
        }
        /*$date = date('Y-m-d');
        if ($showSessions && isset($_POST['date'])) {
            $date = $_POST['date'];
        }*/
        $userInfo = api_get_user_info();

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
            if (!isset($_GET['hidden_links']) || 1 != intval($_GET['hidden_links'])) {
                $htmlHeadXtra[] = '<script>
                $(function () {
                    '.$jqueryReadyContent.'
                });
                </script>';

                $form->addButtonSearch(get_lang('Search'));
                $content .= $form->returnForm();
            }

            $content .= '</div>';
            $content .= '<div class="col-md-'.($showSessions ? '4' : '6').'">';
            //$listCategories = CoursesAndSessionsCatalog::getCourseCategoriesTree();
            $categoriesSelect = CoursesAndSessionsCatalog::getOptionSelect($listCategories, $categoryCode);

            $webAction = api_get_path(WEB_CODE_PATH).'auth/courses.php';

            $form = '<form action="'.$webAction.'" method="GET">';
            $form .= '<input type="hidden" name="action" value="display_courses">';
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
                $content .= '<p><strong>'.get_lang('SearchResultsFor').' '.$searchTerm.'</strong><br />';
            }

            $showTeacher = 'true' === api_get_setting('display_teacher_in_courselist');
            $ajax_url = api_get_path(WEB_AJAX_PATH).'course.ajax.php?a=add_course_vote';
            $user_id = api_get_user_id();
            $categoryListFromDatabase = CourseCategory::getAllCategories();

            $categoryList = [];
            if (!empty($categoryListFromDatabase)) {
                foreach ($categoryListFromDatabase as $categoryItem) {
                    $categoryList[$categoryItem['code']] = $categoryItem['name'];
                }
            }

            if ($allowExtraFields) {
                $extraFieldValues = new ExtraFieldValue('course');
                $em = Database::getManager();
                $fieldsRepo = $em->getRepository('ChamiloCoreBundle:ExtraField');
                $fieldTagsRepo = $em->getRepository('ChamiloCoreBundle:ExtraFieldRelTag');

                $tagField = $fieldsRepo->findOneBy(
                    [
                        'extraFieldType' => \Chamilo\CoreBundle\Entity\ExtraField::COURSE_FIELD_TYPE,
                        'variable' => 'tags',
                    ]
                );
            }

            $courseUrl = api_get_path(WEB_COURSE_PATH);
            $hideRating = api_get_configuration_value('hide_course_rating');
            if (!empty($courses)) {
                foreach ($courses as &$course) {
                    $courseId = $course['real_id'];
                    if (COURSE_VISIBILITY_HIDDEN == $course['visibility']) {
                        continue;
                    }
                    $courseTags = [];
                    if ($allowExtraFields && !is_null($tagField)) {
                        $courseTags = $fieldTagsRepo->getTags($tagField, $courseId);
                    }

                    $userRegisteredInCourse = CourseManager::is_user_subscribed_in_course($user_id, $course['code']);
                    $userRegisteredInCourseAsTeacher = CourseManager::is_course_teacher($user_id, $course['code']);
                    $userRegistered = $userRegisteredInCourse && $userRegisteredInCourseAsTeacher;

                    $course_public = COURSE_VISIBILITY_OPEN_WORLD == $course['visibility'];
                    $course_open = COURSE_VISIBILITY_OPEN_PLATFORM == $course['visibility'];
                    $course_private = COURSE_VISIBILITY_REGISTERED == $course['visibility'];
                    $courseClosed = COURSE_VISIBILITY_CLOSED == $course['visibility'];
                    $course_subscribe_allowed = 1 == $course['subscribe'];
                    $course_unsubscribe_allowed = 1 == $course['unsubscribe'];
                    $count_connections = $course['count_connections'];
                    $creation_date = substr($course['creation_date'], 0, 10);

                    // display the course bloc
                    $course['category_title'] = '';
                    if (isset($course['category'])) {
                        $course['category_title'] = isset($categoryList[$course['category']]) ? $categoryList[$course['category']] : '';
                    }

                    // Display thumbnail
                    $course['thumbnail'] = CoursesAndSessionsCatalog::returnThumbnail($course);
                    $course['description_button'] = CourseManager::returnDescriptionButton($course);
                    $subscribeButton = CoursesAndSessionsCatalog::return_register_button(
                        $course,
                        $stok,
                        $categoryCode,
                        $searchTerm
                    );

                    // Start buy course validation
                    // display the course price and buy button if the buycourses plugin is enabled and this course is configured
                    $plugin = BuyCoursesPlugin::create();
                    $isThisCourseInSale = $plugin->buyCoursesForGridCatalogValidator(
                        $courseId,
                        BuyCoursesPlugin::PRODUCT_TYPE_COURSE
                    );

                    $separator = '';
                    if ($isThisCourseInSale) {
                        // set the Price label
                        $separator = $isThisCourseInSale['html'];
                        // set the Buy button instead register.
                        if ($isThisCourseInSale['verificator']) {
                            $subscribeButton = $plugin->returnBuyCourseButton(
                                $courseId,
                                BuyCoursesPlugin::PRODUCT_TYPE_COURSE
                            );
                        }
                    }

                    // end buy course validation
                    $course['title_formatted'] = CoursesAndSessionsCatalog::return_title($course, $userRegisteredInCourse);
                    $course['rating'] = '';
                    if ($hideRating === false) {
                        $ajax_url = api_get_path(WEB_AJAX_PATH).'course.ajax.php?a=add_course_vote';
                        $rating = Display::return_rating_system(
                            'star_'.$course['real_id'],
                            $ajax_url.'&course_id='.$course['real_id'],
                            $course['point_info']
                        );
                        $course['rating'] = '<div class="ranking">'.$rating.'</div>';
                    }

                    if ($showTeacher) {
                        $course['teacher_info'] = CoursesAndSessionsCatalog::return_teacher($course);
                    }

                    // display button line
                    $course['buy_course'] = $separator;

                    if ($allowExtraFields) {
                        $course['extra_data'] = '';
                        $values = $extraFieldValues->getAllValuesForAnItem($courseId, true, true);
                        foreach ($values as $valueItem) {
                            /** @var \Chamilo\CoreBundle\Entity\ExtraFieldValues $value */
                            $value = $valueItem['value'];
                            if ($value) {
                                $data = $value->getValue();
                                if (!empty($data)) {
                                    $course['extra_data'] .= $value->getField()->getDisplayText().': ';
                                    switch ($value->getField()->getFieldType()) {
                                        case ExtraField::FIELD_TYPE_CHECKBOX:
                                            if ($value->getValue() == 1) {
                                                $course['extra_data'] .= get_lang('Yes').'<br />';
                                            } else {
                                                $course['extra_data'] .= get_lang('No').'<br />';
                                            }
                                            break;
                                        default:
                                            $course['extra_data'] .= $value->getValue().'<br />';
                                            break;
                                    }
                                }
                            }
                        }

                        $course['extra_data_tags'] = [];
                        if (!empty($courseTags)) {
                            /** @var \Chamilo\CoreBundle\Entity\Tag $tag */
                            foreach ($courseTags as $tag) {
                                $tagUrl = Display::url($tag->getTag(), $url.'&extra_tags%5B%5D='.$tag->getTag());
                                $course['extra_data_tags'][] = $tagUrl;
                            }
                        }
                    }

                    // if user registered as student
                    if ($userRegisteredInCourse) {
                        $course['already_registered_formatted'] = Display::url(
                            Display::returnFontAwesomeIcon('external-link').'&nbsp;'.
                            get_lang('GoToCourse'),
                            $courseUrl.$course['directory'].'/index.php?id_session=0',
                            ['class' => 'btn btn-primary']
                        );
                        if (!$courseClosed) {
                            if ($course_unsubscribe_allowed) {
                                $course['unregister_formatted'] = CoursesAndSessionsCatalog::return_unregister_button(
                                    $course,
                                    $stok,
                                    $searchTerm,
                                    $categoryCode
                                );
                            }
                        }
                    } elseif ($userRegisteredInCourseAsTeacher) {
                        // if user registered as teacher
                        if ($course_unsubscribe_allowed) {
                            $course['unregister_formatted'] = CoursesAndSessionsCatalog::return_unregister_button(
                                $course,
                                $stok,
                                $searchTerm,
                                $categoryCode
                            );
                        }
                    } else {
                        // if user not registered in the course
                        if (!$courseClosed) {
                            if (!$course_private) {
                                if ($course_subscribe_allowed) {
                                    $course['subscribe_formatted'] = $subscribeButton;
                                }
                            }
                        }
                    }
                }
            } else {
                if (!isset($_REQUEST['subscribe_user_with_password']) &&
                    !isset($_REQUEST['subscribe_course'])
                ) {
                    Display::addFlash(Display::return_message(
                        get_lang('ThereAreNoCoursesInThisCategory'),
                        'warning'
                    ));
                }
            }
        }

        $template = new Template($toolTitle, true, true, false, false, false);
        $template->assign('content', $content);
        $template->assign('courses', $courses);
        $template->assign('pagination', $catalogPagination);
        $template->display($template->get_template('catalog/course_catalog.tpl'));
        exit;
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
