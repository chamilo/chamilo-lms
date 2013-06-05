<?php
/* For licensing terms, see /license.txt */

namespace ChamiloLMS\Controller;

use Silex\Application;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Finder\Finder;
/**
 * @package ChamiloLMS.Controller
 * @author Julio Montoya <gugli100@gmail.com>
 */
class IndexController extends CommonController
{
    public $section;
    public $languageFiles = array('courses', 'index', 'admin');

    /**
     * Logouts a user
     * @param Application $app
     */
    public function logoutAction(Application $app)
    {
        $userId = api_get_user_id();

        \Online::logout($userId, true);
        // the Online::logout function already does a redirect
        //return $app->redirect($app['url_generator']->generate('index'));
    }

    /**
     * @param \Silex\Application $app
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction(Application $app)
    {
        $this->cidReset();
        /** @var \Template $template */
        $template = $app['template'];

        /*
        $params['yolo'] = array(
            'subject' => 'subject julito',
            'content' => 'content julito',
            'user' => 'julito'
        );
        \MessageManager::sendMessageUsingTemplate('sample/sample.tpl', $params, 1);
        */

        $loginError = $app['request']->get('error');

        $extraJS = array();

        //@todo improve this JS includes should be added using twig
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

        // Testing translation using translator
        //echo $app['translator']->trans('Wiki Search Results');
        //echo $app['translator']->trans('Profile');

        //$token = $app['security']->getToken();

        //$article = $app['orm.em']->getRepository('Entity\Course');
        //$courses_query = $app['orm.em']->createQuery('SELECT a FROM Entity\Course a');
        //$a = new Course();
        //$article = $app['orm.em']->getRepository('Course');
        //var_dump($article);
        //$courses_query = $app['orm.em']->createQuery('SELECT a FROM Entity\Course a');
        /*
          $paginator = new Doctrine\ORM\Tools\Pagination\Paginator($courses_query, $fetchJoinCollection = true);
          $c = count($paginator);
          foreach ($paginator as $course) {
          echo $course->getCode() . "\n";
          }
          exit; */

        //$app['orm.em']->find('Course', 1);
        //var_dump($app['orm.ems']['mysql']);
        // Defines wether or not anonymous visitors can see a list of the courses on the Chamilo homepage that are open to the world.
        //$_setting['display_courses_to_anonymous_users'] = 'true';
        // Delete session neccesary for legal terms

        if (api_get_setting('allow_terms_conditions') == 'true') {
            unset($_SESSION['term_and_condition']);
        }

        // If we are not logged in and custompages activated
        if (!api_get_user_id() && \CustomPages::enabled()) {
            $loggedOut = $request->get('loggedout');
            if ($loggedOut) {
                \CustomPages::display(\CustomPages::LOGGED_OUT);
            } else {
                \CustomPages::display(\CustomPages::INDEX_UNLOGGED);
            }
        }

        if (api_get_setting('display_categories_on_homepage') == 'true') {
            $template->assign('course_category_block', $app['page_controller']->return_courses_in_categories());
        }

        // @todo Custom Facebook connection lib could be replaced with opauth
        // Facebook connection, if activated
        if (api_is_facebook_auth_activated() && !api_get_user_id()) {
            facebook_connect();
        }

        $this->setLoginForm($app);

        if (!api_is_anonymous()) {
            $app['page_controller']->return_profile_block();
            $app['page_controller']->return_user_image_block();

            if (api_is_platform_admin()) {
                $app['page_controller']->return_course_block();
            } else {
                $app['page_controller']->return_teacher_link();
            }
        }

        // Hot courses & announcements
        $hotCourses         = null;
        $announcementsBlock = null;

        // When loading a chamilo page do not include the hot courses and news
        if (!isset($_REQUEST['include'])) {
            if (api_get_setting('show_hot_courses') == 'true') {
                $hotCourses = $app['page_controller']->return_hot_courses();
            }
            $announcementsBlock = $app['page_controller']->return_announcements();
        }

        $template->assign('hot_courses', $hotCourses);
        $template->assign('announcements_block', $announcementsBlock);

        // Homepage
        $template->assign('home_page_block', $app['page_controller']->returnHomePage());

        // Navigation links
        $app['page_controller']->returnNavigationLinks($template->getNavigationLinks());
        $app['page_controller']->return_notice();
        $app['page_controller']->return_help();

        if (api_is_platform_admin() || api_is_drh()) {
            $app['page_controller']->return_skills_links();
        }

        if (!empty($loginError)) {
            $template->assign('login_failed', $this->handleLoginFailed($loginError));
        }

        $response = $template->render_layout('layout_2_col.tpl');

        //return new Response($response, 200, array('Cache-Control' => 's-maxage=3600, public'));
        return new Response($response, 200, array());
    }

    /**
     * @param Application $app
     * @return Response
     */
    public function loginAction(Application $app)
    {
        /*$username = $app['request']->get('login');
        $password = $app['request']->get('password');

        $user_table = \Database::get_main_table(TABLE_MAIN_USER);
        $sql = "SELECT * FROM $user_table WHERE username = ?";
        $userInfo = $app['db']->fetchAssoc($sql, array($username));

        if ($userInfo) {
            if ($userInfo['auth_source'] == PLATFORM_AUTH_SOURCE) {
                if ($password == $userInfo['password'] AND trim($username) == $userInfo['username']) {
                    unset($userInfo['password']);

                }
            }
        }*/
        $response = null;
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
                $track_login_table = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_LOGIN);
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
                    // Decode all open event informations and fill the track_c_* tables
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
    public function setLoginForm(Application $app)
    {
        $userId    = api_get_user_id();
        $loginForm = null;
        if (!$userId || api_is_anonymous($userId)) {

            // Only display if the user isn't logged in

            $app['template']->assign('login_language_form', api_display_language_form(true));
            $app['template']->assign('login_form', self::displayLoginForm($app));

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
     * @param \Silex\Application $app
     *
     * @return string
     */
    public function displayLoginForm(Application $app)
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

        $form = new \FormValidator('formLogin', 'POST', $app['url_generator']->generate('index'), null, array('class' => 'form-vertical'));
        $form->addElement(
            'text',
            'login',
            get_lang('UserName'),
            array('class' => 'input-medium autocapitalize_off', 'autofocus' => 'autofocus')
        );
        $form->addElement('password', 'password', get_lang('Pass'), array('class' => 'input-medium '));
        $form->addElement('style_submit_button', 'submitAuth', get_lang('LoginEnter'), array('class' => 'btn'));
        $html = $form->return_form();
        if (api_get_setting('openid_authentication') == 'true') {
            include_once 'main/auth/openid/login.php';
            $html .= '<div>'.openid_form().'</div>';
        }
        return $html;
    }

    /**
     * @todo move all this getDocument* Actions into another controller
     * @param Application $app
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse|void
     */
    public function getDocumentTemplateAction(Application $app)
    {
        try {
            $file = $app['request']->get('file');
            $file = $app['chamilo.filesystem']->get('document_templates/'.$file);
            return $app->sendFile($file->getPathname());
        } catch (\InvalidArgumentException $e) {
            return $app->abort(404, 'File not found');
        }
    }

    /**
     * Gets a document from the data/courses/MATHS/document/file.jpg to the user
     * @todo check permissions
     * @param Application $app
     * @param string $courseCode
     * @param string $file
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse|void
     */
    public function getDocumentAction(Application $app, $courseCode, $file)
    {
        try {
            $file = $app['chamilo.filesystem']->getCourseDocument($courseCode, $file);
            return $app->sendFile($file->getPathname());
        } catch (\InvalidArgumentException $e) {
            return $app->abort(404, 'File not found');
        }
    }

    /**
     * Gets a document from the data/courses/MATHS/scorm/file.jpg to the user
     * @todo check permissions
     * @param Application $app
     * @param string $courseCode
     * @param string $file
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse|void
     */
    public function getScormDocumentAction(Application $app, $courseCode, $file)
    {
        try {
            $file = $app['chamilo.filesystem']->getCourseScormDocument($courseCode, $file);
            return $app->sendFile($file->getPathname());
        } catch (\InvalidArgumentException $e) {
            return $app->abort(404, 'File not found');
        }
    }

    /**
     * Gets a document from the data/default_platform_document/* folder
     * @param Application $app
     * @param string $file
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse|void
     */
    public function getDefaultPlatformDocumentAction(Application $app, $file)
    {
        try {
            $file = $app['chamilo.filesystem']->get('default_platform_document/'.$file);
            return $app->sendFile($file->getPathname());
        } catch (\InvalidArgumentException $e) {
            return $app->abort(404, 'File not found');
        }
    }

    /**
     * @param Application $app
     * @param $groupId
     * @param $file
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse|void
     */
    public function getGroupFile(Application $app, $groupId, $file)
    {
        try {
            $file = $app['chamilo.filesystem']->get('upload/groups/'.$groupId.'/'.$file);
            return $app->sendFile($file->getPathname());
        } catch (\InvalidArgumentException $e) {
            return $app->abort(404, 'File not found');
        }
    }

    /**
     * @param Application $app
     * @param $file
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse|void
     */
    public function getUserFile(Application $app, $file)
    {
        try {
            $file = $app['chamilo.filesystem']->get('upload/users/'.$file);
            return $app->sendFile($file->getPathname());
        } catch (\InvalidArgumentException $e) {
            return $app->abort(404, 'File not found');
        }
    }


    /**
     * Reacts on a failed login.
     * Displays an explanation with a link to the registration form.
     *
     * @todo use twig template to prompt errors + move this into a helper
     */
    private function handleLoginFailed($error)
    {
        $message = get_lang('InvalidId');

        if (!isset($error)) {
            if (api_is_self_registration_allowed()) {
                $message = get_lang('InvalidForSelfRegistration');
            }
        } else {
            switch ($error) {
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
        return \Display::return_message($message, 'error');
    }
}
