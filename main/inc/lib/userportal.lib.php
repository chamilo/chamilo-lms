<?php

/* For licensing terms, see /license.txt */

/**
 * Class IndexManager.
 */
class IndexManager
{
    public const VIEW_BY_DEFAULT = 0;
    public const VIEW_BY_SESSION = 1;

    // An instance of the template engine
    // No need to initialize because IndexManager is not static,
    // and the constructor immediately instantiates a Template
    public $tpl;
    public $name = '';
    public $home = '';
    public $default_home = 'home/';

    /**
     * Construct.
     *
     * @param string $title
     */
    public function __construct($title)
    {
        $this->tpl = new Template($title);
        $this->home = api_get_home_path();
        $this->user_id = api_get_user_id();
        $this->load_directories_preview = false;

        // Load footer plugins systematically
        /*$config = api_get_settings_params(array('subkey = ? ' => 'customfooter', ' AND category = ? ' => 'Plugins'));
        if (!empty($config)) {
            foreach ($config as $fooid => $configrecord) {
                $canonic = preg_replace('/^customfooter_/', '', $configrecord['variable']);
                $footerconfig->$canonic = $configrecord['selected_value'];
            }
            if (!empty($footerconfig->footer_left)) {
                $this->tpl->assign('plugin_footer_left', $footerconfig->footer_left);
            }
            if (!empty($footerconfig->footer_right)) {
                $this->tpl->assign('plugin_footer_right', $footerconfig->footer_right);
            }
        }*/

        if (api_get_setting('show_documents_preview') === 'true') {
            $this->load_directories_preview = true;
        }
    }

    /**
     * @param $listA
     * @param $listB
     *
     * @return int
     */
    public static function compareListUserCategory($listA, $listB)
    {
        if ($listA['title'] == $listB['title']) {
            return 0;
        }

        if ($listA['title'] > $listB['title']) {
            return 1;
        }

        return -1;
    }

    /**
     * @param $view
     * @param $userId
     */
    public static function setDefaultMyCourseView($view, $userId)
    {
        api_set_cookie('defaultMyCourseView'.$userId, $view);
    }

    /**
     * @param bool $setLoginForm
     */
    public function set_login_form($setLoginForm = true)
    {
        global $loginFailed;
        $this->tpl->setLoginForm($setLoginForm);
    }

    /**
     * @param array $personal_course_list
     */
    public function return_exercise_block($personal_course_list)
    {
        $exercise_list = [];
        if (!empty($personal_course_list)) {
            foreach ($personal_course_list as $course_item) {
                $course_code = $course_item['c'];
                $session_id = $course_item['id_session'];

                $exercises = ExerciseLib::get_exercises_to_be_taken(
                    $course_code,
                    $session_id
                );

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
                $url = Display::url(
                    $my_exercise['title'],
                    api_get_path(
                        WEB_CODE_PATH
                    ).'exercise/overview.php?exerciseId='.$my_exercise['id'].'&cidReq='.$my_exercise['course_code'].'&id_session='.$my_exercise['session_id']
                );
                $this->tpl->assign('exercise_url', $url);
                $this->tpl->assign(
                    'exercise_end_date',
                    api_convert_and_format_date($my_exercise['end_time'], DATE_FORMAT_SHORT)
                );
            }
        }
    }

    /**
     * @param bool $show_slide
     *
     * @return string|null
     */
    public function return_announcements($show_slide = true)
    {
        $hideAnnouncements = api_get_setting('hide_global_announcements_when_not_connected');
        $currentUserId = api_get_user_id();
        if ($hideAnnouncements === 'true' && empty($currentUserId)) {
            return null;
        }
        $announcement = isset($_GET['announcement']) ? $_GET['announcement'] : null;
        $announcement = (int) $announcement;

        if (!api_is_anonymous() && $this->user_id) {
            $visibility = SystemAnnouncementManager::getCurrentUserVisibility();
            if ($show_slide) {
                $announcements = SystemAnnouncementManager::displayAnnouncementsSlider(
                    $visibility,
                    $announcement
                );
            } else {
                $announcements = SystemAnnouncementManager::displayAllAnnouncements(
                    $visibility,
                    $announcement
                );
            }
        } else {
            if ($show_slide) {
                $announcements = SystemAnnouncementManager::displayAnnouncementsSlider(
                    SystemAnnouncementManager::VISIBLE_GUEST,
                    $announcement
                );
            } else {
                $announcements = SystemAnnouncementManager::displayAllAnnouncements(
                    SystemAnnouncementManager::VISIBLE_GUEST,
                    $announcement
                );
            }
        }

        return $announcements;
    }

    /**
     * Alias for the online_logout() function.
     *
     * @param bool  $redirect   Whether to ask online_logout to redirect to index.php or not
     * @param array $logoutInfo Information stored by local.inc.php before new context ['uid'=> x, 'cid'=>y, 'sid'=>z]
     */
    public function logout($redirect = true, $logoutInfo = [])
    {
        online_logout($this->user_id, true);
        Event::courseLogout($logoutInfo);
    }

    /**
     * Includes a created page.
     *
     * @param bool $getIncludedFile Whether to include a file as provided in URL GET or simply the homepage
     *
     * @return string
     */
    public function return_home_page($getIncludedFile = false)
    {
        $userId = api_get_user_id();
        // Including the page for the news
        $html = '';
        if ($getIncludedFile === true) {
            if (!empty($_GET['include']) && preg_match('/^[a-zA-Z0-9_-]*\.html$/', $_GET['include'])) {
                $open = @(string) file_get_contents($this->home.$_GET['include']);
                $html = api_to_system_encoding($open, api_detect_encoding(strip_tags($open)));
            }
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

            $home_top_temp = '';
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
                        $home_top_temp = file_get_contents($this->default_home.'home_top.html');
                    }
                }
            }

            if (trim($home_top_temp) == '' && api_is_platform_admin()) {
                $home_top_temp = get_lang('PortalHomepageDefaultIntroduction');
            }
            $open = str_replace('{rel_path}', api_get_path(REL_PATH), $home_top_temp);
            $html = api_to_system_encoding($open, api_detect_encoding(strip_tags($open)));
        }

        return $html;
    }

    /**
     * @return string
     */
    public function return_notice()
    {
        $user_selected_language = api_get_interface_language();
        // Notice
        $home_notice = @(string) file_get_contents($this->home.'home_notice_'.$user_selected_language.'.html');
        if (empty($home_notice)) {
            $home_notice = @(string) file_get_contents($this->home.'home_notice.html');
        }
        if (!empty($home_notice)) {
            $home_notice = api_to_system_encoding($home_notice, api_detect_encoding(strip_tags($home_notice)));
        }

        return $home_notice;
    }

    /**
     * @return string
     */
    public function return_help()
    {
        $user_selected_language = api_get_interface_language();
        $platformLanguage = api_get_setting('platformLanguage');

        // Help section.
        /* Hide right menu "general" and other parts on anonymous right menu. */
        if (!isset($user_selected_language)) {
            $user_selected_language = $platformLanguage;
        }

        $html = '';
        $home_menu = @(string) file_get_contents($this->home.'home_menu_'.$user_selected_language.'.html');
        if (!empty($home_menu)) {
            $html = api_to_system_encoding($home_menu, api_detect_encoding(strip_tags($home_menu)));
        }

        return $html;
    }

    /**
     * Generate the block for show a panel with links to My Certificates and Certificates Search pages.
     *
     * @return array The HTML code for the panel
     */
    public function returnSkillLinks()
    {
        $items = [];

        if (!api_is_anonymous() &&
            api_get_configuration_value('hide_my_certificate_link') === false
        ) {
            $items[] = [
                'icon' => Display::return_icon('graduation.png', get_lang('MyCertificates')),
                'link' => api_get_path(WEB_CODE_PATH).'gradebook/my_certificates.php',
                'title' => get_lang('MyCertificates'),
            ];
        }
        if (api_get_setting('allow_public_certificates') === 'true') {
            $items[] = [
                'icon' => Display::return_icon('search_graduation.png', get_lang('Search')),
                'link' => api_get_path(WEB_CODE_PATH).'gradebook/search.php',
                'title' => get_lang('Search'),
            ];
        }

        $myCertificate = GradebookUtils::get_certificate_by_user_id(
            +0,
            $this->user_id
        );

        if ($myCertificate) {
            $items[] = [
                'icon' => Display::return_icon(
                    'skill-badges.png',
                    get_lang('MyGeneralCertificate'),
                    null,
                    ICON_SIZE_SMALL
                ),
                'link' => api_get_path(WEB_CODE_PATH).'social/my_skills_report.php?a=generate_custom_skill',
                'title' => get_lang('MyGeneralCertificate'),
            ];
        }

        if (Skill::isAllowed(api_get_user_id(), false)) {
            $items[] = [
                'icon' => Display::return_icon('skill-badges.png', get_lang('MySkills')),
                'link' => api_get_path(WEB_CODE_PATH).'social/my_skills_report.php',
                'title' => get_lang('MySkills'),
            ];
            $allowSkillsManagement = api_get_setting('allow_hr_skills_management') == 'true';
            if (($allowSkillsManagement && api_is_drh()) || api_is_platform_admin()) {
                $items[] = [
                    'icon' => Display::return_icon('edit-skill.png', get_lang('MySkills')),
                    'link' => api_get_path(WEB_CODE_PATH).'admin/skills_wheel.php',
                    'title' => get_lang('ManageSkills'),
                ];
            }
        }

        return $items;
    }

    public static function studentPublicationBlock()
    {
        if (api_is_anonymous()) {
            return [];
        }

        $allow = api_get_configuration_value('allow_my_student_publication_page');
        $items = [];

        if ($allow) {
            $items[] = [
                'icon' => Display::return_icon('lp_student_publication.png', get_lang('StudentPublications')),
                'link' => api_get_path(WEB_CODE_PATH).'work/publications.php',
                'title' => get_lang('MyStudentPublications'),
            ];
        }

        return $items;
    }

    /**
     * Reacts on a failed login:
     * Displays an explanation with a link to the registration form.
     *
     * @version 1.0.1
     */
    public function handle_login_failed()
    {
        return $this->tpl->handleLoginFailed();
    }

    /**
     * Display list of courses in a category.
     * (for anonymous users).
     *
     * @version 1.1
     *
     * @author  Patrick Cool <patrick.cool@UGent.be>, Ghent University - refactoring and code cleaning
     * @author  Julio Montoya <gugli100@gmail.com>, Beeznest template modifs
     */
    public function return_courses_in_categories()
    {
        $result = '';
        $stok = Security::get_token();

        // Initialization.
        $user_identified = (api_get_user_id() > 0 && !api_is_anonymous());
        $web_course_path = api_get_path(WEB_COURSE_PATH);
        $category = isset($_GET['category']) ? Database::escape_string($_GET['category']) : '';
        $setting_show_also_closed_courses = api_get_setting('show_closed_courses') == 'true';

        // Database table definitions.
        $main_course_table = Database::get_main_table(TABLE_MAIN_COURSE);
        $main_category_table = Database::get_main_table(TABLE_MAIN_CATEGORY);

        // Get list of courses in category $category.
        $sql = "SELECT * FROM $main_course_table cours
                WHERE category_code = '".$category."'
                ORDER BY title, UPPER(visual_code)";

        // Showing only the courses of the current access_url_id.
        if (api_is_multiple_url_enabled()) {
            $url_access_id = api_get_current_access_url_id();
            if ($url_access_id != -1) {
                $tbl_url_rel_course = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_COURSE);
                $sql = "SELECT * FROM $main_course_table as course
                        INNER JOIN $tbl_url_rel_course as url_rel_course
                        ON (url_rel_course.c_id = course.id)
                        WHERE
                            access_url_id = $url_access_id AND
                            category_code = '".$category."'
                        ORDER BY title, UPPER(visual_code)";
            }
        }

        // Removed: AND cours.visibility='".COURSE_VISIBILITY_OPEN_WORLD."'
        $queryResult = Database::query($sql);
        while ($course_result = Database::fetch_array($queryResult)) {
            $course_list[] = $course_result;
        }
        $numRows = Database::num_rows($queryResult);

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
                    LEFT JOIN $main_category_table t2
                    ON t1.code=t2.parent_id
                    LEFT JOIN $main_course_table t3
                    ON (t3.category_code = t1.code $platform_visible_courses)
                    WHERE t1.parent_id ".(empty($category) ? "IS NULL" : "='$category'")."
                    GROUP BY t1.name,t1.code,t1.parent_id,t1.children_count
                    ORDER BY t1.tree_pos, t1.name";

        // Showing only the category of courses of the current access_url_id
        if (api_is_multiple_url_enabled()) {
            $table = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_COURSE_CATEGORY);
            $courseCategoryCondition = " INNER JOIN $table a ON (t1.id = a.course_category_id)";

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

        $htmlTitre = '';
        $htmlListCat = '';
        if (Database::num_rows($resCats) > 0) {
            $htmlListCat = Display::page_header(get_lang('CatList'));
            $htmlListCat .= '<ul>';
            while ($catLine = Database::fetch_array($resCats)) {
                $category_has_open_courses = self::category_has_open_courses($catLine['code']);
                if ($category_has_open_courses) {
                    // The category contains courses accessible to anonymous visitors.
                    $htmlListCat .= '<li>';
                    $htmlListCat .= '<a href="'.api_get_self(
                        ).'?category='.$catLine['code'].'">'.$catLine['name'].'</a>';
                    if (api_get_setting('show_number_of_courses') == 'true') {
                        $htmlListCat .= ' ('.$catLine['nbCourse'].' '.get_lang('Courses').')';
                    }
                    $htmlListCat .= "</li>";
                    $thereIsSubCat = true;
                } elseif ($catLine['children_count'] > 0) {
                    // The category has children, subcategories.
                    $htmlListCat .= '<li>';
                    $htmlListCat .= '<a href="'.api_get_self(
                        ).'?category='.$catLine['code'].'">'.$catLine['name'].'</a>';
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
            $result .= $htmlListCat;
        }
        while ($categoryName = Database::fetch_array($resCats)) {
            $result .= '<h3>'.$categoryName['name']."</h3>\n";
        }

        $courses_list_string = '';
        $courses_shown = 0;
        if ($numRows > 0) {
            $courses_list_string .= Display::page_header(get_lang('CourseList'));
            $courses_list_string .= "<ul>";
            if (api_get_user_id()) {
                $courses_of_user = [];
                $coursesByUserCategory = CourseManager::getCoursesByUserCourseCategory(api_get_user_id());
                if (!empty($coursesByUserCategory)) {
                    foreach ($coursesByUserCategory as $courseItem) {
                        $courses_of_user[$courseItem['code']] = $courseItem;
                    }
                }
            }
            foreach ($course_list as $course) {
                // $setting_show_also_closed_courses
                if ($course['visibility'] == COURSE_VISIBILITY_HIDDEN) {
                    continue;
                }
                if (!$setting_show_also_closed_courses) {
                    // If we do not show the closed courses
                    // we only show the courses that are open to the world (to everybody)
                    // and the courses that are open to the platform (if the current user is a registered user.
                    if (($user_identified && $course['visibility'] == COURSE_VISIBILITY_OPEN_PLATFORM) ||
                        ($course['visibility'] == COURSE_VISIBILITY_OPEN_WORLD)
                    ) {
                        $courses_shown++;
                        $courses_list_string .= "<li>";
                        $courses_list_string .= '<a href="'.$web_course_path.$course['directory'].'/">'.$course['title'].'</a><br />';
                        $course_details = [];
                        if (api_get_setting('display_coursecode_in_courselist') === 'true') {
                            $course_details[] = '('.$course['visual_code'].')';
                        }
                        if (api_get_setting('display_teacher_in_courselist') === 'true') {
                            $course_details[] = CourseManager::getTeacherListFromCourseCodeToString($course['code']);
                        }
                        if (api_get_setting('show_different_course_language') === 'true' &&
                            $course['course_language'] != api_get_setting('platformLanguage')
                        ) {
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
                        || api_is_platform_admin()
                    ) {
                        $courses_list_string .= '<a href="'.$web_course_path.$course['directory'].'/">';
                    }
                    $courses_list_string .= $course['title'];
                    if ($course['visibility'] == COURSE_VISIBILITY_OPEN_WORLD
                        || ($user_identified && $course['visibility'] == COURSE_VISIBILITY_OPEN_PLATFORM)
                        || ($user_identified && array_key_exists($course['code'], $courses_of_user)
                            && $course['visibility'] != COURSE_VISIBILITY_CLOSED)
                        || $courses_of_user[$course['code']]['status'] == '1'
                        || api_is_platform_admin()
                    ) {
                        $courses_list_string .= '</a><br />';
                    }
                    $course_details = [];
                    if (api_get_setting('display_coursecode_in_courselist') === 'true') {
                        $course_details[] = '('.$course['visual_code'].')';
                    }
                    if (api_get_setting('display_teacher_in_courselist') === 'true') {
                        if (!empty($course['tutor_name'])) {
                            $course_details[] = $course['tutor_name'];
                        }
                    }
                    if (api_get_setting('show_different_course_language') == 'true' &&
                        $course['course_language'] != api_get_setting('platformLanguage')
                    ) {
                        $course_details[] = $course['course_language'];
                    }

                    $courses_list_string .= implode(' - ', $course_details);
                    // We display a subscription link if:
                    // 1. it is allowed to register for the course and if the course is not already in
                    // the courselist of the user and if the user is identified
                    // 2.
                    if ($user_identified && !array_key_exists($course['code'], $courses_of_user)) {
                        if ($course['subscribe'] == '1') {
                            $courses_list_string .= '&nbsp;<a class="btn btn-primary" href="main/auth/courses.php?action=subscribe_course&sec_token='.$stok.'&course_code='.$course['code'].'&category_code='.Security::remove_XSS(
                                    $_GET['category']
                                ).'">'.get_lang('Subscribe').'</a><br />';
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
            $result .= '<p><a href="'.api_get_self().'">'
                .Display::return_icon('back.png', get_lang('BackToHomePage'))
                .get_lang('BackToHomePage').'</a></p>';
        }

        return $result;
    }

    /**
     * This function checks if there are courses that are open to the world in the platform course categories
     * (=faculties).
     *
     * @param string $category
     *
     * @return bool
     */
    public function category_has_open_courses($category)
    {
        $setting_show_also_closed_courses = api_get_setting('show_closed_courses') == 'true';
        $main_course_table = Database::get_main_table(TABLE_MAIN_COURSE);
        $category = Database::escape_string($category);
        $sql_query = "SELECT * FROM $main_course_table WHERE category_code='$category'";
        $sql_result = Database::query($sql_query);
        while ($course = Database::fetch_array($sql_result)) {
            if (!$setting_show_also_closed_courses) {
                if ((api_get_user_id() > 0 && $course['visibility'] == COURSE_VISIBILITY_OPEN_PLATFORM) ||
                    ($course['visibility'] == COURSE_VISIBILITY_OPEN_WORLD)
                ) {
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

    /**
     * Adds a form to let users login.
     *
     * @version 1.1
     */
    public function display_login_form()
    {
        return $this->tpl->displayLoginForm();
    }

    /**
     * @return string
     *
     * @todo use FormValidator
     */
    public function return_search_block()
    {
        $html = '';
        if (api_get_setting('search_enabled') == 'true') {
            $search_btn = get_lang('Search');
            $search_content = '<form action="main/search/" method="post">
                <div class="form-group">
                <input type="text" id="query" class="form-control" name="query" value="" />
                <button class="btn btn-default" type="submit" name="submit" value="'.$search_btn.'" />'.
                $search_btn.' </button>
                </div></form>';
            $html .= $this->showRightBlock(get_lang('Search'), $search_content, 'search_block');
        }

        return $html;
    }

    /**
     * @param        $title
     * @param        $content
     * @param string $id
     * @param array  $params
     * @param string $idAccordion
     * @param string $idCollapse
     *
     * @return string
     *
     * @todo use the template system
     */
    public function showRightBlock(
        $title,
        $content,
        $id = '',
        $params = [],
        $idAccordion = '',
        $idCollapse = ''
    ) {
        $html = '';
        if (!empty($idAccordion)) {
            $html .= '<div class="panel-group" id="'.$idAccordion.'" role="tablist" aria-multiselectable="true">';
            $html .= '<div class="panel panel-default" id="'.$id.'">';
            $html .= '<div class="panel-heading" role="tab"><h4 class="panel-title">';
            $html .= '<a role="button" data-toggle="collapse" data-parent="#'.$idAccordion.'" href="#'.$idCollapse.'" aria-expanded="true" aria-controls="'.$idCollapse.'">'.$title.'</a>';
            $html .= '</h4></div>';
            $html .= '<div id="'.$idCollapse.'" class="panel-collapse collapse in" role="tabpanel">';
            $html .= '<div class="panel-body">'.$content.'</div>';
            $html .= '</div></div></div>';
        } else {
            if (!empty($id)) {
                $params['id'] = $id;
            }
            $params['class'] = 'panel panel-default';
            $html = null;
            if (!empty($title)) {
                $html .= '<div class="panel-heading">'.$title.'</div>';
            }
            $html .= '<div class="panel-body">'.$content.'</div>';
            $html = Display::div($html, $params);
        }

        return $html;
    }

    /**
     * @return string
     */
    public function returnClassesBlock()
    {
        if (api_get_setting('show_groups_to_users') !== 'true') {
            return '';
        }

        $items = [];

        $usergroup = new UserGroup();
        if (api_is_platform_admin()) {
            $items[] = [
                'link' => api_get_path(WEB_CODE_PATH).'admin/usergroups.php?action=add',
                'title' => get_lang('AddClasses'),
            ];
        } else {
            if (api_is_teacher() && $usergroup->allowTeachers()) {
                $items[] = [
                    'link' => api_get_path(WEB_CODE_PATH).'admin/usergroups.php',
                    'title' => get_lang('ClassList'),
                ];
            }
        }

        $usergroup_list = $usergroup->get_usergroup_by_user(api_get_user_id());
        if (!empty($usergroup_list)) {
            foreach ($usergroup_list as $group_id) {
                $data = $usergroup->get($group_id);
                $items[] = [
                    'link' => api_get_path(WEB_CODE_PATH).'user/classes.php?id='.$data['id'],
                    'title' => $data['name'],
                ];
            }
        }

        $html = $this->showRightBlock(
            get_lang('Classes'),
            self::returnRightBlockItems($items),
            'classes_block'
        );

        return $html;
    }

    /**
     * @return string
     */
    public function return_user_image_block()
    {
        $html = '';
        if (!api_is_anonymous()) {
            $userPicture = UserManager::getUserPicture(api_get_user_id(), USER_IMAGE_SIZE_ORIGINAL);
            $content = null;

            if (api_get_setting('allow_social_tool') == 'true') {
                $content .= '<a style="text-align:center" href="'.api_get_path(WEB_CODE_PATH).'social/home.php">
                <img class="img-circle" src="'.$userPicture.'"></a>';
            } else {
                $content .= '<a style="text-align:center" href="'.api_get_path(WEB_CODE_PATH).'auth/profile.php">
                <img class="img-circle" title="'.get_lang('EditProfile').'" src="'.$userPicture.'"></a>';
            }

            $html = $this->showRightBlock(
                null,
                $content,
                'user_image_block',
                ['style' => 'text-align:center;']
            );
        }

        return $html;
    }

    /**
     * @return array
     */
    public function return_profile_block()
    {
        $userInfo = api_get_user_info();
        $userId = api_get_user_id();
        if (empty($userId)) {
            return;
        }

        $items = [];
        $userGroup = new UserGroup();
        //  @todo Add a platform setting to add the user image.
        if (api_get_setting('allow_message_tool') === 'true') {
            // New messages.
            $number_of_new_messages = MessageManager::getCountNewMessages();
            // New contact invitations.
            $number_of_new_messages_of_friend = SocialManager::get_message_number_invitation_by_user_id(
                $userId
            );

            // New group invitations sent by a moderator.
            $group_pending_invitations = $userGroup->get_groups_by_user(
                $userId,
                GROUP_USER_PERMISSION_PENDING_INVITATION,
                false
            );
            $group_pending_invitations = count($group_pending_invitations);
            $total_invitations = $number_of_new_messages_of_friend + $group_pending_invitations;
            $cant_msg = Display::badge($number_of_new_messages);

            $items[] = [
                'class' => 'inbox-message-social',
                'icon' => Display::return_icon('inbox.png', get_lang('Inbox')),
                'link' => api_get_path(WEB_CODE_PATH).'messages/inbox.php',
                'title' => get_lang('Inbox').$cant_msg,
            ];

            $items[] = [
                'class' => 'new-message-social',
                'icon' => Display::return_icon('new-message.png', get_lang('Compose')),
                'link' => api_get_path(WEB_CODE_PATH).'messages/new_message.php',
                'title' => get_lang('Compose'),
            ];

            if (api_get_setting('allow_social_tool') === 'true') {
                $total_invitations = Display::badge($total_invitations);
                $items[] = [
                    'class' => 'invitations-social',
                    'icon' => Display::return_icon('invitations.png', get_lang('PendingInvitations')),
                    'link' => api_get_path(WEB_CODE_PATH).'social/invitations.php',
                    'title' => get_lang('PendingInvitations').$total_invitations,
                ];
            }
        }

        $items[] = [
            'class' => 'personal-data',
            'icon' => Display::return_icon('database.png', get_lang('PersonalDataReport')),
            'link' => api_get_path(WEB_CODE_PATH).'social/personal_data.php',
            'title' => get_lang('PersonalDataReport'),
        ];

        if (api_get_configuration_value('allow_my_files_link_in_homepage')) {
            if (api_get_setting('allow_my_files') !== 'false') {
                $items[] = [
                    'class' => 'myfiles-social',
                    'icon' => Display::return_icon('sn-files.png', get_lang('Files')),
                    'link' => api_get_path(WEB_CODE_PATH).'social/myfiles.php',
                    'title' => get_lang('MyFiles'),
                ];
            }
        }

        $items[] = [
            'class' => 'profile-social',
            'icon' => Display::return_icon('edit-profile.png', get_lang('EditProfile')),
            'link' => Display::getProfileEditionLink($userId),
            'title' => get_lang('EditProfile'),
        ];

        if (api_get_configuration_value('show_link_request_hrm_user') &&
            api_is_drh()
        ) {
            $label = get_lang('RequestLinkingToUser');
            $items[] = [
                'icon' => Display::return_icon('new_group.png', $label),
                'link' => api_get_path(WEB_CODE_PATH).'social/require_user_linking.php',
                'title' => $label,
            ];
        }

        if (api_get_configuration_value('show_my_lps_page')) {
            $items[] = [
                'icon' => Display::return_icon('learnpath.png', get_lang('MyLps')),
                'link' => api_get_path(WEB_CODE_PATH).'lp/my_list.php',
                'title' => get_lang('MyLps'),
            ];
        }

        if (bbb::showGlobalConferenceLink($userInfo)) {
            $bbb = new bbb('', '', true, api_get_user_id());
            $url = $bbb->getListingUrl();
            $items[] = [
                'class' => 'video-conference',
                'icon' => Display::return_icon(
                    'bbb.png',
                    get_lang('VideoConference')
                ),
                'link' => $url,
                'title' => get_lang('VideoConference'),
            ];
        }

        if ('true' === api_get_plugin_setting('zoom', 'tool_enable')) {
            $zoomPlugin = new ZoomPlugin();
            $blocks = $zoomPlugin->getProfileBlockItems();
            foreach ($blocks as $item) {
                $items[] = $item;
            }
        }

        if (
            true === api_get_configuration_value('whispeak_auth_enabled') &&
            !WhispeakAuthPlugin::checkUserIsEnrolled($userId)
        ) {
            $itemTitle = get_plugin_lang('EnrollmentTitle', WhispeakAuthPlugin::class);

            $items[] = [
                'class' => 'whispeak-enrollment',
                'icon' => Display::return_icon('addworkuser.png', $itemTitle),
                'link' => WhispeakAuthPlugin::getEnrollmentUrl(),
                'title' => $itemTitle,
            ];
        }

        return $items;
    }

    /**
     * @return array
     */
    public function return_navigation_links()
    {
        $items = [];
        // Deleting the myprofile link.
        if (api_get_setting('allow_social_tool') == 'true') {
            unset($this->tpl->menu_navigation['myprofile']);
        }

        $hideMenu = api_get_configuration_value('hide_main_navigation_menu');
        if ($hideMenu === true) {
            return '';
        }

        // Main navigation section.
        // Tabs that are deactivated are added here.
        if (!empty($this->tpl->menu_navigation)) {
            foreach ($this->tpl->menu_navigation as $section => $navigation_info) {
                if (!empty($navigation_info)) {
                    $items[] = [
                        'icon' => null,
                        'link' => $navigation_info['url'],
                        'title' => $navigation_info['title'],
                    ];
                }
            }
        }

        return $items;
    }

    /**
     * @return array
     */
    public function return_course_block()
    {
        if (api_get_configuration_value('hide_course_sidebar')) {
            return '';
        }
        $isHrm = api_is_drh();
        $show_create_link = false;
        $show_course_link = false;
        if (api_is_allowed_to_create_course()) {
            $show_create_link = true;
        }

        if (api_get_setting('allow_students_to_browse_courses') === 'true') {
            $show_course_link = true;
        }

        $items = [];

        // My account section
        if ($show_create_link) {
            if (api_get_setting('course_validation') === 'true' && !api_is_platform_admin()) {
                $items[] = [
                    'class' => 'add-course',
                    'icon' => Display::return_icon('new-course.png', get_lang('CreateCourseRequest')),
                    'link' => api_get_path(WEB_CODE_PATH).'create_course/add_course.php',
                    'title' => get_lang('CreateCourseRequest'),
                ];
            } else {
                $items[] = [
                    'class' => 'add-course',
                    'icon' => Display::return_icon('new-course.png', get_lang('CourseCreate')),
                    'link' => api_get_path(WEB_CODE_PATH).'create_course/add_course.php',
                    'title' => get_lang('CourseCreate'),
                ];
            }

            if (SessionManager::allowToManageSessions()) {
                $items[] = [
                    'class' => 'add-session',
                    'icon' => Display::return_icon('session.png', get_lang('AddSession')),
                    'link' => api_get_path(WEB_CODE_PATH).'session/session_add.php',
                    'title' => get_lang('AddSession'),
                ];
            }
        }

        // Sort courses
        $items[] = [
            'class' => 'order-course',
            'icon' => Display::return_icon('order-course.png', get_lang('SortMyCourses')),
            'link' => api_get_path(WEB_CODE_PATH).'auth/sort_my_courses.php',
            'title' => get_lang('SortMyCourses'),
        ];

        // Session history
        if (isset($_GET['history']) && intval($_GET['history']) == 1) {
            $items[] = [
                'class' => 'history-course',
                'icon' => Display::return_icon('history-course.png', get_lang('DisplayTrainingList')),
                'link' => api_get_path(WEB_PATH).'user_portal.php',
                'title' => get_lang('DisplayTrainingList'),
            ];
        } else {
            $items[] = [
                'class' => 'history-course',
                'icon' => Display::return_icon('history-course.png', get_lang('HistoryTrainingSessions')),
                'link' => api_get_path(WEB_PATH).'user_portal.php?history=1',
                'title' => get_lang('HistoryTrainingSessions'),
            ];
        }

        if ($isHrm) {
            $items[] = [
                'class' => 'list-followed-user-courses',
                'link' => api_get_path(WEB_CODE_PATH).'auth/hrm_courses.php',
                'title' => get_lang('HrmAssignedUsersCourseList'),
            ];
        }

        // Course catalog
        if ($show_course_link) {
            if (!api_is_drh()) {
                $items[] = [
                    'class' => 'list-course',
                    'icon' => Display::return_icon('catalog-course.png', get_lang('CourseCatalog')),
                    'link' => api_get_path(WEB_CODE_PATH).'auth/courses.php',
                    'title' => get_lang('CourseCatalog'),
                ];
            } else {
                $items[] = [
                    'class' => 'dashboard-page',
                    'link' => api_get_path(WEB_CODE_PATH).'dashboard/index.php',
                    'title' => get_lang('Dashboard'),
                ];
            }
        }

        if (!api_is_anonymous()) {
            $items[] = [
                'class' => 'last-visited-course',
                'icon' => Display::return_icon('clock.png', get_lang('LastVisitedCourse')),
                'link' => api_get_path(WEB_CODE_PATH).'course_home/last_course.php',
                'title' => get_lang('LastVisitedCourse'),
            ];
            $items[] = [
                'class' => 'last-visited-lp',
                'icon' => Display::return_icon('learnpath.png', get_lang('LastVisitedLp')),
                'link' => api_get_path(WEB_CODE_PATH).'course_home/last_lp.php',
                'title' => get_lang('LastVisitedLp'),
            ];
        }

        if (api_is_teacher()) {
            if (api_get_configuration_value('my_courses_show_pending_work')) {
                $items[] = [
                    'class' => 'list-pending-student-assignments',
                    'icon' => Display::return_icon('work.png', get_lang('StudentPublicationToCorrect')),
                    'link' => api_get_path(WEB_CODE_PATH).'work/pending.php',
                    'title' => get_lang('StudentPublicationToCorrect'),
                ];
            }

            if (api_get_configuration_value('my_courses_show_pending_exercise_attempts')) {
                $items[] = [
                    'class' => 'list-pending-exercise-attempts',
                    'icon' => Display::return_icon('quiz.png', get_lang('PendingAttempts')),
                    'link' => api_get_path(WEB_CODE_PATH).'exercise/pending.php',
                    'title' => get_lang('PendingAttempts'),
                ];
            }
        }

        return $items;
    }

    /**
     * Shows a welcome message when the user doesn't have any content in the course list.
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
     * UserPortal view for session, return the HTML of the course list.
     *
     * @param $user_id
     *
     * @return string
     */
    public function returnCoursesAndSessionsViewBySession($user_id)
    {
        $sessionCount = 0;
        $courseCount = 0;
        $load_history = (isset($_GET['history']) && intval($_GET['history']) == 1) ? true : false;

        if ($load_history) {
            // Load sessions in category in *history*
            $session_categories = UserManager::get_sessions_by_category($user_id, true);
        } else {
            // Load sessions in category
            $session_categories = UserManager::get_sessions_by_category($user_id, false);
        }

        $html = '';
        $loadDirs = $this->load_directories_preview;

        // If we're not in the history view...
        $listCoursesInfo = [];
        if (!isset($_GET['history'])) {
            // Display special courses
            $specialCoursesResult = CourseManager::returnSpecialCourses(
                $user_id,
                $loadDirs
            );
            $specialCourses = $specialCoursesResult;

            if ($specialCourses) {
                $this->tpl->assign('courses', $specialCourses);
                $html = $this->tpl->fetch(
                    $this->tpl->get_template('/user_portal/classic_courses_without_category.tpl')
                );
            }

            // Display courses
            // [code=>xxx, real_id=>000]
            $listCourses = CourseManager::get_courses_list_by_user_id(
                $user_id,
                false
            );

            foreach ($listCourses as $i => $listCourseCodeId) {
                if (isset($listCourseCodeId['special_course'])) {
                    continue;
                }
                $courseCategory = CourseManager::getUserCourseCategoryForCourse(
                    $user_id,
                    $listCourseCodeId['real_id']
                );

                $userCatTitle = '';
                $userCategoryId = 0;
                if ($courseCategory) {
                    $userCategoryId = $courseCategory['user_course_cat'];
                    $userCatTitle = $courseCategory['title'];
                }

                $listCourse = api_get_course_info_by_id($listCourseCodeId['real_id']);
                $listCoursesInfo[] = [
                    'course' => $listCourse,
                    'code' => $listCourseCodeId['code'],
                    'id' => $listCourseCodeId['real_id'],
                    'title' => $listCourse['title'],
                    'userCatId' => $userCategoryId,
                    'userCatTitle' => $userCatTitle,
                ];
                $courseCount++;
            }
            usort($listCoursesInfo, 'self::compareByCourse');
        }

        $listCoursesInSession = [];
        if (is_array($session_categories)) {
            // all courses that are in a session
            $listCoursesInSession = SessionManager::getNamedSessionCourseForCoach($user_id);
        }

        // we got all courses
        // for each user category, sorted alphabetically, display courses
        $listUserCategories = CourseManager::get_user_course_categories($user_id);
        $listCoursesAlreadyDisplayed = [];
        uasort($listUserCategories, "self::compareListUserCategory");
        $listUserCategories[0] = '';

        $html .= '<div class="session-view-block">';
        foreach ($listUserCategories as $userCategoryId => $userCat) {
            // add user category
            $userCategoryHtml = '';
            if ($userCategoryId != 0) {
                $userCategoryHtml = '<div class="session-view-well ">';
                $userCategoryHtml .= self::getHtmlForUserCategory($userCategoryId, $userCat['title']);
            }
            // look for course in this userCat in session courses : $listCoursesInSession
            $htmlCategory = '';
            if (isset($listCoursesInSession[$userCategoryId])) {
                // list of courses in this user cat
                foreach ($listCoursesInSession[$userCategoryId]['courseInUserCatList'] as $i => $listCourse) {
                    // add course
                    $listCoursesAlreadyDisplayed[$listCourse['courseId']] = 1;
                    if ($userCategoryId == 0) {
                        $htmlCategory .= '<div class="panel panel-default">';
                    } else {
                        $htmlCategory .= '<div class="panel panel-default">';
                    }
                    $htmlCategory .= '<div class="panel-body">';
                    $coursesInfo = $listCourse['course'];

                    $htmlCategory .= self::getHtmlForCourse(
                        $coursesInfo,
                        $userCategoryId,
                        1,
                        $loadDirs
                    );
                    // list of session category
                    $htmlSessionCategory = '<div
                        class="session-view-row"
                        style="display:none;"
                        id="courseblock-'.$coursesInfo['real_id'].'"
                        >';
                    foreach ($listCourse['sessionCatList'] as $listCategorySession) {
                        $catSessionId = null;
                        if (isset($listCategorySession['catSessionId'])) {
                            $catSessionId = $listCategorySession['catSessionId'];
                        }
                        // add session category
                        if ($catSessionId) {
                            $htmlSessionCategory .= self::getHtmlSessionCategory(
                                $listCategorySession['catSessionId'],
                                $listCategorySession['catSessionName']
                            );
                        }

                        // list of session
                        $htmlSession = ''; // start
                        foreach ($listCategorySession['sessionList'] as $listSession) {
                            // add session
                            $htmlSession .= '<div class="session-view-row">';
                            $htmlSession .= self::getHtmlForSession(
                                $listSession['sessionId'],
                                $listSession['sessionName'],
                                $catSessionId,
                                $coursesInfo
                            );
                            $htmlSession .= '</div>';
                            $sessionCount++;
                        }
                        $htmlSession .= ''; // end session block
                        $htmlSessionCategory .= $htmlSession;
                    }
                    $htmlSessionCategory .= '</div>'; // end session cat block
                    $htmlCategory .= $htmlSessionCategory.'</div></div>';
                    $htmlCategory .= ''; // end course block
                }
                $userCategoryHtml .= $htmlCategory;
            }

            // look for courses in this userCat in not in session courses : $listCoursesInfo
            // if course not already added
            $htmlCategory = '';
            foreach ($listCoursesInfo as $i => $listCourse) {
                if ($listCourse['userCatId'] == $userCategoryId &&
                    !isset($listCoursesAlreadyDisplayed[$listCourse['id']])
                ) {
                    if ($userCategoryId != 0) {
                        $htmlCategory .= '<div class="panel panel-default">';
                    } else {
                        $htmlCategory .= '<div class="panel panel-default">';
                    }

                    $htmlCategory .= '<div class="panel-body">';
                    $htmlCategory .= self::getHtmlForCourse(
                        $listCourse['course'],
                        $userCategoryId,
                        0,
                        $loadDirs
                    );
                    $htmlCategory .= '</div></div>';
                }
            }
            $htmlCategory .= '';
            $userCategoryHtml .= $htmlCategory; // end user cat block
            if ($userCategoryId != 0) {
                $userCategoryHtml .= '</div>';
            }
            $html .= $userCategoryHtml;
        }
        $html .= '</div>';

        return [
            'html' => $html,
            'sessions' => $session_categories,
            'courses' => $listCoursesInfo,
            'session_count' => $sessionCount,
            'course_count' => $courseCount,
        ];
    }

    /**
     * @param int $userId
     *
     * @return array
     */
    public function returnCourseCategoryListFromUser($userId)
    {
        $sessionCount = 0;
        $courseList = CourseManager::get_courses_list_by_user_id($userId);
        $categoryCodes = CourseManager::getCourseCategoriesFromCourseList($courseList);
        $categories = [];
        foreach ($categoryCodes as $categoryCode) {
            $categories[] = CourseCategory::getCategory($categoryCode);
        }

        $template = new Template('', false, false, false, true, false, false);
        $layout = $template->get_template('user_portal/course_categories.tpl');
        $template->assign('course_categories', $categories);

        return [
            'courses' => $courseList,
            'html' => $template->fetch($layout),
            'course_count' => count($courseList),
            'session_count' => $sessionCount,
        ];
    }

    /**
     * Set grade book dependency progress bar see BT#13099.
     *
     * @param $userId
     *
     * @return bool
     */
    public function setGradeBookDependencyBar($userId)
    {
        $allow = api_get_configuration_value('gradebook_dependency');

        if (api_is_anonymous()) {
            return false;
        }

        if ($allow) {
            $courseAndSessions = $this->returnCoursesAndSessions(
                $userId,
                false,
                '',
                false,
                false
            );

            $courseList = api_get_configuration_value('gradebook_dependency_mandatory_courses');
            $courseList = isset($courseList['courses']) ? $courseList['courses'] : [];
            $mandatoryCourse = [];
            if (!empty($courseList)) {
                foreach ($courseList as $courseId) {
                    $courseInfo = api_get_course_info_by_id($courseId);
                    $mandatoryCourse[] = $courseInfo['code'];
                }
            }

            // @todo improve calls of course info
            $subscribedCourses = !empty($courseAndSessions['courses']) ? $courseAndSessions['courses'] : [];
            $mainCategoryList = [];
            foreach ($subscribedCourses as $courseInfo) {
                $courseCode = $courseInfo['code'];
                $categories = Category::load(null, null, $courseCode);
                /** @var Category $category */
                $category = !empty($categories[0]) ? $categories[0] : [];
                if (!empty($category)) {
                    $mainCategoryList[] = $category;
                }
            }

            $result20 = 0;
            $result80 = 0;
            $countCoursesPassedNoDependency = 0;
            /** @var Category $category */
            foreach ($mainCategoryList as $category) {
                $userFinished = Category::userFinishedCourse(
                    $userId,
                    $category,
                    true
                );

                if ($userFinished) {
                    if (in_array($category->get_course_code(), $mandatoryCourse)) {
                        if ($result20 < 20) {
                            $result20 += 10;
                        }
                    } else {
                        $countCoursesPassedNoDependency++;
                        if ($result80 < 80) {
                            $result80 += 10;
                        }
                    }
                }
            }

            $finalResult = $result20 + $result80;

            $gradeBookList = api_get_configuration_value('gradebook_badge_sidebar');
            $gradeBookList = isset($gradeBookList['gradebooks']) ? $gradeBookList['gradebooks'] : [];
            $badgeList = [];
            foreach ($gradeBookList as $id) {
                $categories = Category::load($id);
                /** @var Category $category */
                $category = !empty($categories[0]) ? $categories[0] : [];
                $badgeList[$id]['name'] = $category->get_name();
                $badgeList[$id]['finished'] = false;
                $badgeList[$id]['skills'] = [];
                if (!empty($category)) {
                    $minToValidate = $category->getMinimumToValidate();
                    $dependencies = $category->getCourseListDependency();
                    $gradeBooksToValidateInDependence = $category->getGradeBooksToValidateInDependence();
                    $countDependenciesPassed = 0;
                    foreach ($dependencies as $courseId) {
                        $courseInfo = api_get_course_info_by_id($courseId);
                        $courseCode = $courseInfo['code'];
                        $categories = Category::load(null, null, $courseCode);
                        $subCategory = !empty($categories[0]) ? $categories[0] : null;
                        if (!empty($subCategory)) {
                            $score = Category::userFinishedCourse(
                                $userId,
                                $subCategory,
                                true
                            );
                            if ($score) {
                                $countDependenciesPassed++;
                            }
                        }
                    }

                    $userFinished =
                        $countDependenciesPassed >= $gradeBooksToValidateInDependence &&
                        $countCoursesPassedNoDependency >= $minToValidate;

                    if ($userFinished) {
                        $badgeList[$id]['finished'] = true;
                    }

                    $objSkill = new Skill();
                    $skills = $category->get_skills();
                    $skillList = [];
                    foreach ($skills as $skill) {
                        $skillList[] = $objSkill->get($skill['id']);
                    }
                    $badgeList[$id]['skills'] = $skillList;
                }
            }

            $this->tpl->assign(
                'grade_book_sidebar',
                true
            );

            $this->tpl->assign(
                'grade_book_progress',
                $finalResult
            );
            $this->tpl->assign('grade_book_badge_list', $badgeList);

            return true;
        }

        return false;
    }

    /**
     * Prints the session and course list (user_portal.php).
     *
     * @param int    $user_id
     * @param bool   $showSessions
     * @param string $categoryCodeFilter
     * @param bool   $useUserLanguageFilterIfAvailable
     * @param bool   $loadHistory
     *
     * @return array
     */
    public function returnCoursesAndSessions(
        $user_id,
        $showSessions = true,
        $categoryCodeFilter = '',
        $useUserLanguageFilterIfAvailable = true,
        $loadHistory = false
    ) {
        $gameModeIsActive = api_get_setting('gamification_mode');
        $viewGridCourses = api_get_configuration_value('view_grid_courses');
        $showSimpleSessionInfo = api_get_configuration_value('show_simple_session_info');
        $coursesWithoutCategoryTemplate = '/user_portal/classic_courses_without_category.tpl';
        $coursesWithCategoryTemplate = '/user_portal/classic_courses_with_category.tpl';
        $showAllSessions = api_get_configuration_value('show_all_sessions_on_my_course_page') === true;

        if ($loadHistory) {
            // Load sessions in category in *history*
            $session_categories = UserManager::get_sessions_by_category($user_id, true);
        } else {
            // Load sessions in category
            $session_categories = UserManager::get_sessions_by_category($user_id, false);
        }

        $sessionCount = 0;
        $courseCount = 0;

        // Student info code check (shows student progress information on
        // courses list
        $studentInfo = api_get_configuration_value('course_student_info');

        $studentInfoProgress = !empty($studentInfo['progress']) && $studentInfo['progress'] === true;
        $studentInfoScore = !empty($studentInfo['score']) && $studentInfo['score'] === true;
        $studentInfoCertificate = !empty($studentInfo['certificate']) && $studentInfo['certificate'] === true;
        $courseCompleteList = [];
        $coursesInCategoryCount = 0;
        $coursesNotInCategoryCount = 0;
        $listCourse = '';
        $specialCourseList = '';

        // If we're not in the history view...
        if ($loadHistory === false) {
            // Display special courses.
            $specialCourses = CourseManager::returnSpecialCourses(
                $user_id,
                $this->load_directories_preview,
                $useUserLanguageFilterIfAvailable
            );

            // Display courses.
            $courses = CourseManager::returnCourses(
                $user_id,
                $this->load_directories_preview,
                $useUserLanguageFilterIfAvailable
            );

            // Course option (show student progress)
            // This code will add new variables (Progress, Score, Certificate)
            if ($studentInfoProgress || $studentInfoScore || $studentInfoCertificate) {
                if (!empty($specialCourses)) {
                    foreach ($specialCourses as $key => $specialCourseInfo) {
                        if ($studentInfoProgress) {
                            $progress = Tracking::get_avg_student_progress(
                                $user_id,
                                $specialCourseInfo['course_code']
                            );
                            $specialCourses[$key]['student_info']['progress'] = $progress === false ? null : $progress;
                        }

                        if ($studentInfoScore) {
                            $percentage_score = Tracking::get_avg_student_score(
                                $user_id,
                                $specialCourseInfo['course_code'],
                                []
                            );
                            $specialCourses[$key]['student_info']['score'] = $percentage_score;
                        }

                        if ($studentInfoCertificate) {
                            $category = Category::load(
                                null,
                                null,
                                $specialCourseInfo['course_code'],
                                null,
                                null,
                                null
                            );
                            $specialCourses[$key]['student_info']['certificate'] = null;
                            if (isset($category[0])) {
                                if ($category[0]->is_certificate_available($user_id)) {
                                    $specialCourses[$key]['student_info']['certificate'] = Display::label(
                                        get_lang('Yes'),
                                        'success'
                                    );
                                } else {
                                    $specialCourses[$key]['student_info']['certificate'] = Display::label(
                                        get_lang('No'),
                                        'danger'
                                    );
                                }
                            }
                        }
                    }
                }

                if (isset($courses['in_category'])) {
                    foreach ($courses['in_category'] as $key1 => $value) {
                        if (isset($courses['in_category'][$key1]['courses'])) {
                            foreach ($courses['in_category'][$key1]['courses'] as $key2 => $courseInCatInfo) {
                                $courseCode = $courseInCatInfo['course_code'];
                                if ($studentInfoProgress) {
                                    $progress = Tracking::get_avg_student_progress(
                                        $user_id,
                                        $courseCode
                                    );
                                    $courses['in_category'][$key1]['courses'][$key2]['student_info']['progress'] = $progress === false ? null : $progress;
                                }

                                if ($studentInfoScore) {
                                    $percentage_score = Tracking::get_avg_student_score(
                                        $user_id,
                                        $courseCode,
                                        []
                                    );
                                    $courses['in_category'][$key1]['courses'][$key2]['student_info']['score'] = $percentage_score;
                                }

                                if ($studentInfoCertificate) {
                                    $category = Category::load(
                                        null,
                                        null,
                                        $courseCode,
                                        null,
                                        null,
                                        null
                                    );
                                    $courses['in_category'][$key1]['student_info']['certificate'] = null;
                                    $isCertificateAvailable = $category[0]->is_certificate_available($user_id);
                                    if (isset($category[0])) {
                                        if ($isCertificateAvailable) {
                                            $courses['in_category'][$key1]['student_info']['certificate'] = Display::label(
                                                get_lang('Yes'),
                                                'success'
                                            );
                                        } else {
                                            $courses['in_category'][$key1]['student_info']['certificate'] = Display::label(
                                                get_lang('No'),
                                                'danger'
                                            );
                                        }
                                    }
                                }
                            }
                        }
                    }
                }

                if (isset($courses['not_category'])) {
                    foreach ($courses['not_category'] as $key => $courseNotInCatInfo) {
                        $courseCode = $courseNotInCatInfo['course_code'];
                        if ($studentInfoProgress) {
                            $progress = Tracking::get_avg_student_progress(
                                $user_id,
                                $courseCode
                            );
                            $courses['not_category'][$key]['student_info']['progress'] = $progress === false ? null : $progress;
                        }

                        if ($studentInfoScore) {
                            $percentage_score = Tracking::get_avg_student_score(
                                $user_id,
                                $courseCode,
                                []
                            );
                            $courses['not_category'][$key]['student_info']['score'] = $percentage_score;
                        }

                        if ($studentInfoCertificate) {
                            $category = Category::load(
                                null,
                                null,
                                $courseCode,
                                null,
                                null,
                                null
                            );
                            $courses['not_category'][$key]['student_info']['certificate'] = null;

                            if (isset($category[0])) {
                                $certificateAvailable = $category[0]->is_certificate_available($user_id);
                                if ($certificateAvailable) {
                                    $courses['not_category'][$key]['student_info']['certificate'] = Display::label(
                                        get_lang('Yes'),
                                        'success'
                                    );
                                } else {
                                    $courses['not_category'][$key]['student_info']['certificate'] = Display::label(
                                        get_lang('No'),
                                        'danger'
                                    );
                                }
                            }
                        }
                    }
                }
            }

            if ($viewGridCourses) {
                $coursesWithoutCategoryTemplate = '/user_portal/grid_courses_without_category.tpl';
                $coursesWithCategoryTemplate = '/user_portal/grid_courses_with_category.tpl';
            }

            if ($specialCourses) {
                if ($categoryCodeFilter) {
                    $specialCourses = self::filterByCategory($specialCourses, $categoryCodeFilter);
                }
                $this->tpl->assign('courses', $specialCourses);
                $specialCourseList = $this->tpl->fetch($this->tpl->get_template($coursesWithoutCategoryTemplate));
                $courseCompleteList = array_merge($courseCompleteList, $specialCourses);
            }

            if ($courses['in_category'] || $courses['not_category']) {
                foreach ($courses['in_category'] as $courseData) {
                    if (!empty($courseData['courses'])) {
                        $coursesInCategoryCount += count($courseData['courses']);
                        $courseCompleteList = array_merge($courseCompleteList, $courseData['courses']);
                    }
                }

                $coursesNotInCategoryCount += count($courses['not_category']);
                $courseCompleteList = array_merge($courseCompleteList, $courses['not_category']);

                if ($categoryCodeFilter) {
                    $courses['in_category'] = self::filterByCategory(
                        $courses['in_category'],
                        $categoryCodeFilter
                    );
                    $courses['not_category'] = self::filterByCategory(
                        $courses['not_category'],
                        $categoryCodeFilter
                    );
                }

                $this->tpl->assign('courses', $courses['not_category']);
                $this->tpl->assign('categories', $courses['in_category']);

                $listCourse = $this->tpl->fetch($this->tpl->get_template($coursesWithCategoryTemplate));
                $listCourse .= $this->tpl->fetch($this->tpl->get_template($coursesWithoutCategoryTemplate));
            }

            $courseCount = count($specialCourses) + $coursesInCategoryCount + $coursesNotInCategoryCount;
        }

        $sessions_with_category = '';
        $sessions_with_no_category = '';
        $collapsable = api_get_configuration_value('allow_user_session_collapsable');
        $collapsableLink = '';
        if ($collapsable) {
            $collapsableLink = api_get_path(WEB_PATH).'user_portal.php?action=collapse_session';
        }

        $extraFieldValue = new ExtraFieldValue('session');
        if ($showSessions) {
            $coursesListSessionStyle = api_get_configuration_value('courses_list_session_title_link');
            $coursesListSessionStyle = $coursesListSessionStyle === false ? 1 : $coursesListSessionStyle;
            if (api_is_drh()) {
                $coursesListSessionStyle = 1;
            }

            $portalShowDescription = api_get_setting('show_session_description') === 'true';

            // Declared listSession variable
            $listSession = [];
            // Get timestamp in UTC to compare to DB values (in UTC by convention)
            $session_now = strtotime(api_get_utc_datetime(time()));
            $allowUnsubscribe = api_get_configuration_value('enable_unsubscribe_button_on_my_course_page');
            if (is_array($session_categories)) {
                foreach ($session_categories as $session_category) {
                    $session_category_id = $session_category['session_category']['id'];
                    // Sessions and courses that are not in a session category
                    if (empty($session_category_id) &&
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
                            $count_courses_session = 0;

                            // Loop course content
                            $html_courses_session = [];
                            $atLeastOneCourseIsVisible = false;
                            $markAsOld = false;
                            $markAsFuture = false;

                            foreach ($session['courses'] as $course) {
                                $is_coach_course = api_is_coach($session_id, $course['real_id']);
                                $allowed_time = 0;
                                $allowedEndTime = true;

                                if (!empty($date_session_start)) {
                                    if ($is_coach_course) {
                                        $allowed_time = api_strtotime($coachAccessStartDate);
                                    } else {
                                        $allowed_time = api_strtotime($date_session_start);
                                    }

                                    $endSessionToTms = null;
                                    if (!isset($_GET['history'])) {
                                        if (!empty($date_session_end)) {
                                            if ($is_coach_course) {
                                                // if coach end date is empty we use the default end date
                                                if (empty($coachAccessEndDate)) {
                                                    $endSessionToTms = api_strtotime($date_session_end);
                                                    if ($session_now > $endSessionToTms) {
                                                        $allowedEndTime = false;
                                                    }
                                                } else {
                                                    $endSessionToTms = api_strtotime($coachAccessEndDate);
                                                    if ($session_now > $endSessionToTms) {
                                                        $allowedEndTime = false;
                                                    }
                                                }
                                            } else {
                                                $endSessionToTms = api_strtotime($date_session_end);
                                                if ($session_now > $endSessionToTms) {
                                                    $allowedEndTime = false;
                                                }
                                            }
                                        }
                                    }
                                }

                                if ($showAllSessions) {
                                    if ($allowed_time < $session_now && $allowedEndTime === false) {
                                        $markAsOld = true;
                                    }
                                    if ($allowed_time > $session_now && $endSessionToTms > $session_now) {
                                        $markAsFuture = true;
                                    }
                                    $allowedEndTime = true;
                                    $allowed_time = 0;
                                }

                                if ($session_now >= $allowed_time && $allowedEndTime) {
                                    // Read only and accessible.
                                    $atLeastOneCourseIsVisible = true;
                                    if (api_get_setting('hide_courses_in_sessions') === 'false') {
                                        $courseUserHtml = CourseManager::get_logged_user_course_html(
                                            $course,
                                            $session_id,
                                            'session_course_item',
                                            true,
                                            $this->load_directories_preview
                                        );
                                        if (isset($courseUserHtml[1])) {
                                            $course_session = $courseUserHtml[1];
                                            $course_session['skill'] = isset($courseUserHtml['skill']) ? $courseUserHtml['skill'] : '';

                                            // Course option (show student progress)
                                            // This code will add new variables (Progress, Score, Certificate)
                                            if ($studentInfoProgress || $studentInfoScore || $studentInfoCertificate) {
                                                if ($studentInfoProgress) {
                                                    $progress = Tracking::get_avg_student_progress(
                                                        $user_id,
                                                        $course['course_code'],
                                                        [],
                                                        $session_id
                                                    );
                                                    $course_session['student_info']['progress'] = $progress === false ? null : $progress;
                                                }

                                                if ($studentInfoScore) {
                                                    $percentage_score = Tracking::get_avg_student_score(
                                                        $user_id,
                                                        $course['course_code'],
                                                        [],
                                                        $session_id
                                                    );
                                                    $course_session['student_info']['score'] = $percentage_score;
                                                }

                                                if ($studentInfoCertificate) {
                                                    $category = Category::load(
                                                        null,
                                                        null,
                                                        $course['course_code'],
                                                        null,
                                                        null,
                                                        $session_id
                                                    );
                                                    $course_session['student_info']['certificate'] = null;
                                                    if (isset($category[0])) {
                                                        if ($category[0]->is_certificate_available($user_id)) {
                                                            $course_session['student_info']['certificate'] = Display::label(
                                                                get_lang('Yes'),
                                                                'success'
                                                            );
                                                        } else {
                                                            $course_session['student_info']['certificate'] = Display::label(
                                                                get_lang('No'),
                                                                'danger'
                                                            );
                                                        }
                                                    }
                                                }
                                            }

                                            $course_session['extrafields'] = CourseManager::getExtraFieldsToBePresented($course['real_id']);
                                            if (false === $is_coach_course && $allowUnsubscribe && '1' === $course['unsubscribe']) {
                                                $course_session['unregister_button'] =
                                                    CoursesAndSessionsCatalog::return_unregister_button(
                                                        ['code' => $course['course_code']],
                                                        Security::get_existing_token(),
                                                        '',
                                                        '',
                                                        $session_id
                                                    );
                                            }

                                            $html_courses_session[] = $course_session;
                                        }
                                    }
                                    $count_courses_session++;
                                }
                            }

                            // No courses to show.
                            if ($atLeastOneCourseIsVisible === false) {
                                if (empty($html_courses_session)) {
                                    continue;
                                }
                            }

                            if ($count_courses_session > 0) {
                                $params = [
                                    'id' => $session_id,
                                ];
                                $session_box = Display::getSessionTitleBox($session_id);
                                $coachId = $session_box['id_coach'];
                                $imageField = $extraFieldValue->get_values_by_handler_and_field_variable(
                                    $session_id,
                                    'image'
                                );

                                $params['category_id'] = $session_box['category_id'];
                                $params['title'] = $session_box['title'];
                                $params['id_coach'] = $coachId;
                                $params['coach_url'] = api_get_path(WEB_AJAX_PATH).
                                    'user_manager.ajax.php?a=get_user_popup&user_id='.$coachId;
                                $params['coach_name'] = !empty($session_box['coach']) ? $session_box['coach'] : null;
                                $params['coach_avatar'] = UserManager::getUserPicture(
                                    $coachId,
                                    USER_IMAGE_SIZE_SMALL
                                );
                                $params['date'] = $session_box['dates'];
                                $params['image'] = isset($imageField['value']) ? $imageField['value'] : null;
                                $params['duration'] = isset($session_box['duration']) ? ' '.$session_box['duration'] : null;
                                $params['show_actions'] = SessionManager::cantEditSession($session_id);

                                if ($collapsable) {
                                    $collapsableData = SessionManager::getCollapsableData(
                                        $user_id,
                                        $session_id,
                                        $extraFieldValue,
                                        $collapsableLink
                                    );
                                    $params['collapsed'] = $collapsableData['collapsed'];
                                    $params['collapsable_link'] = $collapsableData['collapsable_link'];
                                }

                                $params['show_description'] = $session_box['show_description'] == 1 && $portalShowDescription;
                                $params['description'] = $session_box['description'];
                                $params['visibility'] = $session_box['visibility'];
                                $params['show_simple_session_info'] = $showSimpleSessionInfo;
                                $params['course_list_session_style'] = $coursesListSessionStyle;
                                $params['num_users'] = $session_box['num_users'];
                                $params['num_courses'] = $session_box['num_courses'];
                                $params['course_categories'] = CourseManager::getCourseCategoriesFromCourseList(
                                    $html_courses_session
                                );
                                $params['courses'] = $html_courses_session;
                                $params['is_old'] = $markAsOld;
                                $params['is_future'] = $markAsFuture;

                                if ($showSimpleSessionInfo) {
                                    $params['subtitle'] = self::getSimpleSessionDetails(
                                        $session_box['coach'],
                                        $session_box['dates'],
                                        isset($session_box['duration']) ? $session_box['duration'] : null
                                    );
                                }

                                if ($gameModeIsActive) {
                                    $params['stars'] = GamificationUtils::getSessionStars(
                                        $params['id'],
                                        $this->user_id
                                    );
                                    $params['progress'] = GamificationUtils::getSessionProgress(
                                        $params['id'],
                                        $this->user_id
                                    );
                                    $params['points'] = GamificationUtils::getSessionPoints(
                                        $params['id'],
                                        $this->user_id
                                    );
                                }
                                $listSession[] = $params;
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
                                $html_courses_session = [];
                                $count = 0;
                                $markAsOld = false;
                                $markAsFuture = false;

                                foreach ($session['courses'] as $course) {
                                    $is_coach_course = api_is_coach($session_id, $course['real_id']);
                                    $allowed_time = 0;
                                    $allowedEndTime = true;

                                    if (!empty($date_session_start)) {
                                        if ($is_coach_course) {
                                            $allowed_time = api_strtotime($coachAccessStartDate);
                                        } else {
                                            $allowed_time = api_strtotime($date_session_start);
                                        }

                                        if (!isset($_GET['history'])) {
                                            if (!empty($date_session_end)) {
                                                if ($is_coach_course) {
                                                    // if coach end date is empty we use the default end date
                                                    if (empty($coachAccessEndDate)) {
                                                        $endSessionToTms = api_strtotime($date_session_end);
                                                        if ($session_now > $endSessionToTms) {
                                                            $allowedEndTime = false;
                                                        }
                                                    } else {
                                                        $endSessionToTms = api_strtotime($coachAccessEndDate);
                                                        if ($session_now > $endSessionToTms) {
                                                            $allowedEndTime = false;
                                                        }
                                                    }
                                                } else {
                                                    $endSessionToTms = api_strtotime($date_session_end);
                                                    if ($session_now > $endSessionToTms) {
                                                        $allowedEndTime = false;
                                                    }
                                                }
                                            }
                                        }
                                    }

                                    if ($showAllSessions) {
                                        if ($allowed_time < $session_now && $allowedEndTime == false) {
                                            $markAsOld = true;
                                        }
                                        if ($allowed_time > $session_now && $endSessionToTms > $session_now) {
                                            $markAsFuture = true;
                                        }
                                        $allowedEndTime = true;
                                        $allowed_time = 0;
                                    }

                                    if ($session_now >= $allowed_time && $allowedEndTime) {
                                        if (api_get_setting('hide_courses_in_sessions') === 'false') {
                                            $c = CourseManager::get_logged_user_course_html(
                                                $course,
                                                $session_id,
                                                'session_course_item'
                                            );
                                            if (isset($c[1])) {
                                                $course_session = $c[1];
                                                $course_session['skill'] = isset($c['skill']) ? $c['skill'] : '';

                                                // Course option (show student progress)
                                                // This code will add new variables (Progress, Score, Certificate)
                                                if ($studentInfoProgress || $studentInfoScore || $studentInfoCertificate) {
                                                    if ($studentInfoProgress) {
                                                        $progress = Tracking::get_avg_student_progress(
                                                            $user_id,
                                                            $course['course_code'],
                                                            [],
                                                            $session_id
                                                        );
                                                        $course_session['student_info']['progress'] = $progress === false ? null : $progress;
                                                    }

                                                    if ($studentInfoScore) {
                                                        $percentage_score = Tracking::get_avg_student_score(
                                                            $user_id,
                                                            $course['course_code'],
                                                            [],
                                                            $session_id
                                                        );
                                                        $course_session['student_info']['score'] = $percentage_score;
                                                    }

                                                    if ($studentInfoCertificate) {
                                                        $category = Category::load(
                                                            null,
                                                            null,
                                                            $course['course_code'],
                                                            null,
                                                            null,
                                                            $session_id
                                                        );
                                                        $course_session['student_info']['certificate'] = null;
                                                        if (isset($category[0])) {
                                                            if ($category[0]->is_certificate_available($user_id)) {
                                                                $course_session['student_info']['certificate'] = Display::label(
                                                                    get_lang('Yes'),
                                                                    'success'
                                                                );
                                                            } else {
                                                                $course_session['student_info']['certificate'] = Display::label(
                                                                    get_lang('No'),
                                                                    'danger'
                                                                );
                                                            }
                                                        }
                                                    }
                                                }

                                                $course_session['extrafields'] = CourseManager::getExtraFieldsToBePresented($course['real_id']);
                                                if (false === $is_coach_course && $allowUnsubscribe && '1' === $course['unsubscribe']) {
                                                    $course_session['unregister_button'] =
                                                        CoursesAndSessionsCatalog::return_unregister_button(
                                                            ['code' => $course['course_code']],
                                                            Security::get_existing_token(),
                                                            '',
                                                            '',
                                                            $session_id
                                                        );
                                                }

                                                $html_courses_session[] = $course_session;
                                            }
                                        }
                                        $count_courses_session++;
                                        $count++;
                                    }
                                }

                                $sessionParams = [];
                                // Category
                                if ($count > 0) {
                                    $session_box = Display::getSessionTitleBox($session_id);
                                    $sessionParams[0]['id'] = $session_id;
                                    $sessionParams[0]['date'] = $session_box['dates'];
                                    $sessionParams[0]['duration'] = isset($session_box['duration']) ? ' '.$session_box['duration'] : null;
                                    $sessionParams[0]['course_list_session_style'] = $coursesListSessionStyle;
                                    $sessionParams[0]['title'] = $session_box['title'];
                                    $sessionParams[0]['subtitle'] = (!empty($session_box['coach']) ? $session_box['coach'].' | ' : '').$session_box['dates'];
                                    $sessionParams[0]['show_actions'] = SessionManager::cantEditSession($session_id);
                                    $sessionParams[0]['courses'] = $html_courses_session;
                                    $sessionParams[0]['show_simple_session_info'] = $showSimpleSessionInfo;
                                    $sessionParams[0]['coach_name'] = !empty($session_box['coach']) ? $session_box['coach'] : null;
                                    $sessionParams[0]['is_old'] = $markAsOld;
                                    $sessionParams[0]['is_future'] = $markAsFuture;

                                    if ($collapsable) {
                                        $collapsableData = SessionManager::getCollapsableData(
                                            $user_id,
                                            $session_id,
                                            $extraFieldValue,
                                            $collapsableLink
                                        );
                                        $sessionParams[0]['collapsable_link'] = $collapsableData['collapsable_link'];
                                        $sessionParams[0]['collapsed'] = $collapsableData['collapsed'];
                                    }

                                    if ($showSimpleSessionInfo) {
                                        $sessionParams[0]['subtitle'] = self::getSimpleSessionDetails(
                                            $session_box['coach'],
                                            $session_box['dates'],
                                            isset($session_box['duration']) ? $session_box['duration'] : null
                                        );
                                    }
                                    $this->tpl->assign('session', $sessionParams);
                                    $this->tpl->assign('show_tutor', api_get_setting('show_session_coach') === 'true');
                                    $this->tpl->assign('gamification_mode', $gameModeIsActive);
                                    $this->tpl->assign(
                                        'remove_session_url',
                                        api_get_configuration_value('remove_session_url')
                                    );
                                    $this->tpl->assign(
                                        'hide_session_dates_in_user_portal',
                                        api_get_configuration_value('hide_session_dates_in_user_portal')
                                    );

                                    if ($viewGridCourses) {
                                        $html_sessions .= $this->tpl->fetch(
                                            $this->tpl->get_template('/user_portal/grid_session.tpl')
                                        );
                                    } else {
                                        $html_sessions .= $this->tpl->fetch(
                                            $this->tpl->get_template('user_portal/classic_session.tpl')
                                        );
                                    }
                                    $sessionCount++;
                                }
                            }
                        }

                        if ($count_courses_session > 0) {
                            $categoryParams = [
                                'id' => $session_category['session_category']['id'],
                                'title' => $session_category['session_category']['name'],
                                'show_actions' => api_is_platform_admin(),
                                'subtitle' => '',
                                'sessions' => $html_sessions,
                            ];

                            $session_category_start_date = $session_category['session_category']['date_start'];
                            $session_category_end_date = $session_category['session_category']['date_end'];
                            if ($session_category_start_date == '0000-00-00') {
                                $session_category_start_date = '';
                            }

                            if ($session_category_end_date == '0000-00-00') {
                                $session_category_end_date = '';
                            }

                            if (!empty($session_category_start_date) &&
                                !empty($session_category_end_date)
                            ) {
                                $categoryParams['subtitle'] = sprintf(
                                    get_lang('FromDateXToDateY'),
                                    $session_category_start_date,
                                    $session_category_end_date
                                );
                            } else {
                                if (!empty($session_category_start_date)) {
                                    $categoryParams['subtitle'] = get_lang('From').' '.$session_category_start_date;
                                }

                                if (!empty($session_category_end_date)) {
                                    $categoryParams['subtitle'] = get_lang('Until').' '.$session_category_end_date;
                                }
                            }

                            $this->tpl->assign('session_category', $categoryParams);
                            $sessions_with_category .= $this->tpl->fetch(
                                $this->tpl->get_template('user_portal/session_category.tpl')
                            );
                        }
                    }
                }

                $allCoursesInSessions = [];
                foreach ($listSession as $currentSession) {
                    $coursesInSessions = $currentSession['courses'];
                    unset($currentSession['courses']);
                    foreach ($coursesInSessions as $coursesInSession) {
                        $coursesInSession['session'] = $currentSession;
                        $allCoursesInSessions[] = $coursesInSession;
                    }
                }

                $this->tpl->assign('all_courses', $allCoursesInSessions);
                $this->tpl->assign('session', $listSession);
                $this->tpl->assign('show_tutor', (api_get_setting('show_session_coach') === 'true' ? true : false));
                $this->tpl->assign('gamification_mode', $gameModeIsActive);
                $this->tpl->assign('remove_session_url', api_get_configuration_value('remove_session_url'));
                $this->tpl->assign(
                    'hide_session_dates_in_user_portal',
                    api_get_configuration_value('hide_session_dates_in_user_portal')
                );

                if ($viewGridCourses) {
                    $sessions_with_no_category = $this->tpl->fetch(
                        $this->tpl->get_template('/user_portal/grid_session.tpl')
                    );
                } else {
                    $sessions_with_no_category = $this->tpl->fetch(
                        $this->tpl->get_template('user_portal/classic_session.tpl')
                    );
                }
            }
        }

        return [
            'courses' => $courseCompleteList,
            'sessions' => $session_categories,
            'html' => trim($specialCourseList.$sessions_with_category.$sessions_with_no_category.$listCourse),
            'session_count' => $sessionCount,
            'course_count' => $courseCount,
        ];
    }

    /**
     * Wrapper to CourseManager::returnPopularCoursesHandPicked().
     *
     * @return array
     */
    public function returnPopularCoursesHandPicked()
    {
        return CourseManager::returnPopularCoursesHandPicked();
    }

    /**
     * @param $listA
     * @param $listB
     *
     * @return int
     */
    private static function compareByCourse($listA, $listB)
    {
        if ($listA['userCatTitle'] == $listB['userCatTitle']) {
            if ($listA['title'] == $listB['title']) {
                return 0;
            }

            if ($listA['title'] > $listB['title']) {
                return 1;
            }

            return -1;
        }

        if ($listA['userCatTitle'] > $listB['userCatTitle']) {
            return 1;
        }

        return -1;
    }

    /**
     * Generate the HTML code for items when displaying the right-side blocks.
     *
     * @return string
     */
    private static function returnRightBlockItems(array $items)
    {
        $my_account_content = '';
        foreach ($items as $item) {
            if (empty($item['link']) && empty($item['title'])) {
                continue;
            }

            $my_account_content .= '<li class="list-group-item '.(empty($item['class']) ? '' : $item['class']).'">'
                .(empty($item['icon']) ? '' : '<span class="item-icon">'.$item['icon'].'</span>')
                .'<a href="'.$item['link'].'">'.$item['title'].'</a>'
                .'</li>';
        }

        return '<ul class="list-group">'.$my_account_content.'</ul>';
    }

    /**
     * Return HTML code for personal user course category.
     *
     * @param $id
     * @param $title
     *
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
            ['class' => 'sessionView'],
            ICON_SIZE_LARGE
        );

        return "<div class='session-view-user-category'>$icon<span>$title</span></div>";
    }

    /**
     * return HTML code for course display in session view.
     *
     * @param array $courseInfo
     * @param       $userCategoryId
     * @param bool  $displayButton
     * @param       $loadDirs
     *
     * @return string
     */
    private static function getHtmlForCourse(
        $courseInfo,
        $userCategoryId,
        $displayButton,
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
            $button = '<input id="session-view-button-'.intval(
                    $id
                ).'" class="btn btn-default btn-sm" type="button" onclick="hideUnhide(\'courseblock-'.intval(
                    $id
                ).'\', \'session-view-button-'.intval($id).'\', \'+\', \'-\')" value="+" />';
        }

        $icon = Display::return_icon(
            'blackboard.png',
            $title,
            ['class' => 'sessionView'],
            ICON_SIZE_LARGE
        );

        $courseLink = $courseInfo['course_public_url'].'?id_session=0';

        // get html course params
        $courseParams = CourseManager::getCourseParamsForDisplay($id, $loadDirs);
        $teachers = '';
        $rightActions = '';

        // teacher list
        if (!empty($courseParams['teachers'])) {
            $teachers = '<p class="'.$class2.' view-by-session-teachers">'.$courseParams['teachers'].'</p>';
        }

        // notification
        if (!empty($courseParams['right_actions'])) {
            $rightActions = '<div class="pull-right">'.$courseParams['right_actions'].'</div>';
        }

        $notifications = isset($courseParams['notifications']) ? $courseParams['notifications'] : '';

        return "<div>
                    $button
                    <span class='$class'>$icon
                        <a class='sessionView' href='$courseLink'>$title</a>
                    </span>
                    $notifications
                    $rightActions
                </div>
                $teachers";
    }

    /**
     * return HTML code for session category.
     *
     * @param $id
     * @param $title
     *
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
            ['class' => 'sessionView'],
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
     * return HTML code for session.
     *
     * @param int    $id                session id
     * @param string $title             session title
     * @param int    $categorySessionId
     * @param array  $courseInfo
     *
     * @return string
     */
    private static function getHtmlForSession($id, $title, $categorySessionId, $courseInfo)
    {
        $html = '';
        if ($categorySessionId == 0) {
            $class1 = 'session-view-lvl-2'; // session
            $class2 = 'session-view-lvl-4'; // got to course in session link
        } else {
            $class1 = 'session-view-lvl-3'; // session
            $class2 = 'session-view-lvl-5'; // got to course in session link
        }

        $icon = Display::return_icon(
            'session.png',
            $title,
            ['class' => 'sessionView'],
            ICON_SIZE_LARGE
        );
        $courseLink = $courseInfo['course_public_url'].'?id_session='.intval($id);

        $html .= "<span class='$class1 session-view-session'>$icon$title</span>";
        $html .= '<div class="'.$class2.' session-view-session-go-to-course-in-session">
                  <a class="" href="'.$courseLink.'">'.get_lang('GoToCourseInsideSession').'</a></div>';

        return '<div>'.$html.'</div>';
    }

    /**
     * Filter the course list by category code.
     *
     * @param array  $courseList   course list
     * @param string $categoryCode
     *
     * @return array
     */
    private static function filterByCategory($courseList, $categoryCode)
    {
        return array_filter(
            $courseList,
            function ($courseInfo) use ($categoryCode) {
                if (isset($courseInfo['category_code']) &&
                    $courseInfo['category_code'] === $categoryCode
                ) {
                    return true;
                }

                return false;
            }
        );
    }

    /**
     * Get the session coach name, duration or dates
     * when $_configuration['show_simple_session_info'] is enabled.
     *
     * @param string      $coachName
     * @param string      $dates
     * @param string|null $duration  Optional
     *
     * @return string
     */
    private static function getSimpleSessionDetails($coachName, $dates, $duration = null)
    {
        $strDetails = [];
        if (!empty($coachName)) {
            $strDetails[] = $coachName;
        }

        $strDetails[] = !empty($duration) ? $duration : $dates;

        return implode(' | ', $strDetails);
    }
}
