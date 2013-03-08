<?php

/**
 * Controller for pages presentation in general
 * @license see /license.txt
 * @package chamilo.page.controller
 * @author Julio Montoya <gugli100@gmail.com>
 */

/**
 * Page controller
 */
class PageController
{

    /**
     * Returns an HTML block with the user picture (as a link in a <div>)
     * @param int User ID (if not provided, will use the user ID from session)
     * @return string HTML div with a link to the user's profile
     * @uses UserManager::get_user_pictur_path_by_id() to get the image path
     * @uses UserManager::get_picture_user() to get the details of the image in a specific format
     * @uses PageController::show_right_block() to include the image in a larger user block
     * @assert (-1) === false
     */
    static function return_user_image_block($user_id = null) {
        if (empty($user_id)) {
            $user_id = api_get_user_id();
        }

        //Always show the user image
        $img_array = UserManager::get_user_picture_path_by_id($user_id, 'web', true, true);
        $no_image = false;
        if ($img_array['file'] == 'unknown.jpg') {
            $no_image = true;
        }
        $img_array = UserManager::get_picture_user($user_id, $img_array['file'], 50, USER_IMAGE_SIZE_MEDIUM, ' width="90" height="90" ');

        $profile_content = null;

        if (api_get_setting('allow_social_tool') == 'true') {
            if (!$no_image) {
                $profile_content .='<a style="text-align:center" href="'.api_get_path(WEB_CODE_PATH).'social/home.php"><img src="'.$img_array['file'].'"  '.$img_array['style'].' ></a>';
            } else {
                $profile_content .='<a style="text-align:center"  href="'.api_get_path(WEB_CODE_PATH).'auth/profile.php"><img title="'.get_lang('EditProfile').'" src="'.$img_array['file'].'" '.$img_array['style'].'></a>';
            }
        }
        self::show_right_block(null, null, 'user_image_block', array('content' => $profile_content));
    }

    /**
     * Return a block with course-related links. The resulting HTML block's
     * contents are only based on the user defined by the active session.
     * @return string HTML <div> with links
     * @assert () != ''
     */
    static function return_course_block() {
        $show_create_link = false;
        $show_course_link = false;

        if ((api_get_setting('allow_users_to_create_courses') == 'false' && !api_is_platform_admin()) || api_is_student()) {
            $display_add_course_link = false;
        } else {
            $display_add_course_link = true;
        }
        //$display_add_course_link = api_is_allowed_to_create_course() && ($_SESSION['studentview'] != 'studentenview');

        if ($display_add_course_link) {
            $show_create_link = true;
        }

        if (api_is_platform_admin() || api_is_course_admin() || api_is_allowed_to_create_course()) {
            $show_course_link = true;
        } else {
            if (api_get_setting('allow_students_to_browse_courses') == 'true') {
                $show_course_link = true;
            }
        }

        // My account section
        $my_account_content = array();

        if ($show_create_link) {
            $my_account_content[] = array(
                'href' => api_get_path(WEB_CODE_PATH).'create_course/add_course.php',
                'title' => api_get_setting('course_validation') == 'true' ? get_lang('CreateCourseRequest') : get_lang('CourseCreate'));
        }

        //Sort courses
        $url = api_get_path(WEB_CODE_PATH).'auth/courses.php?action=sortmycourses';
        $my_account_content[] = array(
            'href' => $url,
            'title' => get_lang('SortMyCourses')
        );

        //Course management
        if ($show_course_link) {
            if (!api_is_drh()) {
                $my_account_content[] = array('href' => api_get_path(WEB_CODE_PATH).'auth/courses.php', 'title' => get_lang('CourseCatalog'));

                if (isset($_GET['history']) && intval($_GET['history']) == 1) {
                    $my_account_content [] = array(
                        'href' => 'user_portal.php',
                        'title' => get_lang('DisplayTrainingList')
                    );
                } else {
                    $my_account_content [] = array(
                        'href' => 'user_portal.php?history=1',
                        'title' => get_lang('HistoryTrainingSessions')
                    );
                }
            } else {
                $my_account_content .= array(
                    'href' => api_get_path(WEB_CODE_PATH).'dashboard/index.php',
                    'title' => get_lang('Dashboard')
                );
            }
        }
        self::show_right_block(get_lang('Courses'), $my_account_content, 'course_block');
    }

    /**
     * Returns the profile block, showing links to the messaging and social
     * network tools. The user ID is taken from the active session
     * @return string HTML <div> block
     * @assert () != ''
     */
    static function return_profile_block() {
        global $app;
        //  @todo Add a platform setting to add the user image.
        if (api_get_setting('allow_message_tool') == 'true') {
            if (api_get_setting('allow_social_tool') == 'true') {
                self::show_right_block(get_lang('Profile'), array(), 'profile_social_block');
            } else {
                self::show_right_block(get_lang('Profile'), array(), 'profile_block');
            }
        }
    }

    /**
     * Returns a list of the most popular courses of the moment (also called
     * "hot courses").
     * @uses CourseManager::return_hot_courses() in fact, the current method is only a bypass to this method
     * @return string HTML <div> with the most popular courses
     * @assert () != ''
     */
    static function return_hot_courses() {
        return CourseManager::return_hot_courses();
    }

    /**
     * Returns an online help block read from the home/home_menu_[lang].html
     * file
     * @return string HTML block
     * @assert () != ''
     */
    static function return_help() {
        $home = api_get_home_path();
        $user_selected_language = api_get_interface_language();
        $sys_path = api_get_path(SYS_PATH);
        $platformLanguage = api_get_setting('platformLanguage');

        if (!isset($user_selected_language)) {
            $user_selected_language = $platformLanguage;
        }
        $home_menu = @(string) file_get_contents($sys_path.$home.'home_menu_'.$user_selected_language.'.html');
        if (!empty($home_menu)) {
            $home_menu_content = api_to_system_encoding($home_menu, api_detect_encoding(strip_tags($home_menu)));
            self::show_right_block(get_lang('MenuGeneral'), null, 'help_block', array('content' => $home_menu_content));
        }
    }

    /**
     * Returns an HTML block with links to the skills tools
     * @return string HTML <div> block
     * @assert () != ''
     */
    static function return_skills_links() {
        if (api_get_setting('allow_skills_tool') == 'true') {
            $content = array();
            $content[]= array('title' => get_lang('MySkills'), 'href' => api_get_path(WEB_CODE_PATH).'social/skills_wheel.php');

            if (api_get_setting('allow_hr_skills_management') == 'true' || api_is_platform_admin()) {
                $content[]= array('title' => get_lang('ManageSkills'), 'href' => api_get_path(WEB_CODE_PATH).'admin/skills_wheel.php');
            }
            self::show_right_block(get_lang("Skills"), $content, 'skill_block');
        }
    }

    /**
     * Returns an HTML block with the notice, as found in the
     * home/home_notice_[lang].html file
     * @return string HTML <div> block
     * @assert () != ''
     */
    static function return_notice() {
        $sys_path = api_get_path(SYS_PATH);
        $user_selected_language = api_get_interface_language();
        $home = api_get_home_path();

        // Notice
        $home_notice = @(string) file_get_contents($sys_path.$home.'home_notice_'.$user_selected_language.'.html');
        if (empty($home_notice)) {
            $home_notice = @(string) file_get_contents($sys_path.$home.'home_notice.html');
        }

        if (!empty($home_notice)) {
            $home_notice = api_to_system_encoding($home_notice, api_detect_encoding(strip_tags($home_notice)));
            $home_notice = Display::div($home_notice, array('class' => 'homepage_notice'));

            self::show_right_block(get_lang('Notice'), null, 'notice_block', array('content' => $home_notice));
        }
    }

    /**
     * Returns the received content packaged in <div> block, with the title as
     * <h4>
     * @param string Title to include as h4
     * @param string Longer content to show (usually a <ul> list)
     * @param string ID to be added to the HTML attributes for the block
     * @param array Array of attributes to add to the HTML block
     * @return string HTML <div> block
     * @assert ('a','') != ''
     * @todo use the template system
     */
    static function show_right_block($title, $content, $id, $params = null) {
        //@todo do not use global
        global $app;
        if (!empty($id)) {
            $params['id'] = $id;
        }
        $block_menu = array(
            'id' => $params['id'],
            'title' => $title,
            'elements' => $content,
            'content' => isset($params['content']) ? $params['content'] : null
        );
        $app['template']->assign($id, $block_menu);
    }

    /**
     * Adds a form to let users login
     * @return string An HTML string with the user login form
     * @assert () != ''
     * @version 1.1
     */
    static function display_login_form() {
        $form = new FormValidator('formLogin', 'POST', null, null, array('class' => 'form-vertical'));
        // 'placeholder'=>get_lang('UserName')
        //'autocomplete'=>"off",

        $form->addElement('text', 'login', get_lang('UserName'), array('class' => 'span2 autocapitalize_off', 'autofocus' => 'autofocus'));
        $form->addElement('password', 'password', get_lang('Pass'), array('class' => 'span2'));
        $form->addElement('style_submit_button', 'submitAuth', get_lang('LoginEnter'), array('class' => 'btn'));
        $html = $form->return_form();
        if (api_get_setting('openid_authentication') == 'true') {
            include_once 'main/auth/openid/login.php';
            $html .= '<div>'.openid_form().'</div>';
        }
        return $html;
    }

    /**
     * Returns a content search form in an HTML <div>, pointing at the
     * main/search/ directory. If search_enabled is not set, then it returns
     * an empty string
     * @return string HTML <div> block showing the search form, or an empty string if search not enabled
     * @assert () !== false
     */
    static function return_search_block() {
        $html = '';
        if (api_get_setting('search_enabled') == 'true') {
            $html .= '<div class="searchbox">';
            $search_btn = get_lang('Search');
            $search_content = '<br />
                <form action="main/search/" method="post">
                <input type="text" id="query" class="span2" name="query" value="" />
                <button class="save" type="submit" name="submit" value="'.$search_btn.'" />'.$search_btn.' </button>
                </form></div>';
            $html .= self::show_right_block(get_lang('Search'), $search_content, 'search_block');
        }
        return $html;
    }

    /**
     * Returns a list of announcements
     * @param int User ID
     * @param bool True: show the announcements as a slider. False: show them as a vertical list
     * @return string HTML list of announcements
     * @assert () != ''
     * @assert (1) != ''
     */
    static function return_announcements($user_id = null, $show_slide = true) {
        // Display System announcements
        $announcement = isset($_GET['announcement']) ? intval($_GET['announcement']) : null;

        if (!api_is_anonymous() && $user_id) {
            $visibility = api_is_allowed_to_create_course() ? SystemAnnouncementManager::VISIBLE_TEACHER : SystemAnnouncementManager::VISIBLE_STUDENT;
            if ($show_slide) {
                $announcements = SystemAnnouncementManager :: display_announcements_slider($visibility, $announcement);
            } else {
                $announcements = SystemAnnouncementManager :: display_all_announcements($visibility, $announcement);
            }
        } else {
            if ($show_slide) {
                $announcements = SystemAnnouncementManager :: display_announcements_slider(SystemAnnouncementManager::VISIBLE_GUEST, $announcement);
            } else {
                $announcements = SystemAnnouncementManager :: display_all_announcements(SystemAnnouncementManager::VISIBLE_GUEST, $announcement);
            }
        }
        return $announcements;
    }

    /**
     * Return the homepage, including announcements
     * @return string The portal's homepage as an HTML string
     * @assert () != ''
     */
    static function return_home_page() {
        // Including the page for the news
        $html = null;
        $home = api_get_home_path();
        $home_top_temp = null;

        if (!empty($_GET['include']) && preg_match('/^[a-zA-Z0-9_-]*\.html$/', $_GET['include'])) {
            $open = @(string) file_get_contents(api_get_path(SYS_PATH).$home.$_GET['include']);
            $html = api_to_system_encoding($open, api_detect_encoding(strip_tags($open)));
        } else {
            if (!empty($_SESSION['user_language_choice'])) {
                $user_selected_language = $_SESSION['user_language_choice'];
            } elseif (!empty($_SESSION['_user']['language'])) {
                $user_selected_language = $_SESSION['_user']['language'];
            } else {
                $user_selected_language = api_get_setting('platformLanguage');
            }
            if (!file_exists($home.'home_news_'.$user_selected_language.'.html')) {
                if (file_exists($home.'home_top.html')) {
                    $home_top_temp = file($home.'home_top.html');
                } else {
                    //$home_top_temp = file('home/'.'home_top.html');
                }
                if (!empty($home_top_temp)) {
                    $home_top_temp = implode('', $home_top_temp);
                }
            } else {
                if (file_exists($home.'home_top_'.$user_selected_language.'.html')) {
                    $home_top_temp = file_get_contents($home.'home_top_'.$user_selected_language.'.html');
                } else {
                    $home_top_temp = file_get_contents($home.'home_top.html');
                }
            }
            //trim($home_top_temp) == ''
            if (empty($home_top_temp) && api_is_platform_admin()) {
                $home_top_temp = get_lang('PortalHomepageDefaultIntroduction');
            }
            $open = str_replace('{rel_path}', api_get_path(REL_PATH), $home_top_temp);
            if (!empty($open)) {
                $html = api_to_system_encoding($open, api_detect_encoding(strip_tags($open)));
            }
        }
        return $html;
    }

    /**
     * Returns the reservation block (if the reservation tool is enabled)
     * @return string HTML block, or empty string if reservation tool is disabled
     * @assert () == ''
     */
    static function return_reservation_block() {
        $html = '';
        $booking_content = null;
        if (api_get_setting('allow_reservation') == 'true' && api_is_allowed_to_create_course()) {
            $booking_content .='<ul class="nav nav-list">';
            $booking_content .='<a href="main/reservation/reservation.php">'.get_lang('ManageReservations').'</a><br />';
            $booking_content .='</ul>';
            $html .= self::show_right_block(get_lang('Booking'), $booking_content, 'reservation_block');
        }
        return $html;
    }

    /**
     * Returns an HTML block with classes (if show_groups_to_users is true)
     * @return string A list of links to users classes tools, or an empty string if show_groups_to_users is disabled
     * @assert  () == ''
     */
    static function return_classes_block() {
        $html = '';
        if (api_get_setting('show_groups_to_users') == 'true') {
            require_once api_get_path(LIBRARY_PATH).'usergroup.lib.php';
            $usergroup = new Usergroup();
            $usergroup_list = $usergroup->get_usergroup_by_user(api_get_user_id());
            $classes = '';
            if (!empty($usergroup_list)) {
                foreach ($usergroup_list as $group_id) {
                    $data = $usergroup->get($group_id);
                    $data['name'] = Display::url($data['name'], api_get_path(WEB_CODE_PATH).'user/classes.php?id='.$data['id']);
                    $classes .= Display::tag('li', $data['name']);
                }
            }
            if (api_is_platform_admin()) {
                $classes .= Display::tag('li', Display::url(get_lang('AddClasses'), api_get_path(WEB_CODE_PATH).'admin/usergroups.php?action=add'));
            }
            if (!empty($classes)) {
                $classes = Display::tag('ul', $classes, array('class' => 'nav nav-list'));
                $html .= self::show_right_block(get_lang('Classes'), $classes, 'classes_block');
            }
        }
        return $html;
    }

    /**
     * Prepares a block with all the pending exercises in all courses
     * @param array Array of courses (arrays) of the user
     * @return void Doesn't return anything but prepares and HTML block for use in templates
     * @assert () !== 1
     */
    static function return_exercise_block($personal_course_list, $tpl) {
        require_once api_get_path(SYS_CODE_PATH).'exercice/exercise.lib.php';
        $exercise_list = array();
        if (!empty($personal_course_list)) {
            foreach ($personal_course_list as $course_item) {
                $course_code = $course_item['c'];
                $session_id = $course_item['id_session'];

                $exercises = get_exercises_to_be_taken($course_code, $session_id);

                foreach ($exercises as $exercise_item) {
                    $exercise_item['course_code'] = $course_code;
                    $exercise_item['session_id'] = $session_id;
                    $exercise_item['tms'] = api_strtotime($exercise_item['end_time'], 'UTC');

                    $exercise_list[] = $exercise_item;
                }
            }
            if (!empty($exercise_list)) {
                $exercise_list = msort($exercise_list, 'tms');
                $my_exercise = $exercise_list[0];
                $url = Display::url($my_exercise['title'], api_get_path(WEB_CODE_PATH).'exercice/overview.php?exerciseId='.$my_exercise['id'].'&cidReq='.$my_exercise['course_code'].'&id_session='.$my_exercise['session_id']);
                $tpl->assign('exercise_url', $url);
                $tpl->assign('exercise_end_date', api_convert_and_format_date($my_exercise['end_time'], DATE_FORMAT_SHORT));
            }
        }
    }

    /**
     * Returns links to teachers tools (create course, etc) based on the user
     * in the active session
     * @return string HTML <div> block
     * @assert () == ''
     */
    static function return_teacher_link() {
        $html = '';
        $user_id = api_get_user_id();

        if (!empty($user_id)) {
            // tabs that are deactivated are added here

            $show_menu = false;
            $show_create_link = false;
            $show_course_link = false;

            if (api_is_platform_admin() || api_is_course_admin() || api_is_allowed_to_create_course()) {
                $show_menu = true;
                $show_course_link = true;
            } else {
                if (api_get_setting('allow_students_to_browse_courses') == 'true') {
                    $show_menu = true;
                    $show_course_link = true;
                }
            }

            if ($show_menu && ($show_create_link || $show_course_link )) {
                $show_menu = true;
            } else {
                $show_menu = false;
            }
        }

        // My Account section
        $elements = array();
        if ($show_menu) {
            if ($show_create_link) {
                $elements[] = array('href' => api_get_path(WEB_CODE_PATH).'create_course/add_course.php',
                                    'title' => (api_get_setting('course_validation') == 'true' ? get_lang('CreateCourseRequest') : get_lang('CourseCreate')));
            }

            if ($show_course_link) {
                if (!api_is_drh() && !api_is_session_admin()) {
                    $elements[] = array('href' => api_get_path(WEB_CODE_PATH).'auth/courses.php', 'title' => get_lang('CourseCatalog'));
                } else {
                    $elements[] = array('href' => api_get_path(WEB_CODE_PATH).'dashboard/index.php', 'title' => get_lang('Dashboard'));
                }
            }
        }
        self::show_right_block(get_lang('Courses'), $elements, 'teacher_block');
    }

    /**
     * Display list of courses in a category.
     * (for anonymous users)
     *
     * @version 1.1
     * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University - refactoring and code cleaning
     * @author Julio Montoya <gugli100@gmail.com>, Beeznest template modifs
     * @assert () !== 0
     */
    static function return_courses_in_categories() {
        $result = '';
        $stok = Security::get_token();

        // Initialization.
        $user_identified = (api_get_user_id() > 0 && !api_is_anonymous());
        $web_course_path = api_get_path(WEB_COURSE_PATH);
        $category = Database::escape_string($_GET['category']);
        $setting_show_also_closed_courses = api_get_setting('show_closed_courses') == 'true';

        // Database table definitions.
        $main_course_table = Database :: get_main_table(TABLE_MAIN_COURSE);
        $main_category_table = Database :: get_main_table(TABLE_MAIN_CATEGORY);

        // Get list of courses in category $category.
        $sql_get_course_list = "SELECT * FROM $main_course_table cours
                                    WHERE category_code = '".Database::escape_string($_GET['category'])."'
                                    ORDER BY title, UPPER(visual_code)";

        // Showing only the courses of the current access_url_id.
        global $_configuration;
        if ($_configuration['multiple_access_urls']) {
            $url_access_id = api_get_current_access_url_id();
            if ($url_access_id != -1) {
                $tbl_url_rel_course = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_COURSE);
                $sql_get_course_list = "SELECT * FROM $main_course_table as course INNER JOIN $tbl_url_rel_course as url_rel_course
                        ON (url_rel_course.course_code=course.code)
                        WHERE access_url_id = $url_access_id AND category_code = '".Database::escape_string($_GET['category'])."' ORDER BY title, UPPER(visual_code)";
            }
        }

        // Removed: AND cours.visibility='".COURSE_VISIBILITY_OPEN_WORLD."'
        $sql_result_courses = Database::query($sql_get_course_list);

        while ($course_result = Database::fetch_array($sql_result_courses)) {
            $course_list[] = $course_result;
        }

        $platform_visible_courses = '';
        // $setting_show_also_closed_courses
        if ($user_identified) {
            if ($setting_show_also_closed_courses) {
                $platform_visible_courses = '';
            } else {
                $platform_visible_courses = "  AND (t3.visibility='".COURSE_VISIBILITY_OPEN_WORLD."' OR t3.visibility='".COURSE_VISIBILITY_OPEN_PLATFORM."' )";
            }
        } else {
            if ($setting_show_also_closed_courses) {
                $platform_visible_courses = '';
            } else {
                $platform_visible_courses = "  AND (t3.visibility='".COURSE_VISIBILITY_OPEN_WORLD."' )";
            }
        }
        $sqlGetSubCatList = "
                    SELECT t1.name,t1.code,t1.parent_id,t1.children_count,COUNT(DISTINCT t3.code) AS nbCourse
                    FROM $main_category_table t1
                    LEFT JOIN $main_category_table t2 ON t1.code=t2.parent_id
                    LEFT JOIN $main_course_table t3 ON (t3.category_code=t1.code $platform_visible_courses)
                    WHERE t1.parent_id ".(empty($category) ? "IS NULL" : "='$category'")."
                    GROUP BY t1.name,t1.code,t1.parent_id,t1.children_count ORDER BY t1.tree_pos, t1.name";


        // Showing only the category of courses of the current access_url_id
        if ($_configuration['multiple_access_urls']) {
            $url_access_id = api_get_current_access_url_id();
            if ($url_access_id != -1) {
                $tbl_url_rel_course = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_COURSE);
                $sqlGetSubCatList = "
                    SELECT t1.name,t1.code,t1.parent_id,t1.children_count,COUNT(DISTINCT t3.code) AS nbCourse
                    FROM $main_category_table t1
                    LEFT JOIN $main_category_table t2 ON t1.code=t2.parent_id
                    LEFT JOIN $main_course_table t3 ON (t3.category_code=t1.code $platform_visible_courses)
                    INNER JOIN $tbl_url_rel_course as url_rel_course
                        ON (url_rel_course.course_code=t3.code)
                    WHERE access_url_id = $url_access_id AND t1.parent_id ".(empty($category) ? "IS NULL" : "='$category'")."
                    GROUP BY t1.name,t1.code,t1.parent_id,t1.children_count ORDER BY t1.tree_pos, t1.name";
            }
        }

        $resCats = Database::query($sqlGetSubCatList);
        $thereIsSubCat = false;
        if (Database::num_rows($resCats) > 0) {
            $htmlListCat = Display::page_header(get_lang('CatList'));
            $htmlListCat .= '<ul>';
            while ($catLine = Database::fetch_array($resCats)) {
                if ($catLine['code'] != $category) {
                    $category_has_open_courses = self::category_has_open_courses($catLine['code']);
                    if ($category_has_open_courses) {
                        // The category contains courses accessible to anonymous visitors.
                        $htmlListCat .= '<li>';
                        $htmlListCat .= '<a href="'.api_get_self().'?category='.$catLine['code'].'">'.$catLine['name'].'</a>';
                        if (api_get_setting('show_number_of_courses') == 'true') {
                            $htmlListCat .= ' ('.$catLine['nbCourse'].' '.get_lang('Courses').')';
                        }
                        $htmlListCat .= "</li>";
                        $thereIsSubCat = true;
                    } elseif ($catLine['children_count'] > 0) {
                        // The category has children, subcategories.
                        $htmlListCat .= '<li>';
                        $htmlListCat .= '<a href="'.api_get_self().'?category='.$catLine['code'].'">'.$catLine['name'].'</a>';
                        $htmlListCat .= "</li>";
                        $thereIsSubCat = true;
                    }
                    /* End changed code to eliminate the (0 courses) after empty categories. */ elseif (api_get_setting('show_empty_course_categories') == 'true') {
                        $htmlListCat .= '<li>';
                        $htmlListCat .= $catLine['name'];
                        $htmlListCat .= "</li>";
                        $thereIsSubCat = true;
                    } // Else don't set thereIsSubCat to true to avoid printing things if not requested.
                } else {
                    $htmlTitre = '<p>';
                    if (api_get_setting('show_back_link_on_top_of_tree') == 'true') {
                        $htmlTitre .= '<a href="'.api_get_self().'">&lt;&lt; '.get_lang('BackToHomePage').'</a>';
                    }
                    if (!is_null($catLine['parent_id']) || (api_get_setting('show_back_link_on_top_of_tree') != 'true' && !is_null($catLine['code']))) {
                        $htmlTitre .= '<a href="'.api_get_self().'?category='.$catLine['parent_id'].'">&lt;&lt; '.get_lang('Up').'</a>';
                    }
                    $htmlTitre .= "</p>";
                    if ($category != "" && !is_null($catLine['code'])) {
                        $htmlTitre .= '<h3>'.$catLine['name']."</h3>";
                    } else {
                        $htmlTitre .= '<h3>'.get_lang('Categories')."</h3>";
                    }
                }
            }
            $htmlListCat .= "</ul>";
        }
        $result .= $htmlTitre;
        if ($thereIsSubCat) {
            $result .= $htmlListCat;
        }
        while ($categoryName = Database::fetch_array($resCats)) {
            $result .= '<h3>'.$categoryName['name']."</h3>\n";
        }
        $numrows = Database::num_rows($sql_result_courses);
        $courses_list_string = '';
        $courses_shown = 0;
        if ($numrows > 0) {

            $courses_list_string .= Display::page_header(get_lang('CourseList'));
            $courses_list_string .= "<ul>";

            if (api_get_user_id()) {
                $courses_of_user = self::get_courses_of_user(api_get_user_id());
            }

            foreach ($course_list as $course) {
                // $setting_show_also_closed_courses
                if (!$setting_show_also_closed_courses) {
                    // If we do not show the closed courses
                    // we only show the courses that are open to the world (to everybody)
                    // and the courses that are open to the platform (if the current user is a registered user.
                    if (($user_identified && $course['visibility'] == COURSE_VISIBILITY_OPEN_PLATFORM) || ($course['visibility'] == COURSE_VISIBILITY_OPEN_WORLD)) {
                        $courses_shown++;
                        $courses_list_string .= "<li>\n";
                        $courses_list_string .= '<a href="'.$web_course_path.$course['directory'].'/">'.$course['title'].'</a><br />';
                        $course_details = array();
                        if (api_get_setting('display_coursecode_in_courselist') == 'true') {
                            $course_details[] = $course['visual_code'];
                        }
                        if (api_get_setting('display_teacher_in_courselist') == 'true') {
                            $course_details[] = $course['tutor_name'];
                        }
                        if (api_get_setting('show_different_course_language') == 'true' && $course['course_language'] != api_get_setting('platformLanguage')) {
                            $course_details[] = $course['course_language'];
                        }
                        $courses_list_string .= implode(' - ', $course_details);
                        $courses_list_string .= "</li>\n";
                    }
                } else {
                    // We DO show the closed courses.
                    // The course is accessible if (link to the course homepage):
                    // 1. the course is open to the world (doesn't matter if the user is logged in or not): $course['visibility'] == COURSE_VISIBILITY_OPEN_WORLD);
                    // 2. the user is logged in and the course is open to the world or open to the platform: ($user_identified && $course['visibility'] == COURSE_VISIBILITY_OPEN_PLATFORM);
                    // 3. the user is logged in and the user is subscribed to the course and the course visibility is not COURSE_VISIBILITY_CLOSED;
                    // 4. the user is logged in and the user is course admin of te course (regardless of the course visibility setting);
                    // 5. the user is the platform admin api_is_platform_admin().
                    //
                    $courses_shown++;
                    $courses_list_string .= "<li>\n";
                    if ($course['visibility'] == COURSE_VISIBILITY_OPEN_WORLD
                        || ($user_identified && $course['visibility'] == COURSE_VISIBILITY_OPEN_PLATFORM)
                        || ($user_identified && key_exists($course['code'], $courses_of_user) && $course['visibility'] != COURSE_VISIBILITY_CLOSED)
                        || $courses_of_user[$course['code']]['status'] == '1'
                        || api_is_platform_admin()) {
                        $courses_list_string .= '<a href="'.$web_course_path.$course['directory'].'/">';
                    }
                    $courses_list_string .= $course['title'];
                    if ($course['visibility'] == COURSE_VISIBILITY_OPEN_WORLD
                        || ($user_identified && $course['visibility'] == COURSE_VISIBILITY_OPEN_PLATFORM)
                        || ($user_identified && key_exists($course['code'], $courses_of_user) && $course['visibility'] != COURSE_VISIBILITY_CLOSED)
                        || $courses_of_user[$course['code']]['status'] == '1'
                        || api_is_platform_admin()) {
                        $courses_list_string .= '</a><br />';
                    }
                    $course_details = array();
                    if (api_get_setting('display_coursecode_in_courselist') == 'true') {
                        $course_details[] = $course['visual_code'];
                    }
//                        if (api_get_setting('display_coursecode_in_courselist') == 'true' && api_get_setting('display_teacher_in_courselist') == 'true') {
//                        $courses_list_string .= ' - ';
//                }
                    if (api_get_setting('display_teacher_in_courselist') == 'true') {
                        $course_details[] = $course['tutor_name'];
                    }
                    if (api_get_setting('show_different_course_language') == 'true' && $course['course_language'] != api_get_setting('platformLanguage')) {
                        $course_details[] = $course['course_language'];
                    }
                    if (api_get_setting('show_different_course_language') == 'true' && $course['course_language'] != api_get_setting('platformLanguage')) {
                        $course_details[] = $course['course_language'];
                    }

                    $courses_list_string .= implode(' - ', $course_details);
                    // We display a subscription link if:
                    // 1. it is allowed to register for the course and if the course is not already in the courselist of the user and if the user is identiefied
                    // 2.
                    if ($user_identified && !key_exists($course['code'], $courses_of_user)) {
                        if ($course['subscribe'] == '1') {
                            $courses_list_string .= '<form action="main/auth/courses.php?action=subscribe&category='.Security::remove_XSS($_GET['category']).'" method="post">';
                            $courses_list_string .= '<input type="hidden" name="sec_token" value="'.$stok.'">';
                            $courses_list_string .= '<input type="hidden" name="subscribe" value="'.$course['code'].'" />';
                            $courses_list_string .= '<input type="image" name="unsub" src="main/img/enroll.gif" alt="'.get_lang('Subscribe').'" />'.get_lang('Subscribe').'</form>';
                        } else {
                            $courses_list_string .= '<br />'.get_lang('SubscribingNotAllowed');
                        }
                    }
                    $courses_list_string .= "</li>";
                } //end else
            } // end foreach
            $courses_list_string .= "</ul>";
        }
        if ($courses_shown > 0) {
            // Only display the list of courses and categories if there was more than
            // 0 courses visible to the world (we're in the anonymous list here).
            $result .= $courses_list_string;
        }
        if ($category != '') {
            $result .= '<p><a href="'.api_get_self().'"> '.Display :: return_icon('back.png', get_lang('BackToHomePage')).get_lang('BackToHomePage').'</a></p>';
        }
        return $result;
    }

    /**
     * The most important function here, prints the session and course
     * list (user_portal.php)
     * @param int User ID
     * @return string HTML list of sessions and courses
     * @assert () === false
     */
    static function return_courses_and_sessions($user_id) {
        if (empty($user_id)) {
            return false;
        }

        $session_categories = array();
        $load_history = (isset($_GET['history']) && intval($_GET['history']) == 1) ? true : false;

        if ($load_history) {
            //Load sessions in category in *history*
            $session_categories = UserManager::get_sessions_by_category($user_id, true, false, true);
        } else {
            //Load sessions in category
            $session_categories = UserManager::get_sessions_by_category($user_id, false);
        }

        $html = '';
        //Showing history title
        if ($load_history) {
            $html .= Display::page_subheader(get_lang('HistoryTrainingSession'));
            if (empty($session_categories)) {
                $html .= get_lang('YouDoNotHaveAnySessionInItsHistory');
            }
        }

        $courses_html = '';
        $special_courses = '';

        $load_directories_preview = api_get_setting('show_documents_preview') == 'true' ? true : false;

        // If we're not in the history view...
        if (!isset($_GET['history'])) {

            //Display special courses
            $special_courses = CourseManager::display_special_courses($user_id, $load_directories_preview);

            //Display courses
            $courses_html .= CourseManager::display_courses($user_id, $load_directories_preview);
        }

        $sessions_with_category = '';
        $sessions_with_no_category = '';

        if (is_array($session_categories)) {
            foreach ($session_categories as $session_category) {
                $session_category_id = $session_category['session_category']['id'];
                // Sessions and courses that are not in a session category
                if ($session_category_id == 0) {

                    // Independent sessions
                    if (isset($session_category['sessions'])) {
                        foreach ($session_category['sessions'] as $session) {

                            $session_id = $session['session_id'];

                            // Don't show empty sessions.
                            if (count($session['courses']) < 1) {
                                continue;
                            }

                            $html_courses_session = '';
                            $count_courses_session = 0;

                            foreach ($session['courses'] as $course) {
                                //read only and accesible
                                if (api_get_setting('hide_courses_in_sessions') == 'false') {
                                    $html_courses_session .= CourseManager :: get_logged_user_course_html($course, $session_id, $load_directories_preview);
                                }
                                $count_courses_session++;
                            }

                            if ($count_courses_session > 0) {
                                $params = array();
                                $params['icon'] = Display::return_icon('window_list.png', $session['session_name'], array('id' => 'session_img_'.$session_id), ICON_SIZE_LARGE);
                                $params['is_session'] = true;
                                //Default session name
                                $session_link = $session['session_name'];
                                $params['link'] = null;

                                if (api_get_setting('session_page_enabled') == 'true' && !api_is_drh()) {
                                    //session name with link
                                    $session_link = Display::tag('a', $session['session_name'], array('href' => api_get_path(WEB_CODE_PATH).'session/index.php?session_id='.$session_id));
                                    $params['link'] = api_get_path(WEB_CODE_PATH).'session/index.php?session_id='.$session_id;
                                }

                                $params['title'] = $session_link;

                                $moved_status = SessionManager::get_session_change_user_reason($session['moved_status']);
                                $moved_status = isset($moved_status) && !empty($moved_status) ? ' ('.$moved_status.')' : null;

                                $params['subtitle'] = isset($session['coach_info']) ? $session['coach_info']['complete_name'] : null.$moved_status;
                                $params['dates'] = $session['date_message'];

                                $params['right_actions'] = '';
                                if (api_is_platform_admin()) {
                                    $params['right_actions'] .= '<a href="'.api_get_path(WEB_CODE_PATH).'admin/resume_session.php?id_session='.$session_id.'">';
                                    $params['right_actions'] .= Display::return_icon('edit.png', get_lang('Edit'), array('align' => 'absmiddle'), ICON_SIZE_SMALL).'</a>';
                                }

                                if (api_get_setting('hide_courses_in_sessions') == 'false') {
                                    //    $params['extra'] .=  $html_courses_session;
                                }
                                $sessions_with_no_category .= CourseManager::course_item_parent(CourseManager::course_item_html($params, true), $html_courses_session);
                            }
                        }
                    }
                } else {
                    // All sessions included in
                    $count_courses_session = 0;
                    $html_sessions = '';
                    foreach ($session_category['sessions'] as $session) {
                        $session_id = $session['session_id'];
                        // Don't show empty sessions.
                        if (count($session['courses']) < 1) {
                            continue;
                        }

                        $html_courses_session = '';
                        $count = 0;
                        foreach ($session['courses'] as $course) {
                            if (api_get_setting('hide_courses_in_sessions') == 'false') {
                                $html_courses_session .= CourseManager :: get_logged_user_course_html($course, $session_id);
                            }
                            $count_courses_session++;
                            $count++;
                        }

                        $params = array();
                        if ($count > 0) {
                            $params['icon'] = Display::return_icon('window_list.png', $session['session_name'], array('id' => 'session_img_'.$session_id), ICON_SIZE_LARGE);

                            //Default session name
                            $session_link = $session['session_name'];
                            $params['link'] = null;

                            if (api_get_setting('session_page_enabled') == 'true' && !api_is_drh()) {
                                //session name with link
                                $session_link = Display::tag('a', $session['session_name'], array('href' => api_get_path(WEB_CODE_PATH).'session/index.php?session_id='.$session_id));
                                $params['link'] = api_get_path(WEB_CODE_PATH).'session/index.php?session_id='.$session_id;
                            }


                            $params['title'] .= $session_link;

                            $moved_status = SessionManager::get_session_change_user_reason($session['moved_status']);
                            $moved_status = isset($moved_status) && !empty($moved_status) ? ' ('.$moved_status.')' : null;

                            $params['subtitle'] = isset($session['coach_info']) ? $session['coach_info']['complete_name'] : null.$moved_status;
                            $params['dates'] = $session['date_message'];

                            if (api_is_platform_admin()) {
                                $params['right_actions'] .= '<a href="'.api_get_path(WEB_CODE_PATH).'admin/resume_session.php?id_session='.$session_id.'">'.Display::return_icon('edit.png', get_lang('Edit'), array('align' => 'absmiddle'), ICON_SIZE_SMALL).'</a>';
                            }
                            $html_sessions .= CourseManager::course_item_html($params, true).$html_courses_session;
                        }
                    }

                    if ($count_courses_session > 0) {
                        $params = array();
                        $params['icon'] = Display::return_icon('folder_blue.png', $session_category['session_category']['name'], array(), ICON_SIZE_LARGE);

                        if (api_is_platform_admin()) {
                            $params['right_actions'] .= '<a href="'.api_get_path(WEB_CODE_PATH).'admin/session_category_edit.php?&id='.$session_category['session_category']['id'].'">'.Display::return_icon('edit.png', get_lang('Edit'), array(), ICON_SIZE_SMALL).'</a>';
                        }

                        $params['title'] .= $session_category['session_category']['name'];

                        if (api_is_platform_admin()) {
                            $params['link'] = api_get_path(WEB_CODE_PATH).'admin/session_category_edit.php?&id='.$session_category['session_category']['id'];
                        }

                        $session_category_start_date = $session_category['session_category']['date_start'];
                        $session_category_end_date = $session_category['session_category']['date_end'];

                        if (!empty($session_category_start_date) && $session_category_start_date != '0000-00-00' && !empty($session_category_end_date) && $session_category_end_date != '0000-00-00') {
                            $params['subtitle'] = sprintf(get_lang('FromDateXToDateY'), $session_category['session_category']['date_start'], $session_category['session_category']['date_end']);
                        } else {
                            if (!empty($session_category_start_date) && $session_category_start_date != '0000-00-00') {
                                $params['subtitle'] = get_lang('From').' '.$session_category_start_date;
                            }
                            if (!empty($session_category_end_date) && $session_category_end_date != '0000-00-00') {
                                $params['subtitle'] = get_lang('Until').' '.$session_category_end_date;
                            }
                        }
                        $sessions_with_category .= CourseManager::course_item_parent(CourseManager::course_item_html($params, true), $html_sessions);
                    }
                }
            }
        }
        return $sessions_with_category.$sessions_with_no_category.$courses_html.$special_courses;
    }

    /**
     * Shows a welcome message when the user doesn't have any content in
     * the course list
     * @param object A Template object used to declare variables usable in the given template
     * @return void
     * @assert () === false
     */
    static function return_welcome_to_course_block($tpl) {
        if (empty($tpl)) {
            return false;
        }
        $count_courses = CourseManager::count_courses();

        $course_catalog_url = api_get_path(WEB_CODE_PATH).'auth/courses.php';
        $course_list_url = api_get_path(WEB_PATH).'user_portal.php';

        $tpl->assign('course_catalog_url', $course_catalog_url);
        $tpl->assign('course_list_url', $course_list_url);
        $tpl->assign('course_catalog_link', Display::url(get_lang('here'), $course_catalog_url));
        $tpl->assign('course_list_link', Display::url(get_lang('here'), $course_list_url));
        $tpl->assign('count_courses', $count_courses);
        $tpl->assign('welcome_to_course_block', 1);
    }

    static function return_debug() {
        global $app;
        $mtime = microtime();
        $mtime = explode(" ",$mtime);
        $mtime = $mtime[1] + $mtime[0];

        $message = "22---Page loaded in:".($mtime-START);
        $app['monolog']->addInfo($message);
        $message = "memory_get_usage: ".format_file_size(memory_get_usage(true));
        $app['monolog']->addInfo($message);
        $message = "memory_get_peak_usage: ".format_file_size(memory_get_peak_usage(true));
        $app['monolog']->addInfo($message);
    }
}
