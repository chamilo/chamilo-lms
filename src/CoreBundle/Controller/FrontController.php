<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller;

use Chamilo\CoreBundle\Component\Editor\CkEditor\CkEditor;
use Chamilo\CoreBundle\Component\Editor\Connector;
use Chamilo\CoreBundle\Component\Editor\Finder;
use FM\ElFinderPHP\Connector\ElFinderConnector;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class FrontController.
 *
 * @package Chamilo\CoreBundle\Controller
 */
class FrontController extends Controller
{
    /**
     * Get templates (left column when creating a document).
     *
     * @Route("/editor/templates", name="editor_templates")
     * @Method({"GET"})
     */
    public function editorTemplates()
    {
        $editor = new CkEditor(
            $this->get('translator.default'),
            $this->get('router')
        );
        $templates = $editor->simpleFormatTemplates();

        return $this->render(
            '@ChamiloCore/default/javascript/editor/ckeditor/templates.html.twig',
            ['templates' => $templates]
        );
    }

    /**
     * @Route("/editor/filemanager", name="editor_filemanager")
     * @Method({"GET"})
     */
    public function editorFileManager(Request $request)
    {
        \Chat::setDisableChat();

        $courseId = $request->get('course_id');
        $sessionId = $request->get('session_id');

        return $this->render(
            '@ChamiloCore/default/javascript/editor/ckeditor/elfinder.html.twig',
            [
                'course_id' => $courseId,
                'session_id' => $sessionId,
            ]
        );
    }

    /**
     * @Route("/editor/connector", name="editor_connector")
     * @Method({"GET|POST"})
     */
    public function editorConnector(Request $request)
    {
        error_reporting(-1);
        $courseId = $request->get('course_id');
        $sessionId = $request->get('session_id');

        $courseInfo = [];
        if (!empty($courseId)) {
            $courseInfo = api_get_course_info_by_id($courseId);
        }

        /** @var Connector $connector */
        $connector = new Connector(
            $this->container->get('doctrine')->getManager(),
            [],
            $this->get('router'),
            $this->container->get('translator.default'),
            $this->container->get('security.authorization_checker'),
            $this->getUser(),
            $courseInfo,
            $sessionId
        );

        $driverList = [
            'PersonalDriver',
            'CourseDriver',
            //'CourseUserDriver',
            //'HomeDriver'
        ];
        $connector->setDriverList($driverList);

        $operations = $connector->getOperations();

        // Run elFinder
        ob_start();
        $finder = new Finder($operations);
        $elFinderConnector = new ElFinderConnector($finder);
        $elFinderConnector->run();
        $content = ob_get_contents();

        return $this->render(
            '@ChamiloCore/layout_empty.html.twig',
            ['content' => $content]
        );
    }

    /**
     * @Route("/login")
     * @Method({"GET"})
     */
    public function showLoginAction()
    {
        return $this->render(
            'ChamiloCoreBundle:Security:only_login.html.twig',
            ['error' => null]
        );
    }
}
