<?php
/* For licensing terms, see /license.txt */

/**
 * Class IndexManager
 */
class IndexManager
{
    const VIEW_BY_DEFAULT = 0;
    const VIEW_BY_SESSION = 1;

    // An instance of the template engine
    public $tpl = false;
    public $name = '';
    public $home = '';
    public $default_home = 'home/';

    /**
     * Construct
     * @param string $title
     */
    public function __construct($title)
    {
        $this->tpl = new Template($title);
        $this->home = api_get_home_path();
        $this->user_id = api_get_user_id();
        $this->load_directories_preview = false;

        if (api_get_setting('show_documents_preview') == 'true') {
            $this->load_directories_preview = true;
        }
    }

    /**
     * @param bool $setLoginForm
     */
    function set_login_form($setLoginForm = true)
    {
        global $loginFailed;
        $this->tpl->setLoginForm($setLoginForm);
    }

    function return_exercise_block($personal_course_list)
    {
        $exercise_list = array();
        if (!empty($personal_course_list)) {
            foreach($personal_course_list as  $course_item) {
                $course_code = $course_item['c'];
                $session_id = $course_item['id_session'];

                $exercises = ExerciseLib::get_exercises_to_be_taken(
                    $course_code,
                    $session_id
                );

                foreach($exercises as $exercise_item) {
                    $exercise_item['course_code']     = $course_code;
                    $exercise_item['session_id']     = $session_id;
                    $exercise_item['tms']     = api_strtotime($exercise_item['end_time'], 'UTC');

                    $exercise_list[] = $exercise_item;
                }
            }
            if (!empty($exercise_list)) {
                $exercise_list = msort($exercise_list, 'tms');
                $my_exercise = $exercise_list[0];
                $url = Display::url($my_exercise['title'], api_get_path(WEB_CODE_PATH).'exercice/overview.php?exerciseId='.$my_exercise['id'].'&cidReq='.$my_exercise['course_code'].'&id_session='.$my_exercise['session_id']);
                $this->tpl->assign('exercise_url', $url);
                $this->tpl->assign('exercise_end_date', api_convert_and_format_date($my_exercise['end_time'], DATE_FORMAT_SHORT));
            }
        }
    }

    function return_announcements($show_slide = true)
    {
        //// Display System announcements
        $hideAnnouncements = api_get_setting('hide_global_announcements_when_not_connected');
        if ($hideAnnouncements == 'true' && empty($userId)) {
            return null;
        }
        $announcement = isset($_GET['announcement']) ? $_GET['announcement'] : null;
        $announcement = intval($announcement);

        if (!api_is_anonymous() && $this->user_id) {
            $visibility = api_is_allowed_to_create_course() ? SystemAnnouncementManager::VISIBLE_TEACHER : SystemAnnouncementManager::VISIBLE_STUDENT;
            if ($show_slide) {
                $announcements = SystemAnnouncementManager:: display_announcements_slider(
                    $visibility,
                    $announcement
                );
            } else {
                $announcements = SystemAnnouncementManager:: display_all_announcements(
                    $visibility,
                    $announcement
                );
            }
        } else {
            if ($show_slide) {
                $announcements = SystemAnnouncementManager:: display_announcements_slider(
                    SystemAnnouncementManager::VISIBLE_GUEST,
                    $announcement
                );
            } else {
                $announcements = SystemAnnouncementManager:: display_all_announcements(
                    SystemAnnouncementManager::VISIBLE_GUEST,
                    $announcement
                );
            }
        }

        return $announcements;
    }

    /**
     * Alias for the online_logout() function
     * @param   bool    $redirect   Whether to ask online_logout to redirect to index.php or not
     */
    function logout($redirect = true)
    {
        online_logout($this->user_id, true);
    }

    /**
     * This function checks if there are courses that are open to the world in the platform course categories (=faculties)
     *
     * @param string $category
     * @return boolean
     */
    function category_has_open_courses($category)
    {
        $setting_show_also_closed_courses = api_get_setting('show_closed_courses') == 'true';
        $main_course_table = Database :: get_main_table(TABLE_MAIN_COURSE);
        $category = Database::escape_string($category);
        $sql_query = "SELECT * FROM $main_course_table WHERE category_code='$category'";
        $sql_result = Database::query($sql_query);
        while ($course = Database::fetch_array($sql_result)) {
            if (!$setting_show_also_closed_courses) {
                if ((api_get_user_id() > 0 && $course['visibility'] == COURSE_VISIBILITY_OPEN_PLATFORM) || ($course['visibility'] == COURSE_VISIBILITY_OPEN_WORLD)) {
                    return true; //at least one open course
                }
            } else {
                if (isset($course['visibility'])) {
                    return true; // At least one course (it does not matter weither it's open or not because $setting_show_also_closed_courses = true).
                }
            }
        }

        return false;
    }

    function return_teacher_link()
    {
        $html = '';
        $show_menu = false;
        if (!empty($this->user_id)) {
            // tabs that are deactivated are added here

            $show_menu = false;
            $show_create_link = false;
            $show_course_link = false;

            if (api_is_platform_admin() || api_is_course_admin() || api_is_allowed_to_create_course()) {
                $show_menu = true;
                $show_course_link = true;
                $show_create_link = true;
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

        if ($show_menu) {
            $html .= '<ul class="nav nav-pills nav-stacked">';
            if ($show_create_link) {
                $html .= '<li class="add-course"><a href="' . api_get_path(WEB_CODE_PATH) . 'create_course/add_course.php">'.Display::return_icon('new-course.png',  get_lang('CourseCreate')).(api_get_setting('course_validation') == 'true' ? get_lang('CreateCourseRequest') : get_lang('CourseCreate')).'</a></li>';
            }

            if ($show_course_link) {
                if (!api_is_drh() && !api_is_session_admin()) {
                    $html .=  '<li class="list-course"><a href="'. api_get_path(WEB_CODE_PATH) . 'auth/courses.php">'. Display::return_icon('catalog-course.png', get_lang('CourseCatalog')) .get_lang('CourseCatalog').'</a></li>';
                } else {
                    $html .= '<li><a href="' . api_get_path(WEB_CODE_PATH) . 'dashboard/index.php">'.get_lang('Dashboard').'</a></li>';
                }
            }
            $html .= '</ul>';
        }

        if (!empty($html)) {
            $html = self::show_right_block(get_lang('Courses'), $html, 'teacher_block', null, 'teachers', 'teachersCollapse');
        }

        return $html;
    }

    /**
     * Includes a created page
     * @return string
     */
    public function return_home_page()
    {
        $userId = api_get_user_id();
        // Including the page for the news
        $html = '';

        if (!empty($_GET['include']) && preg_match('/^[a-zA-Z0-9_-]*\.html$/', $_GET['include'])) {
            $open = @(string)file_get_contents(api_get_path(SYS_PATH).$this->home.$_GET['include']);
            $html = api_to_system_encoding($open, api_detect_encoding(strip_tags($open)));
        } else {
            // Hiding home top when user not connected.
            $hideTop = api_get_setting('hide_home_top_when_connected');
            if ($hideTop == 'true' && !empty($userId)) {
                return $html;
            }

            if (!empty($_SESSION['user_language_choice'])) {
                $user_selected_language = $_SESSION['user_language_choice'];
            } elseif (!empty($_SESSION['_user']['language'])) {
                $user_selected_language = $_SESSION['_user']['language'];
            } else {
                $user_selected_language = api_get_setting('platformLanguage');
            }

            // Try language specific home
            if (file_exists($this->home.'home_top_'.$user_selected_language.'.html')) {
                $home_top_temp = file_get_contents($this->home.'home_top_'.$user_selected_language.'.html');
            }

            // Try default language home
            if (empty($home_top_temp)) {
                if (file_exists($this->home.'home_top.html')) {
                    $home_top_temp = file_get_contents($this->home.'home_top.html');
                } else {
                    if (file_exists($this->default_home.'home_top.html')) {
                        $home_top_temp = file_get_contents($this->default_home . 'home_top.html');
                    }
                }
            }

			if (trim($home_top_temp) == '' && api_is_platform_admin()) {
				$home_top_temp = '<div class="welcome-mascot">' . get_lang('PortalHomepageDefaultIntroduction') . '</div>';
			} else {
				$home_top_temp = '<div class="welcome-home-top-temp">' . $home_top_temp . '</div>';
			}
			$open = str_replace('{rel_path}', api_get_path(REL_PATH), $home_top_temp);
			$html = api_to_system_encoding($open, api_detect_encoding(strip_tags($open)));
		}

		return $html;
	}

    function return_notice()
    {
        $sys_path = api_get_path(SYS_PATH);
        $user_selected_language = api_get_interface_language();

        $html = '';
        // Notice
        $home_notice = @(string)file_get_contents($sys_path.$this->home.'home_notice_'.$user_selected_language.'.html');
        if (empty($home_notice)) {
            $home_notice = @(string)file_get_contents($sys_path.$this->home.'home_notice.html');
        }

        if (!empty($home_notice)) {
            $home_notice = api_to_system_encoding($home_notice, api_detect_encoding(strip_tags($home_notice)));
            //$home_notice = Display::div($home_notice, array('class'  => 'homepage_notice'));
            $html = self::show_right_block(get_lang('Notice'), $home_notice, 'notice_block', null, 'notices', 'noticesCollapse');
        }
        return $html;
    }

    function return_help()
    {
        $user_selected_language = api_get_interface_language();
        $sys_path               = api_get_path(SYS_PATH);
        $platformLanguage       = api_get_setting('platformLanguage');

        // Help section.
        /* Hide right menu "general" and other parts on anonymous right menu. */

        if (!isset($user_selected_language)) {
            $user_selected_language = $platformLanguage;
        }

        $html = null;
        $home_menu = @(string)file_get_contents($sys_path.$this->home.'home_menu_'.$user_selected_language.'.html');
        if (!empty($home_menu)) {
            $home_menu_content = '<ul class="nav nav-pills nav-stacked">';
            $home_menu_content .= api_to_system_encoding($home_menu, api_detect_encoding(strip_tags($home_menu)));
            $home_menu_content .= '</ul>';
            $html .= self::show_right_block(get_lang('MenuGeneral'), $home_menu_content, 'help_block', null, 'helps', 'helpsCollapse');
        }
        return $html;
    }

    function return_skills_links()
    {
        $content = '';
        $content .= '<ul class="nav nav-pills nav-stacked">';
        /**
         * Generate the block for show a panel with links to My Certificates and Certificates Search pages
         * @return string The HTML code for the panel
         */
        $certificatesItem = null;

        if (!api_is_anonymous()) {
            $certificatesItem = Display::tag(
                'li',
                Display::url(Display::return_icon('graduation.png',get_lang('MyCertificates'),null,ICON_SIZE_SMALL).
                    get_lang('MyCertificates'),
                    api_get_path(WEB_CODE_PATH) . "gradebook/my_certificates.php"
                )
            );
        }

        $searchItem = null;

        if (api_get_setting('allow_public_certificates') == 'true') {
            $searchItem = Display::tag(
                'li',
                Display::url(Display::return_icon('search_graduation.png',get_lang('Search'),null,ICON_SIZE_SMALL).
                    get_lang('Search'),
                    api_get_path(WEB_CODE_PATH) . "gradebook/search.php"
                )
            );
        }

        if (empty($certificatesItem) && empty($searchItem)) {
            return null;
        }else{
            $content.= $certificatesItem;
            $content.= $searchItem;
        }

        if (api_get_setting('allow_skills_tool') == 'true') {

            $content .= Display::tag('li', Display::url(Display::return_icon('skill-badges.png',get_lang('MySkills'),null,ICON_SIZE_SMALL).get_lang('MySkills'), api_get_path(WEB_CODE_PATH).'social/my_skills_report.php'));
            $allowSkillsManagement = api_get_setting('allow_hr_skills_management') == 'true';
            if (($allowSkillsManagement && api_is_drh()) || api_is_platform_admin()) {
                $content .= Display::tag('li',
                    Display::url(Display::return_icon('edit-skill.png', get_lang('MySkills'), null,
                            ICON_SIZE_SMALL) . get_lang('ManageSkills'),
                        api_get_path(WEB_CODE_PATH) . 'admin/skills_wheel.php'));
            }
        }
        $content .= '</ul>';
        $html = self::show_right_block(
            get_lang("Skills"),
            $content,
            'skill_block',
            null,
            'skills',
            'skillsCollapse'
        );

        return $html;
    }

    /**
     * Reacts on a failed login:
     * Displays an explanation with a link to the registration form.
     *
     * @version 1.0.1
     */
    function handle_login_failed()
    {
        return $this->tpl->handleLoginFailed();
    }

    /**
     * Display list of courses in a category.
     * (for anonymous users)
     *
     * @version 1.1
     * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University - refactoring and code cleaning
     * @author Julio Montoya <gugli100@gmail.com>, Beeznest template modifs
     */
    function return_courses_in_categories()
    {
        $result = '';
        $stok = Security::get_token();

        // Initialization.
        $user_identified = (api_get_user_id() > 0 && !api_is_anonymous());
        $web_course_path = api_get_path(WEB_COURSE_PATH);
        $category = Database::escape_string($_GET['category']);
        $setting_show_also_closed_courses = api_get_setting('show_closed_courses') == 'true';

        // Database table definitions.
        $main_course_table      = Database :: get_main_table(TABLE_MAIN_COURSE);
        $main_category_table    = Database :: get_main_table(TABLE_MAIN_CATEGORY);

        // Get list of courses in category $category.
        $sql_get_course_list = "SELECT * FROM $main_course_table cours
                                    WHERE category_code = '".Database::escape_string($_GET['category'])."'
                                    ORDER BY title, UPPER(visual_code)";

        // Showing only the courses of the current access_url_id.
        if (api_is_multiple_url_enabled()) {
            $url_access_id = api_get_current_access_url_id();
            if ($url_access_id != -1) {
                $tbl_url_rel_course = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_COURSE);
                $sql_get_course_list = "SELECT * FROM $main_course_table as course
                        INNER JOIN $tbl_url_rel_course as url_rel_course
                        ON (url_rel_course.c_id = course.id)
                        WHERE
                            access_url_id = $url_access_id AND
                            category_code = '".Database::escape_string($_GET['category'])."'
                        ORDER BY title, UPPER(visual_code)";
            }
        }

        // Removed: AND cours.visibility='".COURSE_VISIBILITY_OPEN_WORLD."'
        $sql_result_courses = Database::query($sql_get_course_list);

        while ($course_result = Database::fetch_array($sql_result_courses)) {
            $course_list[] = $course_result;
        }

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
                    SELECT  t1.name,
                            t1.code,
                            t1.parent_id,
                            t1.children_count,COUNT(DISTINCT t3.code) AS nbCourse
                    FROM $main_category_table t1
                    LEFT JOIN $main_category_table t2 ON t1.code=t2.parent_id
                    LEFT JOIN $main_course_table t3 ON (t3.category_code = t1.code $platform_visible_courses)
                    WHERE t1.parent_id ". (empty ($category) ? "IS NULL" : "='$category'")."
                    GROUP BY t1.name,t1.code,t1.parent_id,t1.children_count ORDER BY t1.tree_pos, t1.name";

        // Showing only the category of courses of the current access_url_id
        if (api_is_multiple_url_enabled()) {
            $courseCategoryCondition = null;
            if (isMultipleUrlSupport()) {
                $table = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_COURSE_CATEGORY);
                $courseCategoryCondition = " INNER JOIN $table a ON (t1.id = a.course_category_id)";
            }

            $url_access_id = api_get_current_access_url_id();
            if ($url_access_id != -1) {
                $tbl_url_rel_course = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_COURSE);
                $sqlGetSubCatList = "
                    SELECT t1.name,
                            t1.code,
                            t1.parent_id,
                            t1.children_count,
                            COUNT(DISTINCT t3.code) AS nbCourse
                    FROM $main_category_table t1
                    $courseCategoryCondition
                    LEFT JOIN $main_category_table t2 ON t1.code = t2.parent_id
                    LEFT JOIN $main_course_table t3 ON (t3.category_code = t1.code $platform_visible_courses)
                    INNER JOIN $tbl_url_rel_course as url_rel_course
                    ON (url_rel_course.c_id = t3.id)
                    WHERE
                        url_rel_course.access_url_id = $url_access_id AND
                        t1.parent_id ".(empty($category) ? "IS NULL" : "='$category'")."
                    GROUP BY t1.name,t1.code,t1.parent_id,t1.children_count
                    ORDER BY t1.tree_pos, t1.name";
            }
        }

        $resCats = Database::query($sqlGetSubCatList);
        $thereIsSubCat = false;
        if (Database::num_rows($resCats) > 0) {
            $htmlListCat = Display::page_header(get_lang('CatList'));
            $htmlListCat .= '<ul>';
            $htmlTitre = '';
            while ($catLine = Database::fetch_array($resCats)) {
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
                } elseif (api_get_setting('show_empty_course_categories') == 'true') {
                    /* End changed code to eliminate the (0 courses) after empty categories. */
                      $htmlListCat .= '<li>';
                    $htmlListCat .= $catLine['name'];
                    $htmlListCat .= "</li>";
                    $thereIsSubCat = true;
                } // Else don't set thereIsSubCat to true to avoid printing things if not requested.
                // TODO: deprecate this useless feature - this includes removing system variable
                if (empty($htmlTitre)) {
                    $htmlTitre = '<p>';
                    if (api_get_setting('show_back_link_on_top_of_tree') == 'true') {
                        $htmlTitre .= '<a href="'.api_get_self().'">&lt;&lt; '.get_lang('BackToHomePage').'</a>';
                    }
                    $htmlTitre .= "</p>";
                }
            }
            $htmlListCat .= "</ul>";
        }
        $result .= $htmlTitre;
        if ($thereIsSubCat) {
            $result .=  $htmlListCat;
        }
        while ($categoryName = Database::fetch_array($resCats)) {
            $result .= '<h3>' . $categoryName['name'] . "</h3>\n";
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
                if ($course['visibility'] == COURSE_VISIBILITY_HIDDEN) { continue; }
                if (!$setting_show_also_closed_courses) {
                    // If we do not show the closed courses
                    // we only show the courses that are open to the world (to everybody)
                    // and the courses that are open to the platform (if the current user is a registered user.
                    if (($user_identified && $course['visibility'] == COURSE_VISIBILITY_OPEN_PLATFORM) || ($course['visibility'] == COURSE_VISIBILITY_OPEN_WORLD)) {
                        $courses_shown++;
                        $courses_list_string .= "<li>";
                        $courses_list_string .= '<a href="'.$web_course_path.$course['directory'].'/">'.$course['title'].'</a><br />';
                        $course_details = array();
                        if (api_get_setting('display_coursecode_in_courselist') == 'true') {
                            $course_details[] = $course['visual_code'];
                        }
                        if (api_get_setting('display_teacher_in_courselist') == 'true') {
                            $course_details[] = CourseManager::get_teacher_list_from_course_code_to_string($course['code']);
                        }
                        if (api_get_setting('show_different_course_language') == 'true' && $course['course_language'] != api_get_setting('platformLanguage')) {
                            $course_details[] = $course['course_language'];
                        }
                        $courses_list_string .= implode(' - ', $course_details);
                        $courses_list_string .= "</li>";
                    }
                } else {
                    // We DO show the closed courses.
                    // The course is accessible if (link to the course homepage):
                    // 1. the course is open to the world (doesn't matter if the user is logged in or not): $course['visibility'] == COURSE_VISIBILITY_OPEN_WORLD);
                    // 2. the user is logged in and the course is open to the world or open to the platform: ($user_identified && $course['visibility'] == COURSE_VISIBILITY_OPEN_PLATFORM);
                    // 3. the user is logged in and the user is subscribed to the course and the course visibility is not COURSE_VISIBILITY_CLOSED;
                    // 4. the user is logged in and the user is course admin of te course (regardless of the course visibility setting);
                    // 5. the user is the platform admin api_is_platform_admin().

                    $courses_shown++;
                    $courses_list_string .= "<li>";
                    if ($course['visibility'] == COURSE_VISIBILITY_OPEN_WORLD
                        || ($user_identified && $course['visibility'] == COURSE_VISIBILITY_OPEN_PLATFORM)
                        || ($user_identified && array_key_exists($course['code'], $courses_of_user)
                            && $course['visibility'] != COURSE_VISIBILITY_CLOSED)
                        || $courses_of_user[$course['code']]['status'] == '1'
                        || api_is_platform_admin()) {
                            $courses_list_string .= '<a href="'.$web_course_path.$course['directory'].'/">';
                        }
                    $courses_list_string .= $course['title'];
                    if ($course['visibility'] == COURSE_VISIBILITY_OPEN_WORLD
                        || ($user_identified && $course['visibility'] == COURSE_VISIBILITY_OPEN_PLATFORM)
                        || ($user_identified && array_key_exists($course['code'], $courses_of_user)
                            && $course['visibility'] != COURSE_VISIBILITY_CLOSED)
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
                        if (!empty($course['tutor_name'])) {
                            $course_details[] = $course['tutor_name'];
                        }
                    }
                    if (api_get_setting('show_different_course_language') == 'true' && $course['course_language'] != api_get_setting('platformLanguage')) {
                        $course_details[] = $course['course_language'];
                    }

                    $courses_list_string .= implode(' - ', $course_details);
                    // We display a subscription link if:
                    // 1. it is allowed to register for the course and if the course is not already in the courselist of the user and if the user is identiefied
                    // 2.
                    if ($user_identified && !array_key_exists($course['code'], $courses_of_user)) {
                        if ($course['subscribe'] == '1') {
                            $courses_list_string .= '&nbsp;<a class="btn btn-primary" href="main/auth/courses.php?action=subscribe_course&sec_token='.$stok.'&subscribe_course='.$course['code'].'&category_code='.Security::remove_XSS($_GET['category']).'">'.get_lang('Subscribe').'</a><br />';
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
            $result .=  $courses_list_string;
        }
        if ($category != '') {
            $result .=  '<p><a href="'.api_get_self().'"> ' .
                Display :: return_icon('back.png', get_lang('BackToHomePage')).
                get_lang('BackToHomePage') . '</a></p>';
        }
        return $result;
    }

    /**
    * retrieves all the courses that the user has already subscribed to
    * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University, Belgium
    * @param int $user_id: the id of the user
    * @return array an array containing all the information of the courses of the given user
    */
    public function get_courses_of_user($user_id)
    {
        $table_course = Database::get_main_table(TABLE_MAIN_COURSE);
        $table_course_user = Database::get_main_table(TABLE_MAIN_COURSE_USER);
        // Secondly we select the courses that are in a category (user_course_cat <> 0)
        // and sort these according to the sort of the category
        $user_id = intval($user_id);
        $sql_select_courses = "SELECT
            course.code k,
            course.visual_code vc,
            course.subscribe subscr,
            course.unsubscribe unsubscr,
            course.title i,
            course.tutor_name t,
            course.directory dir,
            course_rel_user.status status,
            course_rel_user.sort sort,
            course_rel_user.user_course_cat user_course_cat
            FROM
                $table_course course,
                $table_course_user course_rel_user
            WHERE
                course.id = course_rel_user.c_id AND
                course_rel_user.user_id = '".$user_id."' AND
                course_rel_user.relation_type <> ".COURSE_RELATION_TYPE_RRHH."
            ORDER BY course_rel_user.sort ASC";
        $result = Database::query($sql_select_courses);
        $courses = array();
        while ($row = Database::fetch_array($result)) {
            // We only need the database name of the course.
            $courses[$row['k']] = array(
                'code' => $row['k'],
                'visual_code' => $row['vc'],
                'title' => $row['i'],
                'directory' => $row['dir'],
                'status' => $row['status'],
                'tutor' => $row['t'],
                'subscribe' => $row['subscr'],
                'unsubscribe' => $row['unsubscr'],
                'sort' => $row['sort'],
                'user_course_category' => $row['user_course_cat']
            );
        }
        return $courses;
    }

    /**
     * @todo use the template system
     */
    function show_right_block($title, $content, $id = null, $params = null, $idAccordion = null, $idCollpase = null)
    {
        if (!empty($idAccordion)) {
            $html = null;
            $html .= '<div class="panel-group" id="'.$idAccordion.'" role="tablist" aria-multiselectable="true">' . PHP_EOL;
            $html .= '<div class="panel panel-default" id="'.$id.'">' . PHP_EOL;
            $html .= '<div class="panel-heading" role="tab"><h4 class="panel-title">' . PHP_EOL;
            $html .= '<a role="button" data-toggle="collapse" data-parent="#'.$idAccordion.'" href="#'.$idCollpase.'" aria-expanded="true" aria-controls="'.$idCollpase.'">'.$title.'</a>' . PHP_EOL;
            $html .= '</h4></div>' . PHP_EOL;
            $html .= '<div id="'.$idCollpase.'" class="panel-collapse collapse in" role="tabpanel">' . PHP_EOL;
            $html .= '<div class="panel-body">'.$content.'</div>' . PHP_EOL;
            $html .= '</div></div></div>' . PHP_EOL;

        } else {
            if (!empty($id)) {
                $params['id'] = $id;
            }
            $params['class'] = 'panel panel-default';
            $html = null;
            if (!empty($title)) {
                $html .= '<div class="panel-heading">'.$title.'</div>' . PHP_EOL;
            }
            $html.= '<div class="panel-body">'.$content.'</div>' . PHP_EOL;
            $html = Display::div($html, $params);
        }
        return $html;
    }

    /**
     * Adds a form to let users login
     * @version 1.1
     */
    public function display_login_form()
    {
        return $this->tpl->displayLoginForm();
    }

    /**
     * @todo use FormValidator
     * @return string
     */
    public function return_search_block()
    {
        $html = '';
        if (api_get_setting('search_enabled') == 'true') {
            $search_btn = get_lang('Search');
            $search_content = '<form action="main/search/" method="post">
                <div class="form-group">
                <input type="text" id="query" class="form-control" name="query" value="" />
                <button class="btn btn-default" type="submit" name="submit" value="'.$search_btn.'" />'.$search_btn.' </button>
                </div></form>';
            $html .= self::show_right_block(get_lang('Search'), $search_content, 'search_block');
        }

        return $html;
    }

    /**
     * @return string
     */
    public function return_classes_block()
    {
        $html = '';
        if (api_get_setting('show_groups_to_users') == 'true') {
            $usergroup = new UserGroup();
            $usergroup_list = $usergroup->get_usergroup_by_user(api_get_user_id());
            $classes = '';
            if (!empty($usergroup_list)) {
                foreach($usergroup_list as $group_id) {
                    $data = $usergroup->get($group_id);
                    $data['name'] = Display::url($data['name'], api_get_path(WEB_CODE_PATH).'user/classes.php?id='.$data['id']);
                    $classes .= Display::tag('li', $data['name']);
                }
            }
            if (api_is_platform_admin()) {
                $classes .= Display::tag(
                    'li',
                    Display::url(get_lang('AddClasses') ,api_get_path(WEB_CODE_PATH).'admin/usergroups.php?action=add')
                );
            }
            if (!empty($classes)) {
                $classes = Display::tag('ul', $classes, array('class'=>'nav nav-pills nav-stacked'));
                $html .= self::show_right_block(get_lang('Classes'), $classes, 'classes_block');
            }
        }
        return $html;
    }

    /**
     * @return null|string
     */
    public function return_user_image_block()
    {
        $html = null;
        if (!api_is_anonymous()) {
            $userPicture = UserManager::getUserPicture(api_get_user_id());
            $content = null;

            if (api_get_setting('allow_social_tool') == 'true') {
                $content .= '<a style="text-align:center" href="' . api_get_path(WEB_PATH) . 'main/social/home.php">
                <img class="img-circle" src="' . $userPicture . '" ></a>';
            } else {
                $content .= '<a style="text-align:center" href="' . api_get_path(WEB_PATH) . 'main/auth/profile.php">
                <img class="img-circle" title="' . get_lang('EditProfile') . '" src="' . $userPicture. '" ></a>';
            }

            $html = self::show_right_block(
                null,
                $content,
                'user_image_block',
                array('style' => 'text-align:center;')
            );
        }

        return $html;
    }

    /**
     * @return null|string|void
     */
    public function return_profile_block()
    {
        global $_configuration;
        $user_id = api_get_user_id();

        if (empty($user_id)) {
            return;
        }

        $userGroup = new UserGroup();

        $profile_content = '<ul class="nav nav-pills nav-stacked">';

        //  @todo Add a platform setting to add the user image.
        if (api_get_setting('allow_message_tool') == 'true') {
            // New messages.
            $number_of_new_messages = MessageManager::get_new_messages();
            // New contact invitations.
            $number_of_new_messages_of_friend = SocialManager::get_message_number_invitation_by_user_id(api_get_user_id());

            // New group invitations sent by a moderator.
            $group_pending_invitations = $userGroup->get_groups_by_user(
                api_get_user_id(),
                GROUP_USER_PERMISSION_PENDING_INVITATION,
                false
            );
            $group_pending_invitations = count($group_pending_invitations);

            $total_invitations = $number_of_new_messages_of_friend + $group_pending_invitations;
            $cant_msg = Display::badge($number_of_new_messages);

            $link = '';
            if (api_get_setting('allow_social_tool') == 'true') {
                $link = '?f=social';
            }
            $profile_content .= '<li class="inbox-message-social"><a href="'.api_get_path(WEB_PATH).'main/messages/inbox.php'.$link.'">'.Display::return_icon('inbox.png',get_lang('Inbox'),null,ICON_SIZE_SMALL).get_lang('Inbox').$cant_msg.' </a></li>';
            $profile_content .= '<li class="new-message-social"><a href="'.api_get_path(WEB_PATH).'main/messages/new_message.php'.$link.'">'.Display::return_icon('new-message.png',get_lang('Compose'),null,ICON_SIZE_SMALL).get_lang('Compose').' </a></li>';

            if (api_get_setting('allow_social_tool') == 'true') {
                $total_invitations = Display::badge($total_invitations);
                $profile_content .= '<li class="invitations-social"><a href="'.api_get_path(WEB_PATH).'main/social/invitations.php">'.Display::return_icon('invitations.png',get_lang('PendingInvitations'),null,ICON_SIZE_SMALL).get_lang('PendingInvitations').$total_invitations.'</a></li>';
            }

            if (isset($_configuration['allow_my_files_link_in_homepage']) && $_configuration['allow_my_files_link_in_homepage']) {
                $profile_content .= '<li class="myfiles-social"><a href="'.api_get_path(WEB_PATH).'main/social/myfiles.php">'.get_lang('MyFiles').'</a></li>';
            }
        }

        $editProfileUrl = Display::getProfileEditionLink($user_id);

        $profile_content .= '<li class="profile-social"><a href="' . $editProfileUrl . '">'.Display::return_icon('edit-profile.png',get_lang('EditProfile'),null,ICON_SIZE_SMALL).get_lang('EditProfile').'</a></li>';
        $profile_content .= '</ul>';
        $html = self::show_right_block(
            get_lang('Profile'),
            $profile_content,
            'profile_block',
            null,
            'profile',
            'profileCollapse'
        );

        return $html;
    }

    public function return_navigation_links()
    {
        $html = '';

        // Deleting the myprofile link.
        if (api_get_setting('allow_social_tool') == 'true') {
            unset($this->tpl->menu_navigation['myprofile']);
        }

        // Main navigation section.
        // Tabs that are deactivated are added here.
        if (!empty($this->tpl->menu_navigation)) {
            $content = '<ul class="nav nav-pills nav-stacked">';
            foreach ($this->tpl->menu_navigation as $section => $navigation_info) {
                $current = $section == $GLOBALS['this_section'] ? ' id="current"' : '';
                $content .= '<li'.$current.'>';
                $content .= '<a href="'.$navigation_info['url'].'" target="_self">'.$navigation_info['title'].'</a>';
                $content .= '</li>';
            }
            $content .= '</ul>';
            $html = self::show_right_block(get_lang('MainNavigation'), $content, 'navigation_link_block');
        }
        return $html;
    }

    /**
     * @return null|string
     */
    public function return_course_block()
    {
        $html = '';

        $show_create_link = false;
        $show_course_link = false;

        if ((api_get_setting('allow_users_to_create_courses') == 'false' &&
            !api_is_platform_admin()) || api_is_student()
        ) {
            $display_add_course_link = false;
        } else {
            $display_add_course_link = true;
        }

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
        $my_account_content = '<ul class="nav nav-pills nav-stacked">';

        if ($show_create_link) {
            $my_account_content .= '<li class="add-course"><a href="main/create_course/add_course.php">';
            if (api_get_setting('course_validation') == 'true' && !api_is_platform_admin()) {
                $my_account_content .= Display::return_icon('new-course.png',get_lang('CreateCourseRequest'),null,ICON_SIZE_SMALL);
                $my_account_content .= get_lang('CreateCourseRequest');
            } else {
                $my_account_content .= Display::return_icon('new-course.png',get_lang('CourseCreate'),null,ICON_SIZE_SMALL);
                $my_account_content .= get_lang('CourseCreate');
            }
            $my_account_content .= '</a></li>';

            if (SessionManager::allowToManageSessions()) {
                $my_account_content .= '<li class="add-course"><a href="main/session/session_add.php">';
                $my_account_content .= Display::return_icon('session.png',get_lang('AddSession'),null,ICON_SIZE_SMALL);
                $my_account_content .= get_lang('AddSession');
                $my_account_content .= '</a></li>';
            }
        }

        //Sort courses
        $url = api_get_path(WEB_CODE_PATH).'auth/courses.php?action=sortmycourses';
        $img_order= Display::return_icon('order-course.png',get_lang('SortMyCourses'),null,ICON_SIZE_SMALL);
        $my_account_content .= '<li class="order-course">'.Display::url($img_order.get_lang('SortMyCourses'), $url, array('class' => 'sort course')).'</li>';

        // Session history
        if (isset($_GET['history']) && intval($_GET['history']) == 1) {
            $my_account_content .= '<li class="history-course"><a href="user_portal.php">'.Display::return_icon('history-course.png',get_lang('DisplayTrainingList'),null,ICON_SIZE_SMALL).get_lang('DisplayTrainingList').'</a></li>';
        } else {
            $my_account_content .= '<li class="history-course"><a href="user_portal.php?history=1" >'.Display::return_icon('history-course.png',get_lang('HistoryTrainingSessions'),null,ICON_SIZE_SMALL).get_lang('HistoryTrainingSessions').'</a></li>';
        }

        // Course catalog

        if ($show_course_link) {
            if (!api_is_drh()) {
                $my_account_content .= '<li class="list-course"><a href="main/auth/courses.php" >'.Display::return_icon('catalog-course.png',get_lang('CourseCatalog'),null,ICON_SIZE_SMALL).get_lang('CourseCatalog').'</a></li>';
            } else {
                $my_account_content .= '<li><a href="main/dashboard/index.php">'.get_lang('Dashboard').'</a></li>';
            }
        }

        $my_account_content .= '</ul>';

        if (!empty($my_account_content)) {
            $html = self::show_right_block(
                get_lang('Courses'),
                $my_account_content,
                'course_block',
                null,
                'course',
                'courseCollapse'
            );
        }
        return $html;
    }

    /**
     * Prints the session and course list (user_portal.php)
     * @param int $user_id
     * @return string
     */
    public function returnCoursesAndSessions($user_id)
    {
        global $_configuration;

        $gamificationModeIsActive = api_get_setting('gamification_mode');

        $load_history = (isset($_GET['history']) && intval($_GET['history']) == 1) ? true : false;
        if ($load_history) {
            // Load sessions in category in *history*
            $session_categories = UserManager::get_sessions_by_category($user_id, true);
        } else {
            // Load sessions in category
            $session_categories = UserManager::get_sessions_by_category($user_id, false);
        }

        $html = '';
        // Showing history title
        if ($load_history) {
            $html .= Display::page_subheader(get_lang('HistoryTrainingSession'));
            if (empty($session_categories)) {
                $html .= get_lang('YouDoNotHaveAnySessionInItsHistory');
            }
        }

        $courses_html = '';
        $special_courses = '';
        $sessionCount = 0;
        $courseCount = 0;

        // If we're not in the history view...
        if (!isset($_GET['history'])) {
            // Display special courses.
            $specialCourses = CourseManager::display_special_courses(
                $user_id,
                $this->load_directories_preview
            );
            $special_courses = $specialCourses['html'];
            // Display courses.
            $courses = CourseManager::display_courses(
                $user_id,
                $this->load_directories_preview
            );
            $courses_html .= $courses['html'];
            $courseCount = $specialCourses['course_count'] + $courses['course_count'];
        }

        $sessions_with_category = '';
        $sessions_with_no_category = '';

        $sessionTitleLink = api_get_configuration_value('courses_list_session_title_link');
        $sessionTitleLink = $sessionTitleLink === false ? 1 : $sessionTitleLink;

        if (is_array($session_categories)) {
            foreach ($session_categories as $session_category) {
                $session_category_id = $session_category['session_category']['id'];

                // Sessions and courses that are not in a session category
                if (
                    empty($session_category_id) &&
                    isset($session_category['sessions'])
                ) {
                    // Independent sessions
                    foreach ($session_category['sessions'] as $session) {

                        $session_id = $session['session_id'];

                        // Don't show empty sessions.
                        if (count($session['courses']) < 1) {
                            continue;
                        }

                        // Courses inside the current session.
                        $date_session_start = $session['access_start_date'];
                        $date_session_end = $session['access_end_date'];
                        $coachAccessStartDate = $session['coach_access_start_date'];
                        $coachAccessEndDate = $session['coach_access_end_date'];

                        $session_now = time();
                        $count_courses_session = 0;

                        // Loop course content
                        $html_courses_session = [];
                        $atLeastOneCourseIsVisible = false;

                        foreach ($session['courses'] as $course) {
                            $is_coach_course = api_is_coach($session_id, $course['real_id']);
                            $allowed_time = 0;
                            $dif_time_after = 0;

                            if (!empty($date_session_start) &&
                                $date_session_start != '0000-00-00 00:00:00'
                            ) {
                                if ($is_coach_course) {
                                    $allowed_time = api_strtotime($coachAccessStartDate);
                                } else {
                                    $allowed_time = api_strtotime($date_session_start);
                                }

                                if (!isset($_GET['history'])) {
                                    if (!empty($date_session_end) &&
                                        $date_session_end != '0000-00-00 00:00:00'
                                    ) {
                                        $endSessionToTms = api_strtotime($date_session_end);
                                        if ($session_now > $endSessionToTms) {
                                            $dif_time_after = $session_now - $endSessionToTms;
                                            $dif_time_after = round($dif_time_after / 86400);
                                        }
                                    }
                                }
                            }

                            if (
                                $session_now > $allowed_time
                                //($coachAccessEndDate > $dif_time_after - 1)
                            ) {
                                // Read only and accessible.
                                $atLeastOneCourseIsVisible = true;

                                if (api_get_setting('hide_courses_in_sessions') == 'false') {
                                    $c = CourseManager::get_logged_user_course_html(
                                        $course,
                                        $session_id,
                                        'session_course_item',
                                        true,
                                        $this->load_directories_preview
                                    );
                                    if (isset($c[1])) {
                                        $course_session = $c[1];
                                        $course_session['skill'] = $c['skill'];
                                        $html_courses_session[] = $course_session;
                                    }
                                }
                                $count_courses_session++;
                            }
                        }

                        // No courses to show.
                        if ($atLeastOneCourseIsVisible == false) {
                            if (empty($html_courses_session)) {
                                continue;
                            }
                        }

                        if ($count_courses_session > 0) {
                            $params = array(
                                'id' => $session_id
                            );
                            $session_box = Display::get_session_title_box($session_id);

                            $extra_info = !empty($session_box['coach']) ? $session_box['coach'] : null;
                            $extra_info .= !empty($session_box['coach'])
                                ? ' - ' . $session_box['dates']
                                : $session_box['dates'];
                            $extra_info .= isset($session_box['duration'])
                                ? ' ' . $session_box['duration']
                                : null;

                            $params['extra_fields'] = $session_box['extra_fields'];
                            $params['show_link_to_session'] = !api_is_drh() && $sessionTitleLink;
                            $params['title'] = $session_box['title'];
                            $params['subtitle'] = $extra_info;
                            $params['show_actions'] = api_is_platform_admin() ? true : false;

                            if (api_get_setting('hide_courses_in_sessions') == 'false') {
                                // $params['extra'] .=  $html_courses_session;
                            }

                            $params['description'] = $session_box['description'];
                            $params['show_description'] = $session_box['show_description'];
                            $params['courses'] = $html_courses_session;
                            $params['show_simple_session_info'] = false;

                            if (
                                isset($_configuration['show_simple_session_info']) &&
                                $_configuration['show_simple_session_info']
                            ) {
                                $params['show_simple_session_info'] = true;
                            }

                            if ($gamificationModeIsActive) {
                                $params['stars'] = GamificationUtils::getSessionStars($params['id'], $this->user_id);
                                $params['progress'] = GamificationUtils::getSessionProgress($params['id'], $this->user_id);
                                $params['points'] = GamificationUtils::getSessionPoints($params['id'], $this->user_id);
                            }

                            $this->tpl->assign('session', $params);
                            $this->tpl->assign('gamification_mode', $gamificationModeIsActive);

                            $sessions_with_no_category .= $this->tpl->fetch(
                                $this->tpl->get_template('/user_portal/session.tpl')
                            );

                            $sessionCount++;
                        }
                    }
                } else {
                    // All sessions included in
                    $count_courses_session = 0;
                    $html_sessions = '';
                    if (isset($session_category['sessions'])) {
                        foreach ($session_category['sessions'] as $session) {
                            $session_id = $session['session_id'];

                            // Don't show empty sessions.
                            if (count($session['courses']) < 1) {
                                continue;
                            }

                            $date_session_start = $session['access_start_date'];
                            $date_session_end = $session['access_end_date'];
                            $coachAccessStartDate = $session['coach_access_start_date'];
                            $coachAccessEndDate = $session['coach_access_end_date'];

                            $session_now = time();
                            $html_courses_session = [];
                            $count = 0;

                            foreach ($session['courses'] as $course) {
                                $is_coach_course = api_is_coach(
                                    $session_id,
                                    $course['real_id']
                                );

                                $dif_time_after = 0;
                                $allowed_time = 0;
                                if ($is_coach_course) {
                                    // 24 hours = 86400
                                    if ($date_session_start != '0000-00-00 00:00:00') {
                                        $allowed_time = api_strtotime($coachAccessStartDate);
                                    }
                                    if (!isset($_GET['history'])) {
                                        if ($date_session_end != '0000-00-00 00:00:00') {
                                            $endSessionToTms = api_strtotime(
                                                $date_session_end
                                            );
                                            if ($session_now > $endSessionToTms) {
                                                $dif_time_after = $session_now - $endSessionToTms;
                                                $dif_time_after = round(
                                                    $dif_time_after / 86400
                                                );
                                            }
                                        }
                                    }
                                } else {
                                    $allowed_time = api_strtotime(
                                        $date_session_start
                                    );
                                }

                                if (
                                    $session_now > $allowed_time //&&
                                    //$coachAccessEndDate > $dif_time_after - 1
                                ) {
                                    if (api_get_setting('hide_courses_in_sessions') == 'false') {
                                        $c = CourseManager:: get_logged_user_course_html(
                                            $course,
                                            $session_id,
                                            'session_course_item'
                                        );
                                        $html_courses_session[] = $c[1];
                                    }
                                    $count_courses_session++;
                                    $count++;
                                }
                            }

                            $sessionParams = array();
                            //Category
                            if ($count > 0) {
                                $session_box = Display:: get_session_title_box($session_id);
                                $sessionParams['id'] = $session_id;
                                $sessionParams['show_link_to_session'] = !api_is_drh() && $sessionTitleLink;
                                $sessionParams['title'] = $session_box['title'];
                                $sessionParams['subtitle'] = (!empty($session_box['coach'])
                                    ? $session_box['coach'] . ' | '
                                    : '') . $session_box['dates'];
                                $sessionParams['show_actions'] = api_is_platform_admin();
                                $sessionParams['courses'] = $html_courses_session;
                                $sessionParams['show_simple_session_info'] = false;

                                if (
                                    isset($_configuration['show_simple_session_info']) &&
                                    $_configuration['show_simple_session_info']
                                ) {
                                    $sessionParams['show_simple_session_info'] = true;
                                }

                                $this->tpl->assign('session', $sessionParams);
                                $html_sessions .= $this->tpl->fetch(
                                    $this->tpl->get_template('user_portal/session.tpl')
                                );

                                $sessionCount++;
                            }
                        }
                    }

                    if ($count_courses_session > 0) {
                        $categoryParams = array(
                            'id' => $session_category['session_category']['id'],
                            'title' => $session_category['session_category']['name'],
                            'show_actions' => api_is_platform_admin(),
                            'subtitle' => null,
                            'sessions' => $html_sessions
                        );

                        $session_category_start_date = $session_category['session_category']['date_start'];
                        $session_category_end_date = $session_category['session_category']['date_end'];

                        if (
                            !empty($session_category_start_date) &&
                            $session_category_start_date != '0000-00-00' &&
                            !empty($session_category_end_date) &&
                            $session_category_end_date != '0000-00-00'
                        ) {
                            $categoryParams['subtitle'] = sprintf(
                                get_lang('FromDateXToDateY'),
                                $session_category['session_category']['date_start'],
                                $session_category['session_category']['date_end']
                            );
                        } else {
                            if (
                                !empty($session_category_start_date) &&
                                $session_category_start_date != '0000-00-00'
                            ) {
                                $categoryParams['subtitle'] = get_lang('From') . ' ' . $session_category_start_date;
                            }

                            if (
                                !empty($session_category_end_date) &&
                                $session_category_end_date != '0000-00-00'
                            ) {
                                $categoryParams['subtitle'] = get_lang('Until') . ' ' . $session_category_end_date;
                            }
                        }

                        $this->tpl->assign('session_category', $categoryParams);
                        $sessions_with_category .= $this->tpl->fetch(
                            "{$this->tpl->templateFolder}/user_portal/session_category.tpl"
                        );
                    }
                }
            }
        }

        return [
            'html' => $sessions_with_category.$sessions_with_no_category.$courses_html.$special_courses,
            'session_count' => $sessionCount,
            'course_count' => $courseCount
        ];
    }

    /**
     * Shows a welcome message when the user doesn't have any content in the course list
     */
    public function return_welcome_to_course_block()
    {
        $count_courses = CourseManager::count_courses();
        $tpl = $this->tpl->get_template('layout/welcome_to_course.tpl');

        $course_catalog_url = api_get_path(WEB_CODE_PATH).'auth/courses.php';
        $course_list_url = api_get_path(WEB_PATH).'user_portal.php';

        $this->tpl->assign('course_catalog_url', $course_catalog_url);
        $this->tpl->assign('course_list_url', $course_list_url);
        $this->tpl->assign('course_catalog_link', Display::url(get_lang('Here'), $course_catalog_url));
        $this->tpl->assign('course_list_link', Display::url(get_lang('Here'), $course_list_url));
        $this->tpl->assign('count_courses', $count_courses);

        return $this->tpl->fetch($tpl);
    }

    /**
     * @return array
     */
    public function return_hot_courses()
    {
        return CourseManager::return_hot_courses(30, 6);
    }

    /**
     * UserPortal view for session, return the HTLK of the course list
     * @param $user_id
     * @return string
     */
    public function returnCoursesAndSessionsViewBySession($user_id)
    {
        $sessionCount = 0;
        $courseCount = 0;

        $load_history = (isset($_GET['history']) && intval($_GET['history']) == 1) ? true : false;

        if ($load_history) {
            //Load sessions in category in *history*
            $session_categories = UserManager::get_sessions_by_category($user_id, true);
        } else {
            //Load sessions in category
            $session_categories = UserManager::get_sessions_by_category($user_id, false);
        }

        $html = '';

        //Showing history title
        if ($load_history) {
            $html .= Display::page_subheader(get_lang('HistoryTrainingSession'));
            if (empty($session_categories)) {
                $html .=  get_lang('YouDoNotHaveAnySessionInItsHistory');
            }
        }

        $specialCourses = '';
        $loadDirs = $this->load_directories_preview;

        // If we're not in the history view...
        $listCoursesInfo = array();
        if (!isset($_GET['history'])) {
            // Display special courses
            $specialCoursesResult = CourseManager::display_special_courses(
                $user_id,
                $loadDirs
            );
            $specialCourses = $specialCoursesResult['html'];

            // Display courses
            // [code=>xxx, real_id=>000]
            $listCourses = CourseManager::get_courses_list_by_user_id($user_id, false);
            foreach ($listCourses as $i => $listCourseCodeId) {
                list($userCategoryId, $userCatTitle) = CourseManager::getUserCourseCategoryForCourse(
                    $user_id,
                    $listCourseCodeId['real_id']
                );
                $listCourse = api_get_course_info_by_id($listCourseCodeId['real_id']);
                $listCoursesInfo[] = array(
                    'course' => $listCourse,
                    'code' => $listCourseCodeId['code'],
                    'id' => $listCourseCodeId['real_id'],
                    'title' => $listCourse['title'],
                    'userCatId' => $userCategoryId,
                    'userCatTitle' => $userCatTitle
                );
                $courseCount++;
            }
            usort($listCoursesInfo, 'self::compareByCourse');
        }

        if (is_array($session_categories)) {
            // all courses that are in a session
            $listCoursesInSession = SessionManager::getNamedSessionCourseForCoach($user_id);
        }

        // we got all courses
        // for each user category, sorted alphabetically, display courses
        $listUserCategories = CourseManager::get_user_course_categories($user_id);
        $listCoursesAlreadyDisplayed = array();
        uasort($listUserCategories, "self::compareListUserCategory");
        $listUserCategories[0] = '';

        $html = '<div class="session-view-block">';

        foreach ($listUserCategories as $userCategoryId => $userCatTitle) {
            // add user category
            $userCategoryHtml = '';
            if ($userCategoryId != 0) {
                $userCategoryHtml = '<div class="session-view-well ">';
            }
            $userCategoryHtml .= self::getHtmlForUserCategory($userCategoryId, $userCatTitle);

            // look for course in this userCat in session courses : $listCoursesInSession
            $htmlCategory = '';
            if (isset($listCoursesInSession[$userCategoryId])) {
                // list of courses in this user cat
                foreach ($listCoursesInSession[$userCategoryId]['courseInUserCatList'] as $i => $listCourse) {
                    // add course
                    $listCoursesAlreadyDisplayed[$listCourse['courseId']] = 1;
                    if ($userCategoryId == 0) {
                        $htmlCategory .= '<div class="session-view-well session-view-row well" >';
                    } else {
                        $htmlCategory .= '<div class="session-view-row" >';
                    }
                    $coursesInfo =  $listCourse['course'];

                    $htmlCategory .= self::getHtmlForCourse(
                        $coursesInfo,
                        $userCategoryId,
                        1,
                        $loadDirs
                    );
                    // list of session category
                    $htmlSessionCategory = '<div class="session-view-row" style="display:none;" id="courseblock-'.$coursesInfo['real_id'].'">';
                    foreach ($listCourse['sessionCatList'] as $j => $listCategorySession) {
                        // add session category
                        $htmlSessionCategory .= self::getHtmlSessionCategory(
                            $listCategorySession['catSessionId'],
                            $listCategorySession['catSessionName']
                        );
                        // list of session
                        $htmlSession = '';    // start
                        foreach ($listCategorySession['sessionList'] as $k => $listSession) {
                            // add session
                            $htmlSession .= '<div class="session-view-row">';
                            $htmlSession .= self::getHtmlForSession(
                                $listSession['sessionId'],
                                $listSession['sessionName'],
                                $listCategorySession['catSessionId'],
                                $coursesInfo
                            );
                            $htmlSession .= '</div>';
                            $sessionCount++;
                        }
                        $htmlSession .= ''; // end session block
                        $htmlSessionCategory .= $htmlSession;
                    }
                    $htmlSessionCategory .= '</div>'; // end session cat block
                    $htmlCategory .=  $htmlSessionCategory .'</div>' ;
                    $htmlCategory .= '';   // end course block
                }
                $userCategoryHtml .= $htmlCategory;
            }

            // look for courses in this userCat in not in session courses : $listCoursesInfo
            // if course not already added
            $htmlCategory = '';
            foreach ($listCoursesInfo as $i => $listCourse) {
                if ($listCourse['userCatId'] == $userCategoryId && !isset($listCoursesAlreadyDisplayed[$listCourse['id']])) {
                    if ($userCategoryId != 0) {
                        $htmlCategory .= '<div class="session-view-row" >';
                    } else {
                        $htmlCategory .= '<div class="session-view-well well">';
                    }
                    $htmlCategory .= self::getHtmlForCourse(
                        $listCourse['course'],
                        $userCategoryId,
                        0,
                        $loadDirs
                    );
                    $htmlCategory .= '</div>';
                }
            }
            $htmlCategory .= '';
            $userCategoryHtml .= $htmlCategory;   // end user cat block
            if ($userCategoryId != 0) {
                $userCategoryHtml .= '</div>';
            }
            $html .= $userCategoryHtml;   //
        }
        $html .= '</div>';

        return [
            'html' => $html.$specialCourses,
            'session_count' => $sessionCount,
            'course_count' => $courseCount
        ];
    }

    /**
     * Return HTML code for personal user course category
     * @param $id
     * @param $title
     * @return string
     */
    private static function getHtmlForUserCategory($id, $title)
    {
        if ($id == 0) {
            return '';
        }
        $icon = Display::return_icon(
            'folder_yellow.png',
            $title,
            array('class' => 'sessionView'),
            ICON_SIZE_LARGE
        );

        return "<div class='session-view-user-category'>$icon<span>$title</span></div>";
    }

    /**
     * return HTML code for course display in session view
     * @param array $courseInfo
     * @param $userCategoryId
     * @param bool $displayButton
     * @param $loadDirs
     * @return string
     */
    private static function getHtmlForCourse(
        $courseInfo,
        $userCategoryId,
        $displayButton = false,
        $loadDirs
    ) {
        if (empty($courseInfo)) {
            return '';
        }

        $id = $courseInfo['real_id'];
        $title = $courseInfo['title'];
        $code = $courseInfo['code'];

        $class = 'session-view-lvl-6';
        if ($userCategoryId != 0 && !$displayButton) {
            $class = 'session-view-lvl-7';
        }

        $class2 = 'session-view-lvl-6';
        if ($displayButton || $userCategoryId != 0) {
            $class2 = 'session-view-lvl-7';
        }

        $button = '';
        if ($displayButton) {
            $button = '<input id="session-view-button-'.intval($id).'" class="session-view-button btn btn-default" type="button" onclick="hideUnhide(\'courseblock-'.intval($id).'\', \'session-view-button-'.intval($id).'\', \'+\', \'-\')" value="+" />';
        }

        $icon = Display::return_icon(
            'blackboard.png',
            $title,
            array('class' => 'sessionView'),
            ICON_SIZE_LARGE
        );

        $courseLink = $courseInfo['course_public_url'].'?id_session=0';

        // get html course params
        // ['right_actions'] ['teachers'] ['notifications']
        $tabParams = CourseManager::getCourseParamsForDisplay($id, $loadDirs);
        // teacher list
        if (!empty($tabParams['teachers'])) {
            $teachers = '<p class="'.$class2.' view-by-session-teachers">'.$tabParams['teachers'].'</p>';
        }

        // notification
        if (!empty($tabParams['right_actions'])) {
            $rightActions = '<div class="view-by-session-right-actions">'.$tabParams['right_actions'].'</div>';
        }

        return "<div>
                    $button
                    <span class='$class'>$icon
                    <a class='sessionView' href='$courseLink'>$title</a>
                    </span>".$tabParams['notifications']."$rightActions
                </div>
                $teachers";
    }

    /**
     * return HTML code for session category
     * @param $id
     * @param $title
     * @return string
     */
    private static function getHtmlSessionCategory($id, $title)
    {
        if ($id == 0) {
            return '';
        }

        $icon = Display::return_icon(
            'folder_blue.png',
            $title,
            array('class' => 'sessionView'),
            ICON_SIZE_LARGE
        );

        return "<div class='session-view-session-category'>
                <span class='session-view-lvl-2'>
                    $icon
                    <span>$title</span>
                </span>
                </div>";
    }

    /**
     * return HTML code for session
     * @param int $id session id
     * @param string $title session title
     * @param int $categorySessionId
     * @param array $courseInfo
     *
     * @return string
     */
    private static function getHtmlForSession($id, $title, $categorySessionId, $courseInfo)
    {
        $html = '';

        if ($categorySessionId == 0) {
            $class1 = 'session-view-lvl-2';    // session
            $class2 = 'session-view-lvl-4';    // got to course in session link
        } else {
            $class1 = 'session-view-lvl-3';    // session
            $class2 = 'session-view-lvl-5';    // got to course in session link
        }

        $icon = Display::return_icon(
            'blackboard_blue.png',
            $title,
            array('class' => 'sessionView'),
            ICON_SIZE_LARGE
        );
        $courseLink = $courseInfo['course_public_url'].'?id_session='.intval($id);

        $html .= "<span class='$class1 session-view-session'>$icon$title</span>";
        $html .= '<div class="'.$class2.' session-view-session-go-to-course-in-session">
                  <a class="" href="'.$courseLink.'">'.get_lang('GoToCourseInsideSession').'</a></div>';

        return '<div>'.$html.'</div>';
    }

    /**
     * @param $listA
     * @param $listB
     * @return int
     */
    private static function compareByCourse($listA, $listB)
    {
        if ($listA['userCatTitle'] == $listB['userCatTitle']) {
            if ($listA['title'] == $listB['title']) {
                return 0;
            } else if($listA['title'] > $listB['title']) {
                return 1;
            } else {
                return -1;
            }
        } else if ($listA['userCatTitle'] > $listB['userCatTitle']) {
            return 1;
        } else {
            return -1;
        }
    }

    /**
     * @param $listA
     * @param $listB
     * @return int
     */
    public static function compareListUserCategory($listA, $listB)
    {
        if ($listA['title'] == $listB['title']) {
            return 0;
        } else if($listA['title'] > $listB['title']) {
            return 1;
        } else {
            return -1;
        }
    }

    /**
     * @param $view
     * @param $userId
     */
    public static function setDefaultMyCourseView($view, $userId)
    {
        setcookie('defaultMyCourseView'.$userId, $view);
    }
}
