<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller;

use APY\DataGridBundle\Grid\Action\MassAction;
use APY\DataGridBundle\Grid\Action\RowAction;
use APY\DataGridBundle\Grid\Grid;
use APY\DataGridBundle\Grid\Row;
use APY\DataGridBundle\Grid\Source\Entity;
use Chamilo\CoreBundle\Component\Editor\CkEditor\CkEditor;
use Chamilo\CoreBundle\Component\Editor\Connector;
use Chamilo\CoreBundle\Component\Editor\Finder;
use Chamilo\CoreBundle\Component\Utils\ChamiloApi;
use Chamilo\CoreBundle\Security\Authorization\Voter\ResourceNodeVoter;
use Chamilo\CourseBundle\Entity\CDocument;
use Chamilo\CourseBundle\Repository\CDocumentRepository;
use Chamilo\SettingsBundle\Manager\SettingsManager;
use DocumentManager;
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
     * @todo use resource repository instead of hardcoded CDocumentRepository
     *
     * @Route("/filemanager/{parentId}", methods={"GET"}, name="editor_filemanager")
     *
     * @param int $parentId
     */
    public function customEditorFileManager(Request $request, Grid $grid, $parentId = 0, CDocumentRepository $repository): Response
    {
        $id = $request->get('id');

        $course = $this->getCourse();
        $session = $this->getCourseSession();
        $parent = $course->getResourceNode();

        if (!empty($parentId)) {
            $parent = $repository->getResourceNodeRepository()->find($parentId);
        }

        $source = new Entity(CDocument::class);

        $qb = $repository->getResourcesByCourse($course, $session, null, $parent);

        // 3. Set QueryBuilder to the source.
        $source->initQueryBuilder($qb);
        $grid->setSource($source);

        $title = $grid->getColumn('title');
        $title->setSafe(false);

        //$grid->hideFilters();
        $grid->setLimits(20);
        //$grid->isReadyForRedirect();
        //$grid->setMaxResults(1);
        //$grid->setLimits(2);

        $courseIdentifier = $course->getCode();

        $routeParams = ['cidReq' => $courseIdentifier, 'id'];

        $grid->getColumn('title')->manipulateRenderCell(
            function ($value, Row $row, $router) use ($course, $routeParams) {
                /** @var CDocument $entity */
                $entity = $row->getEntity();
                $resourceNode = $entity->getResourceNode();
                $id = $resourceNode->getId();

                $myParams = $routeParams;
                $myParams['id'] = $id;
                $myParams['parentId'] = $id;

                unset($myParams[0]);

                if ($resourceNode->hasResourceFile()) {
                    $documentParams = [
                        'course' => $course->getCode(),
                        'file' => $resourceNode->getPathForDisplay()
                    ];
                    $url = $router->generate(
                        'resources_document_get_file',
                        $documentParams
                    );
                    return '<a href="'.$url.'" class="select_to_ckeditor">'.$value.'</a>';
                }

                $url = $router->generate(
                    'editor_filemanager',
                    $myParams
                );

                return '<a href="'.$url.'">'.$value.'</a>';
            }
        );

        // Show resource data
        /*$myRowAction = new RowAction(
            'use',
            'chamilo_core_resource_show',
            false,
            '_self',
            ['class' => 'btn btn-secondary']
        );
        $myRowAction->setRouteParameters($routeParams);

        $setNodeParameters = function (RowAction $action, Row $row) use ($routeParams) {
            $id = $row->getEntity()->getResourceNode()->getId();
            $routeParams['id'] = $id;
            $action->setRouteParameters($routeParams);
            return $action;
        };
        $myRowAction->addManipulateRender($setNodeParameters);

        $grid->addRowAction($myRowAction);*/

        //return $this->render('@ChamiloTheme/Editor/custom.html.twig', $params);

        return $grid->getGridResponse(
            '@ChamiloTheme/Editor/custom.html.twig',
            ['id' => $id, 'grid' => $grid]
        );

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
