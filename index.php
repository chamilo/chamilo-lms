<?php
/* For licensing terms, see /license.txt */

/**
 * @package chamilo.main
 */
define('CHAMILO_HOMEPAGE', true);

$language_file = array('courses', 'index');

// Flag forcing the 'current course' reset, as we're not inside a course anymore.
// Maybe we should change this into an api function? an example: CourseManager::unset();
$cidReset = true;

require_once 'main/inc/global.inc.php';
require_once 'main/chat/chat_functions.lib.php';

// The section (for the tabs).
$this_section = SECTION_CAMPUS;

$htmlHeadXtra[] = api_get_jquery_libraries_js(array('bxslider'));
$htmlHeadXtra[] = '
<script>
	$(document).ready(function(){
		$("#slider").bxSlider({
			infiniteLoop	: true,
			auto			: true,
			pager			: true,
			autoHover		: true,
		pause			: 10000
		});
	});
</script>';

//set cookie for check if client browser are cookies enabled
//setcookie('TestCookie', 'cookies_yes', time()+3600*24*31*12);
//use Symfony\Component\HttpFoundation\Cookie;
//$cookie = new Cookie('TestCookie', 'cookies_yes', time()+3600*24*31*12);
//$response->headers->setCookie($cookie);

use Silex\Application;
use Symfony\Component\HttpFoundation\Response;

/**
 *
 */
class IndexController
{
    /**
     * @param Silex\Application $app
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function indexAction(Application $app)
    {
        $request = $app['request'];

        //Actions
        $logout = $request->get('logout');

        if (!empty($logout)) {
            $this->logout();
        }

        //$article = $app['orm.em']->getRepository('Entity\EntityCourse');
        //$courses_query = $app['orm.em']->createQuery('SELECT a FROM Entity\EntityCourse a');
        //$a = new EntityCourse();
        //$article = $app['orm.em']->getRepository('EntityCourse');
        //var_dump($article);
        //$courses_query = $app['orm.em']->createQuery('SELECT a FROM Entity\EntityCourse a');
        /*
          $paginator = new Doctrine\ORM\Tools\Pagination\Paginator($courses_query, $fetchJoinCollection = true);
          $c = count($paginator);
          foreach ($paginator as $course) {
          echo $course->getCode() . "\n";
          }
          exit; */

        //$app['orm.em']->find('EntityCourse', 1);
        //var_dump($app['orm.ems']['mysql']);
        // Defines wether or not anonymous visitors can see a list of the courses on the Chamilo homepage that are open to the world.
        //$_setting['display_courses_to_anonymous_users'] = 'true';
        // Delete session neccesary for legal terms
        if (api_get_setting('allow_terms_conditions') == 'true') {
            unset($_SESSION['term_and_condition']);
        }

        //If we are not logged in and customapages activated
        if (!api_get_user_id() && CustomPages::enabled()) {
            $logged_out = $request->get('loggedout');
            if ($logged_out) {
                CustomPages::display(CustomPages::LOGGED_OUT);
            } else {
                CustomPages::display(CustomPages::INDEX_UNLOGGED);
            }
        }

        //$this->check_last_login();

        if (api_get_setting('display_categories_on_homepage') == 'true') {
            $app['template']->assign('course_category_block', PageController::return_courses_in_categories());
        }

        // Facebook connexion, if activated
        if (api_is_facebook_auth_activated() && !api_get_user_id()) {
            facebook_connect();
        }

        $this->set_login_form($app);

        if (!api_is_anonymous()) {
            PageController::return_profile_block();

            PageController::return_user_image_block();

            if (api_is_platform_admin()) {
                PageController::return_course_block();
            } else {
                PageController::return_teacher_link();
            }
        }

        //Hot courses & announcements
        $hot_courses         = null;
        $announcements_block = null;

        // When loading a chamilo page do not include the hot courses and news
        if (!isset($_REQUEST['include'])) {
            if (api_get_setting('show_hot_courses') == 'true') {
                $hot_courses = PageController::return_hot_courses();
            }
            $announcements_block = PageController::return_announcements();
        }

        $app['template']->assign('hot_courses', $hot_courses);
        $app['template']->assign('announcements_block', $announcements_block);

        //Homepage
        $app['template']->assign('home_page_block', PageController::return_home_page());

        //Navigation links
        $nav_links = $app['template']->return_navigation_links();

        $app['template']->assign('navigation_course_links', $nav_links);
        $app['template']->assign('main_navigation_block', $nav_links);

        PageController::return_notice();
        PageController::return_help();

        if (api_is_platform_admin() || api_is_drh()) {
            PageController::return_skills_links();
        }
        $response = $app['template']->render_layout('layout_2_col.tpl');

        //return new Response($response, 200, array('Cache-Control' => 's-maxage=3600, public'));
        return new Response($response, 200, array());
    }

    /**
     *
     * @todo This piece of code should probably move to local.inc.php where the actual login procedure is handled.
     * @todo Check if this code is used. I think this code is never executed because after clicking the submit button
     *       the code does the stuff in local.inc.php and then redirects to index.php or user_portal.php depending
     *       on api_get_setting('page_after_login').
     * @deprecated seems not to be used
     */
    function check_last_login()
    {
        if (!empty($_POST['submitAuth'])) {
            // The user has been already authenticated, we are now to find the last login of the user.
            if (!empty($this->user_id)) {
                $track_login_table = Database :: get_statistic_table(TABLE_STATISTIC_TRACK_E_LOGIN);
                $sql_last_login    = "SELECT login_date
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
        } else {
            // Only if login form was not sent because if the form is sent the user was already on the page.
            event_open();
        }
    }

    /**
     * @param Silex\Application $app
     */
    function set_login_form(Application $app)
    {
        $user_id    = api_get_user_id();
        $login_form = null;
        if (!$user_id || api_is_anonymous($user_id)) {

            // Only display if the user isn't logged in.
            $app['template']->assign('login_language_form', api_display_language_form(true));
            //self::display_login_form($app);

            $app['template']->assign('login_form', self::display_login_form($app));

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
            $app['template']->assign('login_options', $login_form);
        }
    }

    function logout()
    {
        $user_id = api_get_user_id();
        online_logout($user_id, true);
    }

    /**
     * @param Silex\Application $app
     * @return string
     */
    function display_login_form(Application $app)
    {
        /* {{ form_widget(form) }}
          $form = $app['form.factory']->createBuilder('form')
          ->add('name')
          ->add('email')
          ->add('gender', 'choice', array(
          'choices' => array(1 => 'male', 2 => 'female'),
          'expanded' => true,
          ))
          ->getForm();
          return $app['template']->assign('form', $form->createView());
         */

        $form = new FormValidator('formLogin', 'POST', null, null, array('class' => 'form-vertical'));
        $form->addElement(
            'text',
            'login',
            get_lang('UserName'),
            array('class' => 'span2 autocapitalize_off', 'autofocus' => 'autofocus')
        );
        $form->addElement('password', 'password', get_lang('Pass'), array('class' => 'span2'));
        $form->addElement('style_submit_button', 'submitAuth', get_lang('LoginEnter'), array('class' => 'btn'));
        $html = $form->return_form();
        if (api_get_setting('openid_authentication') == 'true') {
            include_once 'main/auth/openid/login.php';
            $html .= '<div>'.openid_form().'</div>';
        }
        return $html;
    }
}

$app->match('/', 'IndexController::indexAction', 'POST|GET');
$app->run();
//$app['http_cache']->run();