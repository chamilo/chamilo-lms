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
    /**
     * @param \Silex\Application $app
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction(Application $app)
    {
        $template = $this->getTemplate();

        /*$user = $this->getManager()->getRepository('Entity\User')->find(1);
        foreach($user->getPortals() as $portal) {
            var_dump($portal->getUrl());
        }*/

        /*
        $token = $app['security']->getToken();
        if (null !== $token) {
            $user = $token->getUser();
        }*/

        /*\ChamiloSession::write('name', 'clara');
        var_dump(\ChamiloSession::read('name'));
        var_dump($_SESSION['name']);*/

        //var_dump(\ChamiloSession::read('aaa'));

        /*\ChamiloSession::write('name', 'clar');
        echo \ChamiloSession::read('name');
        $app['session']->set('name', 'julio');
        echo $app['session']->get('name');*/
        /*
        $token = $app['security']->getToken();
        if (null !== $token) {
            $user = $token->getUser();
            var_dump($user );
        }
        if ($app['security']->isGranted('ROLE_ADMIN')) {
        }*/

        /** @var \Entity\User $user */
        /*$em = $app['orm.ems']['db_write'];
        $user = $em->getRepository('Entity\User')->find(6);
        $role = $em->getRepository('Entity\Role')->findOneByRole('ROLE_STUDENT');
        $user->getRolesObj()->add($role);
        $em->persist($user);
        $em->flush();*/

        //$user->roles->add($status);
        /*$roles = $user->getRolesObj();
        foreach ($roles as $role) {
        }*/

        // $countries = Intl::getRegionBundle()->getCountryNames('es');
        //var_dump($countries);

        /*$formatter = new \IntlDateFormatter(\Locale::getDefault(), \IntlDateFormatter::NONE, \IntlDateFormatter::NONE);
        //http://userguide.icu-project.org/formatparse/datetime for date formats
        $formatter->setPattern("EEEE d MMMM Y");
        echo $formatter->format(time());*/
        $extra = array();
        if (api_get_setting('use_virtual_keyboard') == 'true') {
            $extra[] = api_get_css(api_get_path(WEB_LIBRARY_JS_PATH).'keyboard/keyboard.css');
            $extra[] = api_get_js('keyboard/jquery.keyboard.js');
        }

        $app['template']->addResource(api_get_jqgrid_js(), 'string');


        $app['this_section'] = SECTION_CAMPUS;
        $request = $app['request'];

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
        /** @var \PageController $pageController */
        $pageController = $app['page_controller'];

        if (api_get_setting('display_categories_on_homepage') == 'true') {
            $template->assign('course_category_block', $pageController->return_courses_in_categories());
        }

        // @todo Custom Facebook connection lib could be replaced with opauth
        // Facebook connection, if activated
        if (api_is_facebook_auth_activated() && !api_get_user_id()) {
            facebook_connect();
        }

        $this->setLoginForm($app);

        if (!api_is_anonymous()) {
            //$pageController->setProfileBlock();
            //$pageController->setUserImageBlock();

            if (api_is_platform_admin()) {
                $pageController->setCourseBlock();
            } else {
                $pageController->return_teacher_link();
            }
        }

        // Hot courses & announcements
        $hotCourses         = null;
        $announcementsBlock = null;

        // When loading a chamilo page do not include the hot courses and news
        if (!isset($_REQUEST['include'])) {
            if (api_get_setting('show_hot_courses') == 'true') {
                $hotCourses = $pageController->returnHotCourses();
            }
            $announcementsBlock = $pageController->getAnnouncements();
        }

        $template->assign('hot_courses', $hotCourses);
        $template->assign('announcements_block', $announcementsBlock);

        // Homepage
        $template->assign('home_page_block', $pageController->returnHomePage());

        // Navigation links
        $pageController->returnNavigationLinks($template->getNavigationLinks());
        $pageController->returnNotice();
        $pageController->returnHelp();

        if (api_is_platform_admin() || api_is_drh()) {
            $pageController->returnSkillsLinks();
        }

        $response = $template->renderLayout('layout_2_col.tpl');
        return new Response($response, 200, array());
    }

    /**
     * @param Application $app
     * @return Response
     */
    public function loginAction(Application $app)
    {
        $request = $this->getRequest();
        $app['template']->assign('error', $app['security.last_error']($request));
        $extra = array();
        if (api_get_setting('use_virtual_keyboard') == 'true') {
            $extra[] = api_get_css(api_get_path(WEB_LIBRARY_JS_PATH).'keyboard/keyboard.css');
            $extra[] = api_get_js('keyboard/jquery.keyboard.js');
        }
        $app['template']->addResource($extra, 'string');
        $response = $app['template']->render_template('auth/login.tpl');
        return new Response($response, 200, array('Cache-Control' => 's-maxage=3600, public'));
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
        $form = new \FormValidator(
            'formLogin',
            'POST',
            $app['url_generator']->generate('secured_login_check'),
            null,
            array('class'=> 'form-signin-block')
        );

        $renderer =& $form->defaultRenderer();
        $renderer->setElementTemplate('{element}');
        $form->addElement(
            'text',
            'username',
            null,
            array(
                'class' => 'input-medium autocapitalize_off virtualkey',
                'placeholder' => get_lang('UserName'),
                'autofocus' => 'autofocus',
                'icon' => 'fa fa-user fa-fw'
            )
        );
        $form->addElement(
            'password',
            'password',
            null,
            array(
                'placeholder' => get_lang('Pass'),
                'class' => 'input-medium virtualkey',
                'icon' => 'fa fa-key fa-fw'
            )
        );
        $form->addElement('style_submit_button', 'submitAuth', get_lang('LoginEnter'), array('class' => 'btn btn-primary btn-block'));
        $html = $form->return_form();

        /** Verify if settings is active to set keyboard. Included extra class in form input elements */

        if (api_get_setting('use_virtual_keyboard') == 'true') {
            $html .= "<script>
                $(function(){
                    $('.virtualkey').keyboard({
                        layout:'custom',
                        customLayout: {
                        'default': [
                            '1 2 3 4 5 6 7 8 9 0 {bksp}',
                            'q w e r t y u i o p',
                            'a s d f g h j k l',
                            'z x c v b n m',
                            '{cancel} {accept}'
                        ]
                        }
                    });
                });
            </script>";
        }
        return $html;
    }

    /**
     * @todo move all this getDocument* actions into another controller
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
     * Gets a document from the data/courses/MATHS/document/file.jpg to the user
     * @todo check permissions
     * @param Application $app
     * @param string $courseCode
     * @param string $file
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse|void
     */
    public function getCourseUploadFileAction(Application $app, $courseCode, $file)
    {
        try {
            $file = $app['chamilo.filesystem']->getCourseUploadFile($courseCode, $file);
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
     * Gets a document from the data/default_platform_document/* folder
     * @param Application $app
     * @param string $file
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse|void
     */
    public function getDefaultCourseDocumentAction(Application $app, $file)
    {
        try {
            $file = $app['chamilo.filesystem']->get('default_course_document/'.$file);
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

    /**
     * @param Application $app
     * @return Response
     */
    function dashboardAction(Application $app)
    {
        $template = $this->getTemplate();

        $template->assign('content', 'welcome!');
        $response = $template->renderLayout('layout_2_col.tpl');

        //return new Response($response, 200, array('Cache-Control' => 's-maxage=3600, public'));
        return new Response($response, 200, array());
    }
}
