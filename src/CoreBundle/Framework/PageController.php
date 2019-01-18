<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Framework;

use ChamiloSession as Session;
use CourseManager;
use Display;
use Pagerfanta\Adapter\FixedAdapter;
use Pagerfanta\Pagerfanta;
use Pagerfanta\View\TwitterBootstrapView;
use UserManager;

/**
 * Class PageController
 * Controller for pages presentation in general.
 *
 * @package chamilo.page.controller
 *
 * @author Julio Montoya <gugli100@gmail.com>
 *
 * @todo move functions in the Template class, remove this class.
 */
class PageController
{
    public $maxPerPage = 5;

    /**
     * Returns an online help block read from the home/home_menu_[lang].html
     * file.
     *
     * @return string HTML block
     */
    public function returnHelp()
    {
        $home = api_get_home_path();
        $user_selected_language = api_get_language_isocode();
        $sys_path = api_get_path(SYS_PATH);
        $platformLanguage = api_get_setting('language.platform_language');

        if (!isset($user_selected_language)) {
            $user_selected_language = $platformLanguage;
        }
        $home_menu = @(string) file_get_contents($sys_path.$home.'home_menu_'.$user_selected_language.'.html');
        if (!empty($home_menu)) {
            $home_menu_content = api_to_system_encoding($home_menu, api_detect_encoding(strip_tags($home_menu)));
            $this->show_right_block(
                get_lang('MenuGeneral'),
                null,
                'help_block',
                ['content' => $home_menu_content]
            );
        }
    }

    /**
     * Returns an HTML block with links to the skills tools.
     *
     * @return string HTML <div> block
     */
    public function returnSkillsLinks()
    {
        if (api_get_setting('skill.allow_skills_tool') == 'true') {
            $content = [];
            $content[] = [
                'title' => get_lang('MySkills'),
                'href' => api_get_path(WEB_CODE_PATH).'social/skills_wheel.php',
            ];

            if (api_get_setting('skill.allow_hr_skills_management') == 'true'
                || api_is_platform_admin()) {
                $content[] = [
                    'title' => get_lang('ManageSkills'),
                    'href' => api_get_path(WEB_CODE_PATH).'admin/skills_wheel.php',
                ];
            }
            $this->show_right_block(get_lang("Skills"), $content, 'skill_block');
        }
    }

    /**
     * Returns an HTML block with the notice, as found in the
     * home/home_notice_[lang].html file.
     *
     * @return string HTML <div> block
     */
    public function returnNotice()
    {
        $sys_path = api_get_path(SYS_PATH);
        $user_selected_language = api_get_language_isocode();
        $home = api_get_home_path();

        // Notice
        $home_notice = @(string) file_get_contents($sys_path.$home.'home_notice_'.$user_selected_language.'.html');
        if (empty($home_notice)) {
            $home_notice = @(string) file_get_contents($sys_path.$home.'home_notice.html');
        }

        if (!empty($home_notice)) {
            $home_notice = api_to_system_encoding($home_notice, api_detect_encoding(strip_tags($home_notice)));
            $home_notice = Display::div($home_notice, ['class' => 'homepage_notice']);

            $this->show_right_block(get_lang('Notice'), null, 'notice_block', ['content' => $home_notice]);
        }
    }

    /**
     * Returns the received content packaged in <div> block, with the title as
     * <h4>.
     *
     * @param string Title to include as h4
     * @param string Longer content to show (usually a <ul> list)
     * @param string ID to be added to the HTML attributes for the block
     * @param array Array of attributes to add to the HTML block
     *
     * @return string HTML <div> block
     *
     * @todo use the menu builder
     */
    public function show_right_block($title, $content, $id, $params = null)
    {
        if (!empty($id)) {
            $params['id'] = $id;
        }
        $block_menu = [
            'id' => $params['id'],
            'title' => $title,
            'elements' => $content,
            'content' => isset($params['content']) ? $params['content'] : null,
        ];

        //$app['template']->assign($id, $block_menu);
    }

    /**
     * Returns a content search form in an HTML <div>, pointing at the
     * main/search/ directory. If search_enabled is not set, then it returns
     * an empty string.
     *
     * @return string HTML <div> block showing the search form, or an empty string if search not enabled
     */
    public function return_search_block()
    {
        $html = '';
        if (api_get_setting('search.search_enabled') == 'true') {
            $html .= '<div class="searchbox">';
            $search_btn = get_lang('Search');
            $search_content = '<br />
                <form action="main/search/" method="post">
                <input type="text" id="query" class="span2" name="query" value="" />
                <button class="save" type="submit" name="submit" value="'.$search_btn.'" />'.$search_btn.' </button>
                </form></div>';
            $html .= $this->show_right_block(get_lang('Search'), $search_content, 'search_block');
        }

        return $html;
    }

    /**
     * Return the homepage, including announcements.
     *
     * @return string The portal's homepage as an HTML string
     */
    public function returnHomePage()
    {
        // Including the page for the news
        $html = null;
        $home = api_get_path(SYS_DATA_PATH).api_get_home_path();
        $home_top_temp = null;

        if (!empty($_GET['include']) && preg_match('/^[a-zA-Z0-9_-]*\.html$/', $_GET['include'])) {
            $open = @(string) file_get_contents(api_get_path(SYS_PATH).$home.$_GET['include']);
            $html = api_to_system_encoding($open, api_detect_encoding(strip_tags($open)));
        } else {
            $user_selected_language = api_get_user_language();

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
     * Returns an HTML block with classes (if show_groups_to_users is true).
     *
     * @return string A list of links to users classes tools, or an empty string if show_groups_to_users is disabled
     */
    public function return_classes_block()
    {
        $html = '';
        if (api_get_setting('show_groups_to_users') == 'true') {
            $usergroup = new Usergroup();
            $usergroup_list = $usergroup->get_usergroup_by_user(api_get_user_id());
            $classes = '';
            if (!empty($usergroup_list)) {
                foreach ($usergroup_list as $group_id) {
                    $data = $usergroup->get($group_id);
                    $data['name'] = Display::url(
                        $data['name'],
                        api_get_path(WEB_CODE_PATH).'user/classes.php?id='.$data['id']
                    );
                    $classes .= Display::tag('li', $data['name']);
                }
            }
            if (api_is_platform_admin()) {
                $classes .= Display::tag(
                    'li',
                    Display::url(get_lang('AddClasses'), api_get_path(WEB_CODE_PATH).'admin/usergroups.php?action=add')
                );
            }
            if (!empty($classes)) {
                $classes = Display::tag('ul', $classes, ['class' => 'nav nav-list']);
                $html .= $this->show_right_block(get_lang('Classes'), $classes, 'classes_block');
            }
        }

        return $html;
    }

    /**
     * Prepares a block with all the pending exercises in all courses.
     *
     * @param array Array of courses (arrays) of the user
     */
    public function return_exercise_block($personal_course_list, $tpl)
    {
        $exercise_list = [];
        if (!empty($personal_course_list)) {
            foreach ($personal_course_list as $course_item) {
                $course_code = $course_item['c'];
                $session_id = $course_item['id_session'];

                $exercises = ExerciseLib::get_exercises_to_be_taken($course_code, $session_id);

                foreach ($exercises as $exercise_item) {
                    $exercise_item['course_code'] = $course_code;
                    $exercise_item['session_id'] = $session_id;
                    $exercise_item['tms'] = api_strtotime($exercise_item['end_time'], 'UTC');

                    $exercise_list[] = $exercise_item;
                }
            }
            if (!empty($exercise_list)) {
                $exercise_list = ArrayClass::msort($exercise_list, 'tms');
                $my_exercise = $exercise_list[0];
                $url = Display::url(
                    $my_exercise['title'],
                    api_get_path(
                        WEB_CODE_PATH
                    ).'exercise/overview.php?exerciseId='.$my_exercise['id'].'&cidReq='.$my_exercise['course_code'].'&id_session='.$my_exercise['session_id']
                );
                $tpl->assign('exercise_url', $url);
                $tpl->assign(
                    'exercise_end_date',
                    api_convert_and_format_date($my_exercise['end_time'], DATE_FORMAT_SHORT)
                );
            }
        }
    }

    /**
     * Display list of courses in a category.
     * (for anonymous users).
     *
     * @version 1.1
     *
     * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University - refactoring and code cleaning
     * @author Julio Montoya <gugli100@gmail.com>, Beeznest template modifs
     */
    public function return_courses_in_categories()
    {
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
        if (api_is_multiple_url_enabled()) {
            $url_access_id = api_get_current_access_url_id();
            if ($url_access_id != -1) {
                $tbl_url_rel_course = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_COURSE);
                $sql_get_course_list = "SELECT * FROM $main_course_table as course INNER JOIN $tbl_url_rel_course as url_rel_course
                        ON (url_rel_course.c_id = course.id)
                        WHERE access_url_id = $url_access_id AND category_code = '".Database::escape_string(
                    $_GET['category']
                )."' ORDER BY title, UPPER(visual_code)";
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
        if (api_is_multiple_url_enabled()) {
            $url_access_id = api_get_current_access_url_id();
            if ($url_access_id != -1) {
                $tbl_url_rel_course = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_COURSE);
                $sqlGetSubCatList = "
                    SELECT t1.name,t1.code,t1.parent_id,t1.children_count,COUNT(DISTINCT t3.code) AS nbCourse
                    FROM $main_category_table t1
                    LEFT JOIN $main_category_table t2 ON t1.code=t2.parent_id
                    LEFT JOIN $main_course_table t3 ON (t3.category_code=t1.code $platform_visible_courses)
                    INNER JOIN $tbl_url_rel_course as url_rel_course
                        ON (url_rel_course.c_id = t3.id)
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
                    $category_has_open_courses = $this->category_has_open_courses($catLine['code']);
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
                } else {
                    $htmlTitre = '<p>';
                    if (api_get_setting('show_back_link_on_top_of_tree') == 'true') {
                        $htmlTitre .= '<a href="'.api_get_self().'">&lt;&lt; '.get_lang('BackToHomePage').'</a>';
                    }
                    if (!is_null($catLine['parent_id']) ||
                        (api_get_setting('show_back_link_on_top_of_tree') != 'true' &&
                        !is_null($catLine['code']))
                    ) {
                        $htmlTitre .= '<a href="'.api_get_self(
                        ).'?category='.$catLine['parent_id'].'">&lt;&lt; '.get_lang('Up').'</a>';
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
                $courses_of_user = $this->get_courses_of_user(api_get_user_id());
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
                        $course_details = [];
                        if (api_get_setting('course.display_coursecode_in_courselist') ==
                            'true') {
                            $course_details[] = $course['visual_code'];
                        }
                        if (api_get_setting('course.display_teacher_in_courselist') ==
                            'true') {
                            $course_details[] = $course['tutor_name'];
                        }
                        if (api_get_setting('display.show_different_course_language') ==
                            'true' && $course['course_language'] != api_get_setting(
                                'language.platform_language'
                            )
                        ) {
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
                        || ($user_identified && key_exists(
                            $course['code'],
                            $courses_of_user
                        ) && $course['visibility'] != COURSE_VISIBILITY_CLOSED)
                        || $courses_of_user[$course['code']]['status'] == '1'
                        || api_is_platform_admin()
                    ) {
                        $courses_list_string .= '<a href="'.$web_course_path.$course['directory'].'/">';
                    }
                    $courses_list_string .= $course['title'];
                    if ($course['visibility'] == COURSE_VISIBILITY_OPEN_WORLD
                        || ($user_identified && $course['visibility'] == COURSE_VISIBILITY_OPEN_PLATFORM)
                        || ($user_identified && key_exists(
                            $course['code'],
                            $courses_of_user
                        ) && $course['visibility'] != COURSE_VISIBILITY_CLOSED)
                        || $courses_of_user[$course['code']]['status'] == '1'
                        || api_is_platform_admin()
                    ) {
                        $courses_list_string .= '</a><br />';
                    }
                    $course_details = [];
                    if (api_get_setting('course.display_coursecode_in_courselist') == 'true') {
                        $course_details[] = $course['visual_code'];
                    }
                    if (api_get_setting('course.display_teacher_in_courselist') == 'true') {
                        $course_details[] = $course['tutor_name'];
                    }
                    if (api_get_setting(
                            'display.show_different_course_language'
                        ) == 'true' && $course['course_language'] != api_get_setting(
                            'language.platform_language'
                        )
                    ) {
                        $course_details[] = $course['course_language'];
                    }
                    if (api_get_setting(
                        'show_different_course_language'
                        ) == 'true' && $course['course_language'] != api_get_setting(
                            'language.platform_language'
                        )
                    ) {
                        $course_details[] = $course['course_language'];
                    }

                    $courses_list_string .= implode(' - ', $course_details);
                    // We display a subscription link if:
                    // 1. it is allowed to register for the course and if the course is not already in the courselist of the user and if the user is identiefied
                    // 2.
                    if ($user_identified && !array_key_exists($course['code'], $courses_of_user)) {
                        if ($course['subscribe'] == '1') {
                            $courses_list_string .= '<form action="main/auth/courses.php?action=subscribe&category='.Security::remove_XSS(
                                $_GET['category']
                            ).'" method="post">';
                            $courses_list_string .= '<input type="hidden" name="sec_token" value="'.$stok.'">';
                            $courses_list_string .= '<input type="hidden" name="subscribe" value="'.$course['code'].'" />';
                            $courses_list_string .= '<input type="image" name="unsub" src="'.api_get_path(WEB_IMG_PATH).'enroll.gif" alt="'.get_lang('Subscribe').'" />'.get_lang('Subscribe').'
                            </form>';
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
     * @param int    $user_id
     * @param string $filter
     * @param int    $page
     *
     * @return bool
     */
    public function returnMyCourseCategories($user_id, $filter, $page)
    {
        if (empty($user_id)) {
            return false;
        }
        $loadDirs = api_get_setting('document.show_documents_preview') == 'true' ? true : false;
        $start = ($page - 1) * $this->maxPerPage;

        $nbResults = (int) CourseManager::displayPersonalCourseCategories($user_id, $filter, $loadDirs, true);

        $html = CourseManager::displayPersonalCourseCategories(
            $user_id,
            $filter,
            $loadDirs,
            false,
            $start,
            $this->maxPerPage
        );

        $adapter = new FixedAdapter($nbResults, []);
        $pagerfanta = new Pagerfanta($adapter);
        $pagerfanta->setMaxPerPage($this->maxPerPage); // 10 by default
        $pagerfanta->setCurrentPage($page); // 1 by default

        $this->app['pagerfanta.view.router.name'] = 'userportal';
        $this->app['pagerfanta.view.router.params'] = [
            'filter' => $filter,
            'type' => 'courses',
            'page' => $page,
        ];
        $this->app['template']->assign('pagination', $pagerfanta);

        return $html;
    }

    public function returnSpecialCourses($user_id, $filter, $page)
    {
        if (empty($user_id)) {
            return false;
        }

        $loadDirs = api_get_setting('document.show_documents_preview') == 'true' ? true : false;
        $start = ($page - 1) * $this->maxPerPage;

        $nbResults = CourseManager::displaySpecialCourses($user_id, $filter, $loadDirs, true);

        $html = CourseManager::displaySpecialCourses($user_id, $filter, $loadDirs, false, $start, $this->maxPerPage);
        if (!empty($html)) {
            $adapter = new FixedAdapter($nbResults, []);
            $pagerfanta = new Pagerfanta($adapter);
            $pagerfanta->setMaxPerPage($this->maxPerPage); // 10 by default
            $pagerfanta->setCurrentPage($page); // 1 by default
            $this->app['pagerfanta.view.router.name'] = 'userportal';
            $this->app['pagerfanta.view.router.params'] = [
                'filter' => $filter,
                'type' => 'courses',
                'page' => $page,
            ];
            $this->app['template']->assign('pagination', $pagerfanta);
        }

        return $html;
    }

    /**
     * The most important function here, prints the session and course list (user_portal.php).
     *
     * @param int    $user_id
     * @param string $filter
     * @param int    $page
     *
     * @return string HTML list of sessions and courses
     */
    public function returnCourses($user_id, $filter, $page)
    {
        if (empty($user_id)) {
            return false;
        }

        $loadDirs = api_get_setting('document.show_documents_preview') == 'true' ? true : false;
        $start = ($page - 1) * $this->maxPerPage;

        return;
        $nbResults = CourseManager::displayCourses(
            $user_id,
            $filter,
            $loadDirs,
            true
        );

        $html = CourseManager::displayCourses(
            $user_id,
            $filter,
            $loadDirs,
            false,
            $start,
            $this->maxPerPage
        );

        if (!empty($html)) {
            $adapter = new FixedAdapter($nbResults, []);
            $pagerfanta = new Pagerfanta($adapter);
            $pagerfanta->setMaxPerPage($this->maxPerPage); // 10 by default
            $pagerfanta->setCurrentPage($page); // 1 by default

            /*
            Original pagination construction
            $view = new TwitterBootstrapView();
            $routeGenerator = function($page) use ($app, $filter) {
                return $app['url_generator']->generate('userportal', array(
                    'filter' => $filter,
                    'type' => 'courses',
                    'page' => $page)
                );
            };
            $pagination = $view->render($pagerfanta, $routeGenerator, array(
                'proximity' => 3,
            ));
            */
            //Pagination using the pagerfanta silex service provider
            /*$this->app['pagerfanta.view.router.name']   = 'userportal';
            $this->app['pagerfanta.view.router.params'] = array(
                'filter' => $filter,
                'type'   => 'courses',
                'page'   => $page
            );
            $this->app['template']->assign('pagination', $pagerfanta);*/
            // {{ pagerfanta(my_pager, 'twitter_bootstrap3') }}
        }

        return $html;
    }

    public function returnSessionsCategories($user_id, $filter, $page)
    {
        if (empty($user_id)) {
            return false;
        }

        $load_history = (isset($filter) && $filter == 'history') ? true : false;

        $start = ($page - 1) * $this->maxPerPage;

        $nbResults = UserManager::getCategories($user_id, false, true, true);
        $session_categories = UserManager::getCategories(
            $user_id,
            false,
            false,
            true,
            $start,
            $this->maxPerPage
        );

        $html = null;
        //Showing history title
        if ($load_history) {
            $html .= Display::page_subheader(get_lang('HistoryTrainingSession'));
            if (empty($session_categories)) {
                $html .= get_lang('YouDoNotHaveAnySessionInItsHistory');
            }
        }

        $load_directories_preview = api_get_setting('document.show_documents_preview') == 'true' ? true : false;
        $sessions_with_category = $html;

        if (isset($session_categories) && !empty($session_categories)) {
            foreach ($session_categories as $session_category) {
                $session_category_id = $session_category['session_category']['id'];

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
                        if (api_get_setting('session.hide_courses_in_sessions') == 'false') {
                            $html_courses_session .= CourseManager::get_logged_user_course_html($course, $session_id);
                        }
                        $count_courses_session++;
                        $count++;
                    }

                    $params = [];
                    if ($count > 0) {
                        $params['icon'] = Display::return_icon(
                            'window_list.png',
                            $session['session_name'],
                            ['id' => 'session_img_'.$session_id],
                            ICON_SIZE_LARGE
                        );

                        //Default session name
                        $session_link = $session['session_name'];
                        $params['link'] = null;

                        if (api_get_setting('session.session_page_enabled') == 'true' && !api_is_drh()) {
                            //session name with link
                            $session_link = Display::tag(
                                'a',
                                $session['session_name'],
                                ['href' => api_get_path(WEB_CODE_PATH).'session/index.php?session_id='.$session_id]
                            );
                            $params['link'] = api_get_path(WEB_CODE_PATH).'session/index.php?session_id='.$session_id;
                        }

                        $params['title'] = $session_link;

                        $moved_status = \SessionManager::getSessionChangeUserReason(
                            isset($session['moved_status']) ? $session['moved_status'] : ''
                        );
                        $moved_status = isset($moved_status) && !empty($moved_status) ? ' ('.$moved_status.')' : null;

                        $params['subtitle'] = isset($session['coach_info']) ? $session['coach_info']['complete_name'] : null.$moved_status;
                        $params['dates'] = \SessionManager::parseSessionDates(
                            $session
                        );

                        if (api_is_platform_admin()) {
                            $params['right_actions'] = '<a href="'.api_get_path(WEB_CODE_PATH).'session/resume_session.php?id_session='.$session_id.'">'.Display::return_icon(
                                'edit.png',
                                get_lang('Edit'),
                                ['align' => 'absmiddle'],
                                ICON_SIZE_SMALL
                            ).'</a>';
                        }
                        $html_sessions .= CourseManager::course_item_html($params, true).$html_courses_session;
                    }
                }

                if ($count_courses_session > 0) {
                    $params = [];
                    $params['icon'] = Display::return_icon(
                        'folder_blue.png',
                        $session_category['session_category']['name'],
                        [],
                        ICON_SIZE_LARGE
                    );

                    if (api_is_platform_admin()) {
                        $params['right_actions'] = '<a href="'.api_get_path(
                            WEB_CODE_PATH
                        ).'admin/session_category_edit.php?&id='.$session_category['session_category']['id'].'">'.Display::return_icon(
                            'edit.png',
                            get_lang('Edit'),
                            [],
                            ICON_SIZE_SMALL
                        ).'</a>';
                    }

                    $params['title'] = $session_category['session_category']['name'];

                    if (api_is_platform_admin()) {
                        $params['link'] = api_get_path(
                            WEB_CODE_PATH
                        ).'admin/session_category_edit.php?&id='.$session_category['session_category']['id'];
                    }

                    $session_category_start_date = $session_category['session_category']['date_start'];
                    $session_category_end_date = $session_category['session_category']['date_end'];

                    if (!empty($session_category_start_date) && $session_category_start_date != '0000-00-00' && !empty($session_category_end_date) && $session_category_end_date != '0000-00-00') {
                        $params['subtitle'] = sprintf(
                            get_lang('FromDateXToDateY'),
                            $session_category['session_category']['date_start'],
                            $session_category['session_category']['date_end']
                        );
                    } else {
                        if (!empty($session_category_start_date) && $session_category_start_date != '0000-00-00') {
                            $params['subtitle'] = get_lang('From').' '.$session_category_start_date;
                        }
                        if (!empty($session_category_end_date) && $session_category_end_date != '0000-00-00') {
                            $params['subtitle'] = get_lang('Until').' '.$session_category_end_date;
                        }
                    }
                    $sessions_with_category .= CourseManager::course_item_parent(
                        CourseManager::course_item_html($params, true),
                        $html_sessions
                    );
                }
            }

            //Pagination
            $adapter = new FixedAdapter($nbResults, []);
            $pagerfanta = new Pagerfanta($adapter);
            $pagerfanta->setMaxPerPage($this->maxPerPage); // 10 by default
            $pagerfanta->setCurrentPage($page); // 1 by default

            $this->app['pagerfanta.view.router.name'] = 'userportal';
            $this->app['pagerfanta.view.router.params'] = [
                'filter' => $filter,
                'type' => 'sessioncategories',
                'page' => $page,
            ];
            $this->app['template']->assign('pagination', $pagerfanta);
        }

        return $sessions_with_category;
    }

    /**
     * @param int    $user_id
     * @param string $filter  current|history
     * @param int    $page
     *
     * @return bool|string|null
     */
    public function returnSessions($user_id, $filter, $page)
    {
        if (empty($user_id)) {
            return false;
        }

        $loadHistory = (isset($filter) && $filter == 'history') ? true : false;

        /*$app['session_menu'] = function ($app) use ($loadHistory) {
            $menu = $app['knp_menu.factory']->createItem(
                'root',
                array(
                    'childrenAttributes' => array(
                        'class'        => 'nav nav-tabs',
                        'currentClass' => 'active'
                    )
                )
            );

            $current = $menu->addChild(
                get_lang('Current'),
                array(
                    'route'           => 'userportal',
                    'routeParameters' => array(
                        'filter' => 'current',
                        'type'   => 'sessions'
                    )
                )
            );
            $history = $menu->addChild(
                get_lang('HistoryTrainingSession'),
                array(
                    'route'           => 'userportal',
                    'routeParameters' => array(
                        'filter' => 'history',
                        'type'   => 'sessions'
                    )
                )
            );
            //@todo use URIVoter
            if ($loadHistory) {
                $history->setCurrent(true);
            } else {
                $current->setCurrent(true);
            }

            return $menu;
        };*/

        //@todo move this in template
        //$app['knp_menu.menus'] = array('actions_menu' => 'session_menu');

        $start = ($page - 1) * $this->maxPerPage;

        if ($loadHistory) {
            // Load sessions in category in *history*.
            $nbResults = (int) UserManager::get_sessions_by_category(
                $user_id,
                true,
                true,
                true,
                null,
                null,
                'no_category'
            );

            $session_categories = UserManager::get_sessions_by_category(
                $user_id,
                true,
                false,
                true,
                $start,
                $this->maxPerPage,
                'no_category'
            );
        } else {
            // Load sessions in category.
            $nbResults = (int) UserManager::get_sessions_by_category(
                $user_id,
                false,
                true,
                false,
                null,
                null,
                'no_category'
            );

            $session_categories = UserManager::get_sessions_by_category(
                $user_id,
                false,
                false,
                false,
                $start,
                $this->maxPerPage,
                'no_category'
            );
        }

        $html = null;

        // Showing history title
        if ($loadHistory) {
            // $html .= Display::page_subheader(get_lang('HistoryTrainingSession'));
            if (empty($session_categories)) {
                $html .= get_lang('YouDoNotHaveAnySessionInItsHistory');
            }
        }

        $load_directories_preview = api_get_setting('document.show_documents_preview') === 'true' ? true : false;
        $sessions_with_no_category = $html;

        if (isset($session_categories) && !empty($session_categories)) {
            foreach ($session_categories as $session_category) {
                $session_category_id = $session_category['session_category']['id'];

                // Sessions does not belong to a session category
                if ($session_category_id == 0) {
                    // Independent sessions
                    if (isset($session_category['sessions'])) {
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
                            $count_courses_session = 0;

                            // Loop course content
                            $html_courses_session = [];
                            $atLeastOneCourseIsVisible = false;

                            foreach ($session['courses'] as $course) {
                                $is_coach_course = api_is_coach($session_id, $course['real_id']);
                                $allowed_time = 0;

                                // Read only and accessible
                                if (api_get_setting('session.hide_courses_in_sessions') == 'false') {
                                    $courseUserHtml = CourseManager::get_logged_user_course_html(
                                        $course,
                                        $session_id,
                                        $load_directories_preview
                                    );

                                    if (isset($courseUserHtml[1])) {
                                        $course_session = $courseUserHtml[1];
                                        $course_session['skill'] = isset($courseUserHtml['skill']) ? $courseUserHtml['skill'] : '';
                                        $html_courses_session[] = $course_session;
                                    }
                                }
                                $count_courses_session++;
                            }

                            if ($count_courses_session > 0) {
                                $params = [];

                                $params['icon'] = Display::return_icon(
                                    'window_list.png',
                                    $session['session_name'],
                                    ['id' => 'session_img_'.$session_id],
                                    ICON_SIZE_LARGE
                                );
                                $params['is_session'] = true;
                                //Default session name
                                $session_link = $session['session_name'];
                                $params['link'] = null;

                                if (api_get_setting('session.session_page_enabled') == 'true' && !api_is_drh()) {
                                    //session name with link
                                    $session_link = Display::tag(
                                        'a',
                                        $session['session_name'],
                                        [
                                            'href' => api_get_path(WEB_CODE_PATH).'session/index.php?session_id='.$session_id,
                                        ]
                                    );
                                    $params['link'] = api_get_path(WEB_CODE_PATH).'session/index.php?session_id='.$session_id;
                                }

                                $params['title'] = $session_link;

                                $moved_status = \SessionManager::getSessionChangeUserReason(
                                    $session['moved_status'] ?? ''
                                );
                                $moved_status = isset($moved_status) && !empty($moved_status) ? ' ('.$moved_status.')' : null;

                                $params['subtitle'] = isset($session['coach_info']) ? $session['coach_info']['complete_name'] : null.$moved_status;
                                //$params['dates'] = $session['date_message'];

                                $params['dates'] = \SessionManager::parseSessionDates($session);
                                $params['right_actions'] = '';
                                if (api_is_platform_admin()) {
                                    $params['right_actions'] .=
                                        Display::url(
                                            Display::return_icon(
                                                'edit.png',
                                                get_lang('Edit'),
                                                ['align' => 'absmiddle'],
                                                ICON_SIZE_SMALL
                                            ),
                                            api_get_path(WEB_CODE_PATH).'session/resume_session.php?id_session='.$session_id
                                        );
                                }

                                if (api_get_setting('session.hide_courses_in_sessions') == 'false') {
                                    //    $params['extra'] .=  $html_courses_session;
                                }
                                $courseDataToString = CourseManager::parseCourseListData($html_courses_session);
                                $sessions_with_no_category .= CourseManager::course_item_parent(
                                    CourseManager::course_item_html($params, true),
                                    $courseDataToString
                                );
                            }
                        }
                    }
                }
            }

            /*$adapter = new FixedAdapter($nbResults, array());
            $pagerfanta = new Pagerfanta($adapter);
            $pagerfanta->setMaxPerPage($this->maxPerPage); // 10 by default
            $pagerfanta->setCurrentPage($page); // 1 by default

            $this->app['pagerfanta.view.router.name']   = 'userportal';
            $this->app['pagerfanta.view.router.params'] = array(
                'filter' => $filter,
                'type'   => 'sessions',
                'page'   => $page
            );
            $this->app['template']->assign('pagination', $pagerfanta);*/
        }

        return $sessions_with_no_category;
    }

    /**
     * Shows a welcome message when the user doesn't have any content in
     * the course list.
     *
     * @param object A Template object used to declare variables usable in the given template
     */
    public function return_welcome_to_course_block($tpl)
    {
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

    /**
     * @param array
     */
    public function returnNavigationLinks($items)
    {
        // Main navigation section.
        // Tabs that are deactivated are added here.
        if (!empty($items)) {
            $content = '<ul class="nav nav-list">';
            foreach ($items as $section => $navigation_info) {
                $current = isset($GLOBALS['this_section']) && $section == $GLOBALS['this_section'] ? ' id="current"' : '';
                $content .= '<li '.$current.'>';
                $content .= '<a href="'.$navigation_info['url'].'" target="_self">'.$navigation_info['title'].'</a>';
                $content .= '</li>';
            }
            $content .= '</ul>';
            $this->show_right_block(get_lang('MainNavigation'), null, 'navigation_block', ['content' => $content]);
        }
    }
}
