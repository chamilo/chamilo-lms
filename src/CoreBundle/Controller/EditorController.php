<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller;

use Chamilo\CoreBundle\Component\Editor\CkEditor\CkEditor;
use Chamilo\CoreBundle\Component\Editor\Connector;
use Chamilo\CoreBundle\Component\Editor\Finder;
use Chamilo\CoreBundle\Component\Utils\ChamiloApi;
use FM\ElFinderPHP\Connector\ElFinderConnector;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class EditorController.
 *
 * @Route("/editor")
 *
 * @deprecated not used for now
 *
 * @package Chamilo\CoreBundle\Controller
 */
class EditorController extends Controller
{
    /**
     * Get templates (left column when creating a document).
     *
     * @Route("/templates", name="editor_templates")
     * @Method({"GET"})
     */
    public function editorTemplatesAction()
    {
        $editor = new CkEditor(
            $this->get('translator.default'),
            $this->get('router')
        );
        $templates = $editor->simpleFormatTemplates();

        return $this->render(
            '@ChamiloCore/Editor/templates.html.twig',
            ['templates' => $templates]
        );
    }

    /**
     * @Route("/filemanager", name="editor_filemanager")
     * @Method({"GET"})
     */
    public function editorFileManager()
    {
        \Chat::setDisableChat();

        return $this->render('@ChamiloCore/Editor/elfinder.html.twig');
    }

    /**
     * @Route("/connector", name="editor_connector")
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
     * @Route("/config", name="config_editor")
     *
     * @Method({"GET"})
     *
     * @return Response
     */
    public function configEditorAction()
    {
        $moreButtonsInMaximizedMode = false;
        $settingsManager = $this->get('chamilo.settings.manager');

        if ($settingsManager->getSetting('editor.more_buttons_maximized_mode') === 'true') {
            $moreButtonsInMaximizedMode = true;
        }

        return $this->render(
            'ChamiloCoreBundle:Editor:config_js.html.twig',
            [
                // @todo replace api_get_bootstrap_and_font_awesome
                'bootstrap_css' => api_get_bootstrap_and_font_awesome(true),
                'css_editor' => ChamiloApi::getEditorBlockStylePath(),
                'more_buttons_in_max_mode' => $moreButtonsInMaximizedMode,
            ]
        );
    }
}
