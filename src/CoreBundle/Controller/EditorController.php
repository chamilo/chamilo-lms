<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller;

use Chamilo\CoreBundle\Component\Editor\CkEditor\CkEditor;
use Chamilo\CoreBundle\Component\Editor\Connector;
use Chamilo\CoreBundle\Component\Editor\Finder;
use Chamilo\CoreBundle\Component\Utils\ChamiloApi;
use Chamilo\SettingsBundle\Manager\SettingsManager;
use FM\ElfinderBundle\Connector\ElFinderConnector;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Class EditorController.
 *
 * @Route("/editor")
 *
 * @deprecated not used for now
 *
 * @package Chamilo\CoreBundle\Controller
 */
class EditorController extends BaseController
{
    /**
     * Get templates (left column when creating a document).
     *
     * @Route("/templates", methods={"GET"}, name="editor_templates")
     *
     * @param TranslatorInterface $translator
     * @param RouterInterface     $router
     *
     * @return Response
     */
    public function editorTemplatesAction(TranslatorInterface $translator, RouterInterface $router)
    {
        $editor = new CkEditor(
            $translator,
            $router
        );
        $templates = $editor->simpleFormatTemplates();

        return $this->render(
            '@ChamiloTheme/Editor/templates.html.twig',
            ['templates' => $templates]
        );
    }

    /**
     * @Route("/filemanager", methods={"GET"}, name="editor_filemanager")
     *
     * @return Response
     */
    public function editorFileManager(): Response
    {
        \Chat::setDisableChat();
        $params = [
            'course_condition' => '?'.$this->getCourseUrlQuery(),
        ];

        return $this->render('@ChamiloTheme/Editor/elfinder.html.twig', $params);
    }

    /**
     * @Route("/connector", methods={"GET", "POST"}, name="editor_connector")
     *
     * @param TranslatorInterface $translator
     * @param RouterInterface     $router
     * @param Request             $request
     *
     * @return Response
     */
    public function editorConnector(TranslatorInterface $translator, RouterInterface $router, Request $request)
    {
        $course = $this->getCourse();
        $session = $this->getCourseSession();

        /** @var Connector $connector */
        $connector = new Connector(
            $this->getDoctrine()->getManager(),
            [],
            $router,
            $translator,
            $this->container->get('security.authorization_checker'),
            $this->getUser(),
            $course,
            $session
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
            '@ChamiloTheme/layout_empty.html.twig',
            ['content' => $content]
        );
    }

    /**
     * @Route("/config", methods={"GET"}, name="config_editor")
     *
     * @param SettingsManager $settingsManager
     *
     * @return Response
     */
    public function configEditorAction(SettingsManager $settingsManager)
    {
        $moreButtonsInMaximizedMode = false;
        //$settingsManager = $this->get('chamilo.settings.manager');

        if ($settingsManager->getSetting('editor.more_buttons_maximized_mode') === 'true') {
            $moreButtonsInMaximizedMode = true;
        }

        return $this->render(
            '@ChamiloTheme/Editor/config_js.html.twig',
            [
                // @todo replace api_get_bootstrap_and_font_awesome
                'bootstrap_css' => api_get_bootstrap_and_font_awesome(true),
                'css_editor' => ChamiloApi::getEditorBlockStylePath(),
                'more_buttons_in_max_mode' => $moreButtonsInMaximizedMode,
            ]
        );
    }
}
