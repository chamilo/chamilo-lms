<?php
/* For licensing terms, see /license.txt */

/**
 * View (MVC patter) for courses categories.
 *
 * @author Christian Fasanando <christian1827@gmail.com> - Beeznest
 *
 * @package chamilo.auth
 */
if (isset($_REQUEST['action']) && Security::remove_XSS($_REQUEST['action']) !== 'subscribe') {
    $stok = Security::get_token();
} else {
    $stok = Security::getTokenFromSession();
}

$action = !empty($_REQUEST['action']) ? Security::remove_XSS($_REQUEST['action']) : 'display_courses';
global $actions;
$action = in_array($action, $actions) ? $action : 'display_courses';

$showCourses = CoursesAndSessionsCatalog::showCourses();
$showSessions = CoursesAndSessionsCatalog::showSessions();
$pageCurrent = isset($pageCurrent) ? $pageCurrent : isset($_GET['pageCurrent']) ? (int) $_GET['pageCurrent'] : 1;
$pageLength = isset($pageLength) ? $pageLength : isset($_GET['pageLength']) ? (int) $_GET['pageLength'] : CoursesAndSessionsCatalog::PAGE_LENGTH;
$pageTotal = (int) ceil((int) $countCoursesInCategory / $pageLength);
$cataloguePagination = $pageTotal > 1 ? CourseCategory::getCatalogPagination($pageCurrent, $pageLength, $pageTotal) : '';
$searchTerm = isset($_REQUEST['search_term']) ? Security::remove_XSS($_REQUEST['search_term']) : '';
$codeType = isset($_REQUEST['category_code']) ? Security::remove_XSS($_REQUEST['category_code']) : '';

$date = date('Y-m-d');
if ($showSessions && isset($_POST['date'])) {
    $date = $_POST['date'];
}
$userInfo = api_get_user_info();
$code = isset($code) ? $code : null;
$search = null;
$select = null;
$message = null;
?>
<script>
    $(document).ready( function() {
        $('#stars li').on('click', function(event) {
            var id = $(this).parents('ul').attr('id');
            $('#vote_label2_' + id).html("<?php echo get_lang('Loading'); ?>");
            $.ajax({
                url: $(this).attr('data-link'),
                success: function(data) {
                    $("#rating_wrapper_"+id).html(data);
                    if (data == 'added') {
                        //$('#vote_label2_' + id).html("{'Saved'|get_lang}");
                    }
                    if (data == 'updated') {
                        //$('#vote_label2_' + id).html("{'Saved'|get_lang}");
                    }
                }
            });
        });
        var getSessionId = function (el) {
            var parts = el.id.split('_');
            return parseInt(parts[1], 10);
        };

        <?php if ($showSessions) {
    ?>
        $('#date').datepicker({
            dateFormat: 'yy-mm-dd'
        });
        <?php
} ?>
    });
</script>

<?php

    if (!isset($_GET['hidden_links']) || intval($_GET['hidden_links']) != 1) {
        $term = empty($_POST['search_term']) ? '' : api_htmlentities($searchTerm);
        $urlAction = CourseCategory::getCourseCategoryUrl(1, $pageLength, 'ALL', 0, 'subscribe');
        $formSearch = new FormValidator('search_catalog', 'post', $urlAction, null, [], FormValidator::LAYOUT_BOX_SEARCH);
        $formSearch->addHidden('sec_token', $stok);
        $formSearch->addHidden('search_course', 1);
        $formSearch->addText('search_term', get_lang('Search'), false, ['value' => $term, 'icon' => 'search'])->setButton(true);
        //$formSearch->defaultRenderer()->setElementTemplate($formSearch->getDefaultElementTemplate(),'search_term');
        $search = $formSearch->returnForm();

        $webAction = api_get_path(WEB_CODE_PATH).'auth/courses.php';
        $formSelect = new FormValidator('select_category', 'get', $webAction, null, []);
        $formSelect->addHidden('action', $action);
        $formSelect->addHidden('pageCurrent', $pageCurrent);
        $formSelect->addHidden('pageLength', $pageLength);
        $options = [];

        foreach ($browse_course_categories[0] as $category) {
            $categoryCode = $category['code'];
            $countCourse = $category['count_courses'];
            if (empty($countCourse)) {
                continue;
            }

            $options[$categoryCode] = $category['name'].' ('.$countCourse.')';
        }

        if (empty($codeType)) {
            $codeType = 'ALL';
        }
        $formSelect->addSelect('category_code', get_lang('Categories'), $options, ['onchange' => 'submit();'])->setSelected($codeType);
        $select = $formSelect->returnForm();
    }

if ($showCourses && $action != 'display_sessions') {
    if (!empty($message)) {
        $message = Display::return_message($message, 'confirmation', false);
    }
    if (!empty($error)) {
        $message = Display::return_message($error, 'error', false);
    }

    if (!empty($content)) {
        $message = $content;
    }

    if (!empty($searchTerm)) {
        $message = "<p><strong>".get_lang('SearchResultsFor')." ".$searchTerm."</strong><br />";
    }

    $showTeacher = api_get_setting('display_teacher_in_courselist') === 'true';
    $ajax_url = api_get_path(WEB_AJAX_PATH).'course.ajax.php?a=add_course_vote';
    $user_id = api_get_user_id();
    $categoryListFromDatabase = CourseCategory::getCategories();

    $courseList = [];
    $categoryList = [];
    if (!empty($categoryListFromDatabase)) {
        foreach ($categoryListFromDatabase as $categoryItem) {
            $categoryList[$categoryItem['code']] = $categoryItem['name'];
        }
    }

    if (!empty($browse_courses_in_category)) {
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

            $course['category_title'] = '';
            if (isset($course['category'])) {
                $course['category_title'] = isset($categoryList[$course['category']]) ? $categoryList[$course['category']] : '';
            }

            if (api_get_configuration_value('hide_course_rating') === false) {
                $ajax_url = api_get_path(WEB_AJAX_PATH).'course.ajax.php?a=add_course_vote';
                $rating = Display::return_rating_system(
                        'star_'.$course['real_id'],
                        $ajax_url.'&course_id='.$course['real_id'],
                        $course['point_info']
                    );
            }

            //course return image

            $course_path = api_get_path(SYS_COURSE_PATH).$course['directory'];

            if (file_exists($course_path.'/course-pic.png')) {
                $courseMediumImage = api_get_path(WEB_COURSE_PATH).$course['directory'].'/course-pic.png';
            } else {
                // without picture
                $courseMediumImage = Display::return_icon(
                        'session_default.png',
                        null,
                        null,
                        null,
                        null,
                        true
                    );
            }

            if ($showTeacher) {
                $teachers = CourseManager::getTeachersFromCourse($course['real_id']);
            }

            $separator = null;
            $subscribeBuy = return_register_button($course, $stok, $code, $searchTerm);
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
                    $subscribeBuy = $plugin->returnBuyCourseButton(
                        $course['real_id'],
                        BuyCoursesPlugin::PRODUCT_TYPE_COURSE
                    );
                }
            }
            // end buy course validation

            // if user registered as student
            if ($userRegisteredInCourse) {
                $subscribeButton = return_already_registered_label('student');
                if (!$course_closed) {
                    if ($course_unsubscribe_allowed) {
                        $subscribeButton = return_unregister_button($course, $stok, $searchTerm, $code);
                    }
                }
            } elseif ($userRegisteredInCourseAsTeacher) {
                // if user registered as teacher
                if ($course_unsubscribe_allowed) {
                    $subscribeButton = return_unregister_button($course, $stok, $searchTerm, $code);
                }
            } else {
                // if user not registered in the course
                if (!$course_closed) {
                    if (!$course_private) {
                        if ($course_subscribe_allowed) {
                            $subscribeButton = $subscribeBuy;
                        }
                    }
                }
            }

            $courseList[] = [
                    'id' => $course['real_id'],
                    'title' => $course['title'],
                    'category' => $course['category_title'],
                    'image' => $courseMediumImage,
                    'url' => $linkCourse = api_get_path(WEB_PATH).'course/'.$course['real_id'].'/about',
                    'teachers' => $teachers,
                    'ranking' => $rating,
                    'description_ajax' => CourseManager::returnDescriptionButton($course),
                    'subscribe' => $subscribeButton,
                ];
        }
    } else {
        if (!isset($_REQUEST['subscribe_user_with_password']) &&
            !isset($_REQUEST['subscribe_course'])
        ) {
            $message = Display::return_message(
                get_lang('ThereAreNoCoursesInThisCategory'),
                'warning'
            );
        }
    }
}

$template = new Template(get_lang('Course Catalog'));
$template->assign('search', $search);
$template->assign('select', $select);
$template->assign('pagination', $cataloguePagination);
$template->assign('courses', $courseList);
$template->assign('message', $message);
$layout = $template->get_template('auth/course_catalog.html.twig');
$content = $template->fetch($layout);

echo $content;

/**
 * Display the goto course button of a course in the course catalog.
 *
 * @param $course
 *
 * @return string HTML string
 */
function return_goto_button($course)
{
    $title = get_lang('GoToCourse');
    $html = Display::url(
        Display::returnFontAwesomeIcon('share'),
        api_get_course_url($course['code']),
        [
            'class' => 'btn btn-default btn-sm',
            'title' => $title,
            'aria-label' => $title,
        ]
    );

    return $html.PHP_EOL;
}

/**
 * Display the already registerd text in a course in the course catalog.
 *
 * @param $in_status
 *
 * @return string HTML string
 */
function return_already_registered_label($in_status)
{
    $icon = '<em class="fa fa-check"></em>';
    $title = get_lang("YouAreATeacherOfThisCourse");
    if ($in_status == 'student') {
        $icon = '<em class="fa fa-check"></em>';
        $title = get_lang("AlreadySubscribed");
    }

    $html = Display::tag(
        'span',
        $icon.' '.$title,
        [
            'id' => 'register',
            'class' => 'label-subscribed text-success',
            'title' => $title,
            'aria-label' => $title,
        ]
    );

    return $html.PHP_EOL;
}

/**
 * Display the register button of a course in the course catalog.
 *
 * @param $course
 * @param $stok
 * @param $code
 * @param $search_term
 *
 * @return string
 */
function return_register_button($course, $stok, $code, $search_term)
{
    $title = get_lang('Subscribe');
    $action = 'subscribe_course';
    if (!empty($course['registration_code'])) {
        $action = 'subscribe_course_validation';
    }

    $html = Display::url(
        Display::returnFontAwesomeIcon('check').' '.$title,
        api_get_self().'?action='.$action.'&sec_token='.$stok.
        '&subscribe_course='.$course['code'].'&search_term='.$search_term.'&category_code='.$code,
        ['class' => 'btn btn-success btn-sm', 'title' => $title, 'aria-label' => $title]
    );

    return $html;
}

/**
 * Display the unregister button of a course in the course catalog.
 *
 * @param $course
 * @param $stok
 * @param $search_term
 * @param $code
 *
 * @return string
 */
function return_unregister_button($course, $stok, $search_term, $code)
{
    $title = get_lang('Unsubscription');
    $html = Display::url(
        Display::returnFontAwesomeIcon('sign-in').' '.$title,
        api_get_self().'?action=unsubscribe&sec_token='.$stok
        .'&unsubscribe='.$course['code'].'&search_term='.$search_term.'&category_code='.$code,
        ['class' => 'btn btn-danger btn-sm', 'title' => $title, 'aria-label' => $title]
    );

    return $html;
}
