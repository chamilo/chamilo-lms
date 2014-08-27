<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller;

use Chamilo\CoreBundle\Admin\CourseAdmin;
use Chamilo\CoreBundle\Framework\PageController;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Chamilo\CoreBundle\Controller\BaseController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Finder\Finder;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
//use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

/**
 * Class IndexController
 * author Julio Montoya <gugli100@gmail.com>
 * @package Chamilo\CoreBundle\Controller
 */
class IndexController extends BaseController
{
    public function indexAction()
    {
        /** @var \PageController $pageController */
        //$pageController = $this->get('page_controller');
        $pageController = new PageController();

/*
        if (api_get_setting('display_categories_on_homepage') == 'true') {
            //$template->assign('course_category_block', $pageController->return_courses_in_categories());
        }

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

        // Navigation links
        //$pageController->returnNavigationLinks($template->getNavigationLinks());
        $pageController->returnNotice();
        $pageController->returnHelp();

        if (api_is_platform_admin() || api_is_drh()) {
            $pageController->returnSkillsLinks();
        }*/

        if (api_get_setting('show_hot_courses') == 'true') {
            $hotCourses = $pageController->returnHotCourses();
        }

        $announcementsBlock = $pageController->getAnnouncements();
        return $this->render(
            'ChamiloCoreBundle:Index:index.html.twig',
            array(
                'content' => 'hello',
                'hot_courses' => $hotCourses,
                'announcements_block' => $announcementsBlock
                //'home_page_block' => $pageController->returnHomePage()
            )
        );
    }


    //@Security("has_role('ROLE_USER')")
    /**
     * @Route("/userportal", name="userportal")
     * @Method({"GET"})
     *
     *
     * @param string $type courses|sessions|mycoursecategories
     * @param string $filter for the userportal courses page. Only works when setting 'history'
     * @param int $page
     * @return Response
     */
    public function userPortalAction($type = 'courses', $filter = 'current', $page = 1)
    {
        /** @var \Chamilo\CoreBundle\Entity\CourseManager $courseManager */
        $courseManager = $this->get('chamilo_core.manager.course');

        /** @var \Application\Sonata\PageBundle\Entity\Site $site */
        $site = $this->get('sonata.page.site.selector')->retrieve();
        $site->getId();

        $user = $this->getUser();
        $pageController = new \Chamilo\CoreBundle\Framework\PageController();
        $items = null;

        if (!empty($user)) {
            $userId = $user->getId();

            // Main courses and session list
            $type = str_replace('/', '', $type);

            switch ($type) {
                case 'sessions':
                    $items = $pageController->returnSessions(
                        $userId,
                        $filter,
                        $page
                    );
                    break;
                case 'sessioncategories':
                    $items = $pageController->returnSessionsCategories(
                        $userId,
                        $filter,
                        $page
                    );
                    break;
                case 'courses':
                    $items = $pageController->returnCourses(
                        $userId,
                        $filter,
                        $page
                    );
                    break;
                case 'mycoursecategories':
                    $items = $pageController->returnMyCourseCategories(
                        $userId,
                        $filter,
                        $page
                    );
                    break;
                case 'specialcourses':
                    $items = $pageController->returnSpecialCourses(
                        $userId,
                        $filter,
                        $page
                    );
                    break;
            }
        }

        $template = $this->getTemplate();

        // Show the chamilo mascot
        if (empty($items) && empty($filter)) {
            $pageController->return_welcome_to_course_block($template);
        }
        /** @var \Chamilo\SettingsBundle\Manager\SettingsManager $settingManager */
        $settingManager = $this->get('chamilo.settings.manager');
        /*var_dump($settingManager->getSetting('platform.institution'));
        $settings = $settingManager->loadSettings('platform');
        var_dump($settings->get('institution'));
        var_dump(api_get_setting('institution'));*/

        $pageController->returnSkillsLinks();

        // Deleting the session_id.
        $this->getSessionHandler()->remove('session_id');

        return $this->render(
            'ChamiloCoreBundle:Index:userportal.html.twig',
            array('content' => $items, 'page_title' => 'dsqd')
        );
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



    //* @Security("has_role('ROLE_TEACHER')")
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
