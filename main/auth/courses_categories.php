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
$pageCurrent = isset($pageCurrent) ? $pageCurrent : isset($_GET['pageCurrent']) ? intval($_GET['pageCurrent']) : 1;
$pageLength = isset($pageLength) ? $pageLength : isset($_GET['pageLength']) ? intval($_GET['pageLength']) : CoursesAndSessionsCatalog::PAGE_LENGTH;
$pageTotal = intval(ceil(intval($countCoursesInCategory) / $pageLength));
$cataloguePagination = $pageTotal > 1 ? CourseCategory::getCatalogPagination($pageCurrent, $pageLength, $pageTotal) : '';
$searchTerm = isset($_REQUEST['search_term']) ? Security::remove_XSS($_REQUEST['search_term']) : '';
$codeType = isset($_REQUEST['category_code']) ? Security::remove_XSS($_REQUEST['category_code']) : '';

if ($showSessions && isset($_POST['date'])) {
    $date = $_POST['date'];
} else {
    $date = date('Y-m-d');
}

$userInfo = api_get_user_info();
$code = isset($code) ? $code : null;

?>
<script>
    $(document).ready( function() {
        $('.star-rating li a').on('click', function(event) {
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
<div class="row">
    <div class="col-md-12">
        <h2 class="title-courses"><?php echo get_lang('CourseManagement'); ?></h2>
        <div class="search-courses">
            <div class="row">
<?php if ($showCourses) {
        ?>
                    <div class="col-md-<?php echo $showSessions ? '4' : '6'; ?>">
                        <?php if (!isset($_GET['hidden_links']) || intval($_GET['hidden_links']) != 1) {
            ?>
                            <form method="post"
                                  action="<?php echo CourseCategory::getCourseCategoryUrl(1, $pageLength, 'ALL', 0, 'subscribe'); ?>">
                                <input type="hidden" name="sec_token" value="<?php echo $stok; ?>">
                                <input type="hidden" name="search_course" value="1"/>
                                <label><?php echo get_lang('Search'); ?></label>
                                <div class="input-group">
                                    <input class="form-control" type="text" name="search_term"
                                           value="<?php echo empty($_POST['search_term'])
                                               ? ''
                                               : api_htmlentities($searchTerm); ?>"/>
                                    <div class="input-group-btn">
                                        <button class="btn btn-default" type="submit">
                                            <em class="fa fa-search"></em> <?php echo get_lang('Search'); ?>
                                        </button>
                                    </div>
                                </div>
                            </form>
                        <?php
        } ?>
                    </div>
                    <div class="col-md-<?php echo $showSessions ? '4' : '6'; ?>">
<?php
    $webAction = api_get_path(WEB_CODE_PATH).'auth/courses.php';
        $form = '<form action="'.$webAction.'" method="GET">';
        $form .= '<input type="hidden" name="action" value="'.$action.'">';
        $form .= '<input type="hidden" name="pageCurrent" value="'.$pageCurrent.'">';
        $form .= '<input type="hidden" name="pageLength" value="'.$pageLength.'">';
        $form .= '<div class="form-group">';
        $form .= '<label>'.get_lang('CourseCategories').'</label>';
        $form .= '<select name="category_code" onchange="submit();" class="selectpicker show-tick form-control">';
        foreach ($browse_course_categories[0] as $category) {
            $categoryCode = $category['code'];
            $countCourse = $category['count_courses'];
            if (empty($countCourse)) {
                continue;
            }
            $form .= '<option '.($categoryCode == $codeType ? 'selected="selected" ' : '')
                            .' value="'.$category['code'].'">'.$category['name'].' ('.$countCourse.') </option>';
            if (!empty($browse_course_categories[$categoryCode])) {
                foreach ($browse_course_categories[$categoryCode] as $subCategory) {
                    if (empty($subCategory['count_courses'])) {
                        continue;
                    }
                    $subCategoryCode = $subCategory['code'];
                    $form .= '<option '.($subCategoryCode == $codeType
                                        ? 'selected="selected" '
                                        : '')
                                    .' value="'.$subCategory['code'].'">---';
                    echo $subCategory['name'].' ('.$subCategory['count_courses'].')</option>';
                }
            }
        }
        $form .= '</select>';
        $form .= '</div>';
        $form .= '</form>';
        echo $form;
        echo '</div>';
    }

if ($showSessions) {
    ?>
    <div class="col-md-4">
        <div class="return-catalog">
            <a class="btn btn-default btn-lg btn-block"
               href="<?php echo CourseCategory::getCourseCategoryUrl(1, $pageLength, null, 0, 'display_sessions'); ?>">
                <em class="fa fa-arrow-right"></em> <?php echo get_lang('SessionList'); ?>
            </a>
        </div>
    </div>
<?php
} ?>
            </div>
        </div>
    </div>
</div>
<?php
if ($showCourses && $action != 'display_sessions') {
        if (!empty($message)) {
            echo Display::return_message($message, 'confirmation', false);
        }
        if (!empty($error)) {
            echo Display::return_message($error, 'error', false);
        }

        if (!empty($content)) {
            echo $content;
        }

        if (!empty($searchTerm)) {
            echo "<p><strong>".get_lang('SearchResultsFor')." ".$searchTerm."</strong><br />";
        }

        $showTeacher = api_get_setting('display_teacher_in_courselist') === 'true';
        $ajax_url = api_get_path(WEB_AJAX_PATH).'course.ajax.php?a=add_course_vote';
        $user_id = api_get_user_id();
        $categoryListFromDatabase = CourseCategory::getCategories();

        $categoryList = [];
        if (!empty($categoryListFromDatabase)) {
            foreach ($categoryListFromDatabase as $categoryItem) {
                $categoryList[$categoryItem['code']] = $categoryItem['name'];
            }
        }

        if (!empty($browse_courses_in_category)) {
            echo '<div class="grid-courses row">';
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
                $html .= returnThumbnail($course, $userRegistered);

                $separator = null;
                $subscribeButton = return_register_button($course, $stok, $code, $searchTerm);
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
                $html .= return_title($course, $userRegisteredInCourse);

                if ($showTeacher) {
                    $html .= return_teacher($course);
                }

                // display button line
                $html .= '<div class="toolbar row">';
                $html .= $separator ? '<div class="col-sm-4">'.$separator.'</div>' : '';
                $html .= '<div class="col-sm-8">';
                // if user registered as student
                if ($userRegisteredInCourse) {
                    $html .= return_already_registered_label('student');
                    if (!$course_closed) {
                        if ($course_unsubscribe_allowed) {
                            $html .= return_unregister_button($course, $stok, $searchTerm, $code);
                        }
                    }
                } elseif ($userRegisteredInCourseAsTeacher) {
                    // if user registered as teacher
                    if ($course_unsubscribe_allowed) {
                        $html .= return_unregister_button($course, $stok, $searchTerm, $code);
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
                echo $html;
            }
            echo '</div>';
        } else {
            if (!isset($_REQUEST['subscribe_user_with_password']) &&
            !isset($_REQUEST['subscribe_course'])
        ) {
                echo Display::return_message(
                get_lang('ThereAreNoCoursesInThisCategory'),
                'warning'
            );
            }
        }
    }

echo '<div class="col-md-12">';
echo $cataloguePagination;
echo '</div>';

/**
 * Display the course catalog image of a course.
 *
 * @param array $course
 * @param bool  $registeredUser
 *
 * @return string HTML string
 */
function returnThumbnail($course, $registeredUser)
{
    $html = '';
    $title = cut($course['title'], 70);
    //$linkCourse = api_get_course_url($course['code']);
    $linkCourse = api_get_path(WEB_PATH).'course/'.$course['real_id'].'/about';
    // course path
    $course_path = api_get_path(SYS_COURSE_PATH).$course['directory'];

    if (file_exists($course_path.'/course-pic.png')) {
        // redimensioned image 85x85
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

    $html .= '<div class="image">';
    $html .= '<a href="'.$linkCourse.'" title="'.$course['title'].'">'
            .'<img class="img-responsive" src="'.$courseMediumImage.'" '
            .'alt="'.api_htmlentities($title).'"/></a>';

    $categoryTitle = isset($course['category_title']) ? $course['category_title'] : '';
    if (!empty($categoryTitle)) {
        $html .= '<span class="category">'.$categoryTitle.'</span>';
        $html .= '<div class="cribbon"></div>';
    }

    $html .= '<div class="user-actions">';
    $html .= CourseManager::returnDescriptionButton($course);
    $html .= '</div></div>';

    return $html;
}

/**
 * @param array $courseInfo
 *
 * @return string
 */
function return_teacher($courseInfo)
{
    $teachers = CourseManager::getTeachersFromCourse($courseInfo['real_id']);
    $length = count($teachers);

    if (!$length) {
        return '';
    }

    $html = '<div class="block-author">';
    if ($length > 6) {
        $html .= '<a 
            id="plist" 
            data-trigger="focus" 
            tabindex="0" role="button" 
            class="btn btn-default panel_popover" 
            data-toggle="popover" 
            title="'.addslashes(get_lang('CourseTeachers')).'" 
            data-html="true"
        >
            <i class="fa fa-graduation-cap" aria-hidden="true"></i>
        </a>';
        $html .= '<div id="popover-content-plist" class="hide">';
        foreach ($teachers as $value) {
            $name = $value['firstname'].' '.$value['lastname'];
            $html .= '<div class="popover-teacher">';
            $html .= '<a href="'.$value['url'].'" class="ajax" data-title="'.$name.'" title="'.$name.'">
                        <img src="'.$value['avatar'].'" alt="'.get_lang('UserPicture').'"/></a>';
            $html .= '<div class="teachers-details"><h5>
                        <a href="'.$value['url'].'" class="ajax" data-title="'.$name.'">'
                        .$name.'</a></h5></div>';
            $html .= '</div>';
        }
        $html .= '</div>';
    } else {
        foreach ($teachers as $value) {
            $name = $value['firstname'].' '.$value['lastname'];
            if ($length > 2) {
                $html .= '<a href="'.$value['url'].'" class="ajax" data-title="'.$name.'" title="'.$name.'">
                        <img src="'.$value['avatar'].'" alt="'.get_lang('UserPicture').'"/></a>';
            } else {
                $html .= '<a href="'.$value['url'].'" class="ajax" data-title="'.$name.'" title="'.$name.'">
                        <img src="'.$value['avatar'].'" alt="'.get_lang('UserPicture').'"/></a>';
                $html .= '<div class="teachers-details"><h5>
                        <a href="'.$value['url'].'" class="ajax" data-title="'.$name.'">'
                        .$name.'</a></h5><p>'.get_lang('Teacher').'</p></div>';
            }
        }
    }
    $html .= '</div>';

    return $html;
}

/**
 * Display the title of a course in course catalog.
 *
 * @param array $course
 * @param bool  $registeredUser
 *
 * @return string HTML string
 */
function return_title($course, $registeredUser)
{
    //$linkCourse = api_get_course_url($course['code']);
    $linkCourse = api_get_path(WEB_PATH).'course/'.$course['real_id'].'/about';
    $html = '<div class="block-title"><h4 class="title">';
    $html .= '<a title="'.$course['title'].'" href="'.$linkCourse.'">'.$course['title'].'</a>';
    $html .= '</h4></div>';

    if (api_get_configuration_value('hide_course_rating') === false) {
        $ajax_url = api_get_path(WEB_AJAX_PATH).'course.ajax.php?a=add_course_vote';
        $rating = Display::return_rating_system(
            'star_'.$course['real_id'],
            $ajax_url.'&course_id='.$course['real_id'],
            $course['point_info']
        );
        $html .= '<div class="ranking">'.$rating.'</div>';
    }

    return $html;
}

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
    $html = Display::url(
        Display::returnFontAwesomeIcon('check').' '.$title,
        api_get_self().'?action=subscribe_course&sec_token='.$stok.
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
