<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller;

use Chamilo\CoreBundle\Component\Editor\CkEditor\CkEditor;
use Chamilo\CoreBundle\Component\Editor\Connector;
use Chamilo\CoreBundle\Component\Editor\Finder;
use Chamilo\CoreBundle\Component\Utils\ChamiloApi;
use Chamilo\SettingsBundle\Manager\SettingsManager;
use DocumentManager;
use FM\ElfinderBundle\Connector\ElFinderConnector;
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
     * @Route("/filemanager/{parentId}", methods={"GET"}, name="editor_filemanager")
     *
     * @param int $parentId
     *
     * @return Response
     */
    public function customEditorFileManager($parentId = 0): Response
    {
        $courseInfo = api_get_course_info();
        $groupIid = api_get_group_id();
        $isAllowedToEdit = api_is_allowed_to_edit();
        $groupMemberWithUploadRights = false;

        $path = '/';
        if (!empty($parentId)) {
            $doc = $this->getDoctrine()->getRepository('ChamiloCourseBundle:CDocument')->find($parentId);
            $path = $doc->getPath();
        }

        $documentAndFolders = DocumentManager::getAllDocumentData(
            $courseInfo,
            $path,
            $groupIid,
            null,
            $isAllowedToEdit || $groupMemberWithUploadRights,
            false,
            0,
            null,
            $parentId
        );

        $url = $this->generateUrl('editor_filemanager', ['parentId' => $parentId]);
        $data = DocumentManager::processDocumentAndFolders(
            $documentAndFolders,
            $courseInfo,
            false,
            $groupMemberWithUploadRights,
            $path,
            true,
            $url
        );

        $table = new \SortableTableFromArrayConfig(
            $data,
            2,
            20,
            'documents',
            [0, 1, 1, 1, 1],
            [],
            'ASC',
            true
        );
        $column = 1;
        $table->set_header($column++, '', false, ['style' => 'width:12px;']);
        $table->set_header($column++, get_lang('Type'), false, ['style' => 'width:30px;']);
        $table->set_header($column++, get_lang('Name'));
        $table->set_header($column++, get_lang('Size'), false, ['style' => 'width:50px;']);
        $table->set_header($column, get_lang('Date'), false, ['style' => 'width:150px;']);

        $params = [
            'table' => $table->return_table(),
            'parent_id' => (int) $parentId
        ];

        return $this->render('@ChamiloTheme/Editor/custom.html.twig', $params);
    }

    /**
     * @Route("/connector", methods={"GET", "POST"}, name="editor_connector")
     *
     * @param TranslatorInterface $translator
     * @param RouterInterface     $router
     *
     * @return Response
     */
    public function editorConnector(TranslatorInterface $translator, RouterInterface $router)
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
