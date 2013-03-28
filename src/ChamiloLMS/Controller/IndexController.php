<?php
/* For licensing terms, see /license.txt */

namespace ChamiloLMS\Controller;

use Silex\Application;
use Symfony\Component\HttpFoundation\Response;

/**
 *
 */
class IndexController// extends Controller
{
    public $section;
    public $language_files = array('courses', 'index', 'admin');

    /**
     *
     * @return bool
     */
    public function security()
    {
        return false;
        if (api_is_allowed_to_edit()) {

            return true;
        }
    }

    function logoutAction(Application $app)
    {
        $this->logout();

        return $app->redirect($app['url_generator']->generator('index'));
    }

    /**
     * @param \Silex\Application $app
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction(Application $app)
    {
        $extraJS = array();
        //@todo improve js includes
        $extraJS[] = api_get_jquery_libraries_js(array('bxslider'));
        $extraJS[] = '<script>
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

        $app['this_section'] = SECTION_CAMPUS;
        $app['extraJS'] = $extraJS;
        $request = $app['request'];
        $app['languages_file'] = array('courses', 'index', 'admin');
        $app['cidReset'] = true;

        //echo $app['translator']->trans('Wiki Search Results');
        //echo $app['translator']->trans('Profile');

        //$token = $app['security']->getToken();

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
        if (!api_get_user_id() && \CustomPages::enabled()) {
            $loggedOut = $request->get('loggedout');
            if ($loggedOut) {
                \CustomPages::display(\CustomPages::LOGGED_OUT);
            } else {
                \CustomPages::display(\CustomPages::INDEX_UNLOGGED);
            }
        }

        //$this->check_last_login();

        if (api_get_setting('display_categories_on_homepage') == 'true') {
            $app['template']->assign('course_category_block', \PageController::return_courses_in_categories());
        }

        // Facebook connexion, if activated
        if (api_is_facebook_auth_activated() && !api_get_user_id()) {
            facebook_connect();
        }

        //$app['url_generator']->generator('index');

        $this->setLoginForm($app);

        if (!api_is_anonymous()) {
            \PageController::return_profile_block();
            \PageController::return_user_image_block();

            if (api_is_platform_admin()) {
                \PageController::return_course_block();
            } else {
                \PageController::return_teacher_link();
            }
        }

        //Hot courses & announcements
        $hotCourses         = null;
        $announcementsBlock = null;

        // When loading a chamilo page do not include the hot courses and news
        if (!isset($_REQUEST['include'])) {
            if (api_get_setting('show_hot_courses') == 'true') {
                $hotCourses = \PageController::return_hot_courses();
            }
            $announcementsBlock = \PageController::return_announcements();
        }

        $app['template']->assign('hot_courses', $hotCourses);
        $app['template']->assign('announcements_block', $announcementsBlock);

        //Homepage
        $app['template']->assign('home_page_block', \PageController::return_home_page());

        //Navigation links
        $navLinks = $app['template']->returnNavigationLinks();

        $app['template']->assign('navigation_course_links', $navLinks);
        $app['template']->assign('main_navigation_block', $navLinks);

        \PageController::return_notice();
        \PageController::return_help();

        if (api_is_platform_admin() || api_is_drh()) {
            \PageController::return_skills_links();
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
     * @param \Silex\Application $app
     */
    function setLoginForm(Application $app)
    {
        $userId    = api_get_user_id();
        $loginForm = null;
        if (!$userId || api_is_anonymous($userId)) {

            // Only display if the user isn't logged in

            $app['template']->assign('login_language_form', api_display_language_form(true));
            $app['template']->assign('login_form', self::display_login_form($app));

            if (api_get_setting('allow_lostpassword') == 'true' || api_get_setting('allow_registration') == 'true') {
                $loginForm .= '<ul class="nav nav-list">';
                if (api_get_setting('allow_registration') != 'false') {
                    $loginForm .= '<li><a href="'.api_get_path(WEB_CODE_PATH).'auth/inscription.php">'.get_lang('Reg').'</a></li>';
                }
                if (api_get_setting('allow_lostpassword') == 'true') {
                    $loginForm .= '<li><a href="'.api_get_path(WEB_CODE_PATH).'auth/lostPassword.php">'.get_lang('LostPassword').'</a></li>';
                }
                $loginForm .= '</ul>';
            }
            $app['template']->assign('login_options', $loginForm);
        }
    }

    /**
     * Logout action
     */
    public function logout()
    {
        $userId = api_get_user_id();
        online_logout($userId, true);
    }

    /**
     * @param \Silex\Application $app
     *
     * @return string
     */
    public function display_login_form(Application $app)
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

        $form = new \FormValidator('formLogin', 'POST', null, null, array('class' => 'form-vertical'));
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