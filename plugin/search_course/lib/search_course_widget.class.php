<?php

require_once dirname(__FILE__) . '/register_course_widget.class.php';

/**
 * Search course widget. 
 * Display a search form and a list of courses that matches the search.
 * 
 * @copyright (c) 2011 University of Geneva
 * @license GNU General Public License - http://www.gnu.org/copyleft/gpl.html
 * @author Laurent Opprecht
 */
class SearchCourseWidget
{

    const PARAM_ACTION = 'action';
    const ACTION_SUBSCRIBE = 'subscribe';

    /**
     * Returns $_POST data for $key is it exists or $default otherwise.
     * 
     * @param string $key
     * @param object $default
     * @return string 
     */
    public static function post($key, $default = '')
    {
        return isset($_POST[$key]) ? $_POST[$key] : $default;
    }

    /**
     * Returns $_GET data for $key is it exists or $default otherwise.
     * 
     * @param string $key
     * @param object $default
     * @return string 
     */
    public static function get($key, $default = '')
    {
        return isset($_GET[$key]) ? $_GET[$key] : $default;
    }

    public static function server($key, $default = '')
    {
        return isset($_SERVER[$key]) ? $_SERVER[$key] : $default;
    }

    public static function get_lang($name)
    {
        return SearchCoursePlugin::create()->get_lang($name);
    }

    /**
     *
     * @return bool
     */
    function is_homepage()
    {

        $url = self::server('REQUEST_URI');
        $url = explode('?', $url);
        $url = reset($url);
        $url = self::server('SERVER_NAME') . $url;

        $root = api_get_path('WEB_PATH');
        $root = str_replace('https://', '', $root);
        $root = str_replace('http://', '', $root);
        $index_url = $root . 'index.php';

        return $url == $index_url || $url == $root;
    }

    /**
     *
     * @return bool
     */
    function is_user_portal()
    {

        $url = self::server('REQUEST_URI');
        $url = explode('?', $url);
        $url = reset($url);
        $url = self::server('SERVER_NAME') . $url;

        $root = api_get_path('WEB_PATH');
        $root = str_replace('https://', '', $root);
        $root = str_replace('http://', '', $root);
        $index_url = $root . 'user_portal.php';

        return $url == $index_url || $url == $root;
    }

    /**
     * 
     */
    function accept()
    {
        return $this->is_homepage() || $this->is_user_portal();
    }

    /**
     * Display the search course widget:
     * 
     * Title
     * Search form
     * 
     * Search results
     */
    function run()
    {
        if (!$this->accept())
        {
            return;
        }
        $this->display_header();

        $this->display_form();

        $search_term = self::post('search_term');
        $action = self::get('action');

        $has_content = !empty($search_term) || !empty($action);
        if ($has_content)
        {
            echo '<div class="list">';
        }
        else
        {
            echo '<div>';
        }

        if (RegisterCourseWidget::factory()->run())
        {
            $result = true;
        }
        else
        {
            $result = $this->action_display();
        }

        echo '</div>';

        $this->display_footer();
        return $result;
    }

    function get_url($action = '')
    {
        $self = $_SERVER['PHP_SELF'];
        $parameters = array();
        if ($action)
        {
            $parameters[self::PARAM_ACTION] = $action;
        }
        $parameters = implode('&', $parameters);
        $parameters = $parameters ? '?' . $parameters : '';
        return $self . $parameters;
    }

    /**
     * Handle the display action
     */
    function action_display()
    {
        global $charset;

        $search_term = self::post('search_term');
        if ($search_term)
        {
            $search_result_for_label = self::get_lang('SearchResultsFor');
            $search_term_html = htmlentities($search_term, ENT_QUOTES, $charset);
            echo "<h5>$search_result_for_label $search_term_html</h5>";

            $courses = $this->retrieve_courses($search_term);
            $this->display_list($courses);
        }
        return true;
    }

    function display_header()
    {
        $search_course_label = self::get_lang('SearchCourse');
        echo <<<EOT
        <div class="well course_search">
        <div class="menusection">
            <h4>$search_course_label</h4>
EOT;
    }

    function display_footer()
    {
        echo '</div></div>';
    }

    /**
     * Display the search course form.
     */
    function display_form()
    {
        global $stok;

        $search_label = self::get_lang('_search');
        $self = api_get_self();
        $search_term = self::post('search_term');
        $form = <<<EOT
        <form class="course_list" method="post" action="$self">
            <input type="hidden" name="sec_token" value="$stok" />
            <input type="hidden" name="search_course" value="1" />
            <input type="text" name="search_term" class="span2" value="$search_term" />
            &nbsp;<input class="btn" type="submit" value="$search_label" />
        </form>
EOT;
        echo $form;
    }

    /**
     *
     * @param array $courses
     * @return bool 
     */
    function display_list($courses)
    {
        if (empty($courses))
        {
            return false;
        }

        $user_courses = $this->retrieve_user_courses();

        $display_coursecode = (get_setting('display_coursecode_in_courselist') == 'true');
        $display_teacher = (get_setting('display_teacher_in_courselist') == 'true');

        echo '<table cellpadding="4">';
        foreach ($courses as $key => $course)
        {
            $details = array();
            if ($display_coursecode)
            {
                $details[] = $course['visual_code'];
            }
            if ($display_teacher)
            {
                $details[] = $course['tutor'];
            }
            $details = implode(' - ', $details);
            $title = $course['title'];

            $href = api_get_path(WEB_PATH) . 'courses/' . $course['code'] .'/index.php';
            echo '<tr><td><b><a href="' . $href . '">' . "$title</a></b><br/>$details</td><td>";
            if (!api_is_anonymous())
            {
                if ($course['registration_code'])
                {
                    Display::display_icon('passwordprotected.png', '', array('style' => 'float:left;'));
                }
                $this->display_subscribe_icon($course, $user_courses);
            }
            echo '</td></tr>';
        }
        echo '</table>';
        return true;
    }

    /**
     * Displays the subscribe icon if subscribing is allowed and 
     * if the user is not yet subscribed to this course
     * 
     * @global type $stok
     * @param array $current_course
     * @param array $user_courses
     * @return bool 
     */
    function display_subscribe_icon($current_course, $user_courses)
    {
        global $stok;

        //Already subscribed
        $code = $current_course['code'];
        if (isset($user_courses[$code]))
        {
            echo self::get_lang('AlreadySubscribed');
            return false;
        }

        //Not authorized to subscribe
        if ($current_course['subscribe'] != SUBSCRIBE_ALLOWED)
        {
            echo self::get_lang('SubscribingNotAllowed');
            return false;
        }

        //Subscribe form     
        $self = $_SERVER['PHP_SELF'];
        echo <<<EOT
                <form action="$self?action=subscribe" method="post">
                    <input type="hidden" name="sec_token" value="$stok" />
                    <input type="hidden" name="subscribe" value="$code" />
EOT;

        $search_term = $this->post('search_term');
        if ($search_term)
        {
            $search_term = Security::remove_XSS($search_term);
            echo <<<EOT
                    <input type="hidden" name="search_course" value="1" />
                    <input type="hidden" name="search_term" value="$search_term" />
EOT;
        }

        $web_path = api_get_path(WEB_PATH);
        $subscribe_label = get_lang('Subscribe');
        echo <<<EOT
                    <input type="image" name="unsub" src="$web_path/main/img/enroll.gif" alt="$subscribe_label" />$subscribe_label
                </form>
EOT;
        return true;
    }

    /**
     * DB functions - DB functions - DB functions
     */

    /**
     * Search courses that match the search term.
     * Search is done on the code, title and tutor fields.
     * 
     * @param string $search_term 
     * @return array 
     */
    function retrieve_courses($search_term)
    {
        if (empty($search_term))
        {
            return array();
        }
        $search_term = Database::escape_string($search_term);
        $course_table = Database::get_main_table(TABLE_MAIN_COURSE);

        if (api_is_anonymous())
        {
            $course_fiter = 'visibility = ' . COURSE_VISIBILITY_OPEN_WORLD;
        }
        else
        {
            $course_fiter = 'visibility = ' . COURSE_VISIBILITY_OPEN_WORLD . ' OR ';
            $course_fiter .= 'visibility = ' . COURSE_VISIBILITY_OPEN_PLATFORM . ' OR ';
            $course_fiter .= '(visibility = ' . COURSE_VISIBILITY_REGISTERED . ' AND subscribe = 1)';
        }

        $sql = <<<EOT
                SELECT * FROM $course_table 
                WHERE ($course_fiter) AND (code LIKE '%$search_term%' OR visual_code LIKE '%$search_term%' OR title LIKE '%$search_term%' OR tutor_name LIKE '%$search_term%') 
                ORDER BY title, visual_code ASC      
EOT;

        $result = array();
        $resultset = api_sql_query($sql, __FILE__, __LINE__);
        while ($row = Database::fetch_array($resultset))
        {
            $code = $row['code'];
            $result[$code] = array(
                'code' => $code,
                'directory' => $row['directory'],
                'db' => $row['db_name'],
                'visual_code' => $row['visual_code'],
                'title' => $row['title'],
                'tutor' => $row['tutor_name'],
                'subscribe' => $row['subscribe'],
                'unsubscribe' => $row['unsubscribe']
            );
        }
        return $result;
    }

    /**
     * Retrieves courses that the user is subscribed to
     * 
     * @param int $user_id
     * @return array 
     */
    function retrieve_user_courses($user_id = null)
    {
        if (is_null($user_id))
        {
            global $_user;
            $user_id = $_user['user_id'];
        }
        $course_table = Database::get_main_table(TABLE_MAIN_COURSE);
        $user_course_table = Database::get_main_table(TABLE_MAIN_COURSE_USER);

        $user_id = intval($user_id);
        $sql_select_courses = "SELECT course.code k, course.visual_code  vc, course.subscribe subscr, course.unsubscribe unsubscr,
                                      course.title i, course.tutor_name t, course.db_name db, course.directory dir, course_rel_user.status status,
				      course_rel_user.sort sort, course_rel_user.user_course_cat user_course_cat
		               FROM $course_table course, $user_course_table course_rel_user
		               WHERE course.code = course_rel_user.course_code
		                     AND course_rel_user.user_id = $user_id
		               ORDER BY course_rel_user.sort ASC";
        $result = array();
        $resultset = api_sql_query($sql_select_courses, __FILE__, __LINE__);
        while ($row = Database::fetch_array($resultset))
        {
            $code = $row['k'];
            $result[$code] = array(
                'db' => $row['db'],
                'code' => $code,
                'visual_code' => $row['vc'],
                'title' => $row['i'],
                'directory' => $row['dir'],
                'status' => $row['status'],
                'tutor' => $row['t'],
                'subscribe' => $row['subscr'],
                'unsubscribe' => $row['unsubscr'],
                'sort' => $row['sort'],
                'user_course_category' => $row['user_course_cat']);
        }
        return $result;
    }

    /*
     * Utility functions - Utility functions - Utility functions 
     */

    /**
     * Removes from $courses all courses the user is subscribed to.
     * 
     * @global array $_user
     * @param array $courses
     * @return array 
     */
    function filter_out_user_courses($courses)
    {
        if (empty($courses))
        {
            return $courses;
        }

        global $_user;
        $user_id = $_user['user_id'];

        $user_courses = $this->retrieve_user_courses($user_id);
        foreach ($user_courses as $key => $value)
        {
            unset($courses[$key]);
        }
        return $courses;
    }

}
