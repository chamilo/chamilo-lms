<?php
namespace ChamiloLMS\Controller;

use Silex\Application;
use \ChamiloSession as Session;
use Symfony\Component\HttpFoundation\Response;

class UserPortalController
{

    function indexAction(Application $app)
    {
        //@todo use filters like after/before to manage user access
        api_block_anonymous_users();

        //User is not allowed
        if ($app['allowed'] == false) {
            return $app->abort(403);
        }

        // Check if a user is enrolled only in one course for going directly to the course after the login.
        if (api_get_setting('go_to_course_after_login') == 'true') {

            // Get the courses list
            $personal_course_list = UserManager::get_personal_session_course_list(api_get_user_id());

            $my_session_list = array();
            $count_of_courses_no_sessions = 0;
            $count_of_courses_with_sessions = 0;

            foreach ($personal_course_list as $course) {
                if (!empty($course['id_session'])) {
                    $my_session_list[$course['id_session']] = true;
                    $count_of_courses_with_sessions++;
                } else {
                    $count_of_courses_no_sessions++;
                }
            }
            $count_of_sessions = count($my_session_list);

            if ($count_of_sessions == 1 && $count_of_courses_no_sessions == 0) {

                $key = array_keys($personal_course_list);
                $course_info = $personal_course_list[$key[0]];
                $course_directory = $course_info['course_info']['path'];
                $id_session = isset($course_info['id_session']) ? $course_info['id_session'] : 0;

                $url = api_get_path(WEB_CODE_PATH).'session/?session_id='.$id_session;

                header('location:'.$url);
                exit;
            }

            if (!isset($_SESSION['coursesAlreadyVisited']) && $count_of_sessions == 0 && $count_of_courses_no_sessions == 1) {
                $key = array_keys($personal_course_list);
                $course_info = $personal_course_list[$key[0]];
                $course_directory = $course_info['course_info']['path'];
                $id_session = isset($course_info['id_session']) ? $course_info['id_session'] : 0;

                $url = api_get_path(WEB_COURSE_PATH).$course_directory.'/?id_session='.$id_session;
                header('location:'.$url);
                exit;
            }
        }

        /* Sniffing system */
        /*
          //store posts to sessions
          if ($_SESSION['sniff_navigator']!="checked") {
          $_SESSION['sniff_navigator']=Security::remove_XSS($_POST['sniff_navigator']);
          $_SESSION['sniff_screen_size_w']=Security::remove_XSS($_POST['sniff_navigator_screen_size_w']);
          $_SESSION['sniff__screen_size_h']=Security::remove_XSS($_POST['sniff_navigator_screen_size_h']);
          $_SESSION['sniff_type_mimetypes']=Security::remove_XSS($_POST['sniff_navigator_type_mimetypes']);
          $_SESSION['sniff_suffixes_mimetypes']=Security::remove_XSS($_POST['sniff_navigator_suffixes_mimetypes']);
          $_SESSION['sniff_list_plugins']=Security::remove_XSS($_POST['sniff_navigator_list_plugins']);
          $_SESSION['sniff_check_some_activex']=Security::remove_XSS($_POST['sniff_navigator_check_some_activex']);
          $_SESSION['sniff_check_some_plugins']=Security::remove_XSS($_POST['sniff_navigator_check_some_plugins']);
          $_SESSION['sniff_java']=Security::remove_XSS($_POST['sniff_navigator_java']);
          $_SESSION['sniff_java_sun_ver']=Security::remove_XSS($_POST['sniff_navigator_java_sun_ver']);
          } */

        // Main courses and session list
        $courses_and_sessions = \PageController::return_courses_and_sessions(api_get_user_id());

        //Show the chamilo mascot
        if (empty($courses_and_sessions) && !isset($_GET['history'])) {
            \PageController::return_welcome_to_course_block($app['template']);
        }

        $app['template']->assign('content', $courses_and_sessions);

        /*
          if (api_get_setting('allow_browser_sniffer') == 'true') {
          if ($_SESSION['sniff_navigator']!="checked") {
          $app['template']->assign('show_sniff', 	1);
          } else {
          $app['template']->assign('show_sniff', 	0);
          }
          }

          //check for flash and message

          $sniff_notification = '';
          $some_activex=$_SESSION['sniff_check_some_activex'];
          $some_plugins=$_SESSION['sniff_check_some_plugins'];

          if(!empty($some_activex) || !empty($some_plugins)){
          if (! preg_match("/flash_yes/", $some_activex) && ! preg_match("/flash_yes/", $some_plugins)) {
          $sniff_notification = Display::return_message(get_lang('NoFlash'), 'warning', true);
          //js verification - To annoying of redirecting every time the page
          $app['template']->assign('sniff_notification',  $sniff_notification);
          }
          } */

        \PageController::return_profile_block();
        \PageController::return_user_image_block();
        \PageController::return_course_block();

        $app['template']->assign('navigation_course_links', $app['template']->returnNavigationLinks());
        \PageController::return_reservation_block();
        $app['template']->assign('search_block', \PageController::return_search_block());
        $app['template']->assign('classes_block', \PageController::return_classes_block());
        \PageController::return_skills_links();

        // Deleting the session_id.
        Session::erase('session_id');

        $response = $app['template']->render_template('userportal/index.tpl');

        //return new Response($response, 200, array('Cache-Control' => 's-maxage=3600, private'));
        return new Response($response, 200, array());
    }

    function check_last_login()
    {
        /**
         * @todo This piece of code should probably move to local.inc.php where the actual login procedure is handled.
         * @todo Check if this code is used. I think this code is never executed because after clicking the submit button
         *       the code does the stuff in local.inc.php and then redirects to index.php or user_portal.php depending
         *       on api_get_setting('page_after_login').
         */
        if (!empty($_POST['submitAuth'])) {
            // The user has been already authenticated, we are now to find the last login of the user.
            if (!empty($this->user_id)) {
                $track_login_table = Database :: get_statistic_table(TABLE_STATISTIC_TRACK_E_LOGIN);
                $sql_last_login = "SELECT login_date
                                    FROM $track_login_table
                                    WHERE login_user_id = '".$this->user_id."'
                                    ORDER BY login_date DESC LIMIT 1";
                $result_last_login = Database::query($sql_last_login);
                if (!$result_last_login) {
                    if (Database::num_rows($result_last_login) > 0) {
                        $user_last_login_datetime = Database::fetch_array($result_last_login);
                        $user_last_login_datetime = $user_last_login_datetime[0];
                        Session::write('user_last_login_datetime', $user_last_login_datetime);
                    }
                }
                Database::free_result($result_last_login);

                if (api_is_platform_admin()) {
                    // decode all open event informations and fill the track_c_* tables
                    include api_get_path(LIBRARY_PATH).'stats.lib.inc.php';
                    decodeOpenInfos();
                }
            }
            // End login -- if ($_POST['submitAuth'])
        } else {
            // Only if login form was not sent because if the form is sent the user was already on the page.
            event_open();
        }
    }

    function set_login_form()
    {
        global $loginFailed;

        $login_form = '';

        if (!($this->user_id) || api_is_anonymous($this->user_id)) {

            // Only display if the user isn't logged in.
            $this->page->assign('login_language_form', api_display_language_form(true));
            $this->page->assign('login_form', self::display_login_form());

            if ($loginFailed) {
                $this->page->assign('login_failed', self::handle_login_failed());
            }

            if (api_get_setting('allow_lostpassword') == 'true' || api_get_setting('allow_registration') == 'true') {
                $login_form .= '<ul class="nav nav-list">';
                if (api_get_setting('allow_registration') != 'false') {
                    $login_form .= '<li><a href="main/auth/inscription.php">'.get_lang('Reg').'</a></li>';
                }
                if (api_get_setting('allow_lostpassword') == 'true') {
                    $login_form .= '<li><a href="main/auth/lostPassword.php">'.get_lang('LostPassword').'</a></li>';
                }
                $login_form .= '</ul>';
            }
            $this->page->assign('login_options', $login_form);
        }
    }

    /**
     * Alias for the online_logout() function
     */
    function logout()
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

    /**
     * Reacts on a failed login:
     * Displays an explanation with a link to the registration form.
     *
     * @version 1.0.1
     */
    function handle_login_failed()
    {
        $message = get_lang('InvalidId');

        if (!isset($_GET['error'])) {
            if (api_is_self_registration_allowed()) {
                $message = get_lang('InvalidForSelfRegistration');
            }
        } else {
            switch ($_GET['error']) {
                case '':
                    if (api_is_self_registration_allowed()) {
                        $message = get_lang('InvalidForSelfRegistration');
                    }
                    break;
                case 'account_expired':
                    $message = get_lang('AccountExpired');
                    break;
                case 'account_inactive':
                    $message = get_lang('AccountInactive');
                    break;
                case 'user_password_incorrect':
                    $message = get_lang('InvalidId');
                    break;
                case 'access_url_inactive':
                    $message = get_lang('AccountURLInactive');
                    break;
                case 'unrecognize_sso_origin':
                    //$message = get_lang('SSOError');
                    break;
            }
        }
        return Display::return_message($message, 'error');
    }

    /**
     * retrieves all the courses that the user has already subscribed to
     * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University, Belgium
     * @param int $user_id: the id of the user
     * @return array an array containing all the information of the courses of the given user
     */
    function get_courses_of_user($user_id)
    {
        $table_course = Database::get_main_table(TABLE_MAIN_COURSE);
        $table_course_user = Database::get_main_table(TABLE_MAIN_COURSE_USER);
        // Secondly we select the courses that are in a category (user_course_cat <> 0) and sort these according to the sort of the category
        $user_id = intval($user_id);
        $sql_select_courses = "SELECT course.code k, course.visual_code  vc, course.subscribe subscr, course.unsubscribe unsubscr,
    		course.title i, course.tutor_name t, course.db_name db, course.directory dir, course_rel_user.status status,
    		course_rel_user.sort sort, course_rel_user.user_course_cat user_course_cat
    		FROM    $table_course       course,
    		$table_course_user  course_rel_user
    		WHERE course.code = course_rel_user.course_code
    		AND   course_rel_user.user_id = '".$user_id."'
                                    AND course_rel_user.relation_type<>".COURSE_RELATION_TYPE_RRHH."
                                    ORDER BY course_rel_user.sort ASC";
        $result = Database::query($sql_select_courses);
        $courses = array();
        while ($row = Database::fetch_array($result)) {
            // We only need the database name of the course.
            $courses[$row['k']] = array(
                'db' => $row['db'],
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
}