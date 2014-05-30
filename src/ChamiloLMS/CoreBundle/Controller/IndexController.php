<?php
/* For licensing terms, see /license.txt */

namespace ChamiloLMS\CoreBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use ChamiloLMS\CoreBundle\Controller\BaseController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Finder\Finder;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

/**
 * Class IndexController
 * author Julio Montoya <gugli100@gmail.com>
 * @package ChamiloLMS\CoreBundle\Controller
 */
class IndexController extends BaseController
{
    /**
     * @Route("/index", name="index")
     * @Route("/")
     * @Method({"GET"})
     * @return Response
     */
    public function indexAction()
    {
        return $this->render(
            'ChamiloLMSCoreBundle:Index:index.html.twig',
            array('content' => 'julio')
        );

        //$template = $this->get('templating');
        // $countries = Intl::getRegionBundle()->getCountryNames('es');
        //var_dump($countries);

        /*$formatter = new \IntlDateFormatter(\Locale::getDefault(), \IntlDateFormatter::NONE, \IntlDateFormatter::NONE);
        //http://userguide.icu-project.org/formatparse/datetime for date formats
        $formatter->setPattern("EEEE d MMMM Y");
        echo $formatter->format(time());*/

        $extra = array();
      /*  if ($this->getSetting('use_virtual_keyboard') == 'true') {
            $extra[] = api_get_css(api_get_path(WEB_LIBRARY_JS_PATH).'keyboard/keyboard.css');
            $extra[] = api_get_js('keyboard/jquery.keyboard.js');
        }*/

        //$template->addResource(api_get_jqgrid_js(), 'string');

        //$this->app['this_section'] = SECTION_CAMPUS;

        /** @var \PageController $pageController */
        //$pageController = $this->get('page_controller');

        if (api_get_setting('display_categories_on_homepage') == 'true') {
            //$template->assign('course_category_block', $pageController->return_courses_in_categories());
        }
        $this->setLoginForm();

        if (!api_is_anonymous()) {
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

    public function setLoginForm()
    {
        $userId    = api_get_user_id();
        $loginForm = null;
        if (!$userId || api_is_anonymous($userId)) {

            // Only display if the user isn't logged in

            $this->getTemplate()->assign('login_language_form', api_display_language_form(true));
            $this->getTemplate()->assign('login_form', self::displayLoginForm());

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
            $this->getTemplate()->assign('login_options', $loginForm);
        }
    }

    /**
     * @param \Silex\Application $app
     *
     * @return string
     */
    public function displayLoginForm()
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
            $this->get('url_generator')->generate('secured_login_check'),
            null,
            array('class'=> 'form-signin-block')
        );

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

    public function dashboardAction()
    {
        /*$template = $this->getTemplate();

        $template->assign('content', 'welcome!');
        $response = $template->renderLayout('layout_2_col.tpl');

        //return new Response($response, 200, array('Cache-Control' => 's-maxage=3600, public'));
        return new Response($response, 200, array());*/
    }

    /**
     * @Route("/userportal", name="userportal")
     * @Method({"GET"})
     *
     * @param string $type courses|sessions|mycoursecategories
     * @param string $filter for the userportal courses page. Only works when setting 'history'
     * @param int $page
     * @return Response
     */
    public function userPortalAction($type = 'courses', $filter = 'current', $page = 1)
    {
        //api_block_anonymous_users();

        // Abort request because the user is not allowed here - @todo use filters
        /*if ($this->app['allowed'] == false) {
            return $this->abort(403, 'Not allowed');
        }*/

        // Main courses and session list
        $items = null;
        $type = str_replace('/', '', $type);

        $pageController = new \ChamiloLMS\CoreBundle\Framework\PageController();

        switch ($type) {
            case 'sessions':
                $items = $pageController->returnSessions(api_get_user_id(), $filter, $page);
                break;
            case 'sessioncategories':
                $items = $pageController->returnSessionsCategories(api_get_user_id(), $filter, $page);
                break;
            case 'courses':
                $items = $pageController->returnCourses(api_get_user_id(), $filter, $page);
                break;
            case 'mycoursecategories':
                $items = $pageController->returnMyCourseCategories(api_get_user_id(), $filter, $page);
                break;
            case 'specialcourses':
                $items = $pageController->returnSpecialCourses(api_get_user_id(), $filter, $page);
                break;
        }

        $template = $this->getTemplate();
        //Show the chamilo mascot
        if (empty($items) && empty($filter)) {
            $pageController->return_welcome_to_course_block($template);
        }

        /*
        $app['my_main_menu'] = function($app) {
            $menu = $app['knp_menu.factory']->createItem('root');
            $menu->addChild('Home', array('route' => api_get_path(WEB_CODE_PATH)));
            return $menu;
        };
        $app['knp_menu.menus'] = array('main' => 'my_main_menu');*/

        /*$pageController->setCourseSessionMenu();
        $pageController->setProfileBlock();
        $pageController->setUserImageBlock();
        $pageController->setCourseBlock($filter);
        $pageController->setSessionBlock();
        $pageController->return_reservation_block();
        $pageController->returnNavigationLinks($template->getNavigationLinks());*/

        //$template->assign('search_block', $pageController->return_search_block());
        //$template->assign('classes_block', $pageController->return_classes_block());
        $pageController->returnSkillsLinks();

        // Deleting the session_id.
        $this->getSessionHandler()->remove('session_id');


        return $this->render(
            'ChamiloLMSCoreBundle:Index:userportal.html.twig',
            array('content' => $items)
        );
    }

    /**
     * Toggle the student view action
     *
     * @Route("/toggle_student_view")
     * @Method({"GET"})
     *
     * @return Response
     */
    public function toggleStudentViewAction()
    {
        if (!api_is_allowed_to_edit(false, false, false, false)) {
            return '';
        }
        $request = $this->getRequest();
        $studentView = $request->getSession()->get('studentview');
        if (empty($studentView) || $studentView == 'studentview') {
            $request->getSession()->set('studentview', 'teacherview');
            return 'teacherview';
        } else {
            $request->getSession()->set('studentview', 'studentview');
            return 'studentview';
        }
    }
}
