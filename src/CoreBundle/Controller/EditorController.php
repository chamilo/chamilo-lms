<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller;

use Chamilo\CoreBundle\Component\Editor\CkEditor\CkEditor;
use Chamilo\CoreBundle\Component\Editor\Connector;
use Chamilo\CoreBundle\Component\Editor\Finder;
use Chamilo\CoreBundle\Component\Utils\ChamiloApi;
use Chamilo\CourseBundle\Entity\CDocument;
use Chamilo\CourseBundle\Repository\CDocumentRepository;
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
     * @Route("/myfilemanager", methods={"GET"}, name="editor_myfiles")
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
     */
    public function customEditorFileManager(\Symfony\Component\HttpFoundation\Request $request,  $parentId = 0, CDocumentRepository $documentRepository): Response
    {
        $id = $request->get('id');
        $courseInfo = api_get_course_info();

        $params = [
            'table' => '',
            'parent_id' => -1,
            'allow_course' => false,
        ];

        if (!empty($courseInfo)) {
            $groupIid = api_get_group_id();
            $isAllowedToEdit = api_is_allowed_to_edit();
            $groupMemberWithUploadRights = false;

            $path = '/';
            $oldParentId = -1;
            if (!empty($parentId)) {
                /** @var CDocument $doc */
                $doc = $this->getDoctrine()->getRepository('ChamiloCourseBundle:CDocument')->findOneBy(['resourceNode'=> $id]);
                $path = $doc->getPath();

                $parent = $documentRepository->getParent($doc);
                $oldParentId = 0;
                if (!empty($parent)) {
                    $oldParentId = $parent->getId();
                }
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

            $url = $this->generateUrl('editor_filemanager');

            $data = DocumentManager::processDocumentAndFolders(
                $documentAndFolders,
                $courseInfo,
                false,
                $groupMemberWithUploadRights,
                $path,
                true,
                $url
            );

            $show = [1, 1, 1, 1];
            if ($isAllowedToEdit) {
                $show = [0, 1, 1, 1, 1];
            }

            $table = new \SortableTableFromArrayConfig(
                $data,
                2,
                20,
                'documents',
                $show,
                [],
                'ASC',
                true
            );
            $column = 1;
            if ($isAllowedToEdit) {
                $table->set_header($column++, '', false, ['style' => 'width:12px;']);
            }
            $table->set_header($column++, get_lang('Type'), false, ['style' => 'width:30px;']);
            $table->set_header($column++, get_lang('Name'));
            $table->set_header($column++, get_lang('Size'), false, ['style' => 'width:50px;']);
            $table->set_header($column, get_lang('Date'), false, ['style' => 'width:150px;']);

            $params = [
                'table' => $table->return_table(),
                'parent_id' => $oldParentId,
                'allow_course' => true,
            ];
        }

        return $this->render('@ChamiloTheme/Editor/custom.html.twig', $params);
    }

    /**
     * @Route("/connector", methods={"GET", "POST"}, name="editor_connector")
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
            //'CourseDriver',
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
