<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller;

use Chamilo\CoreBundle\Component\Editor\CkEditor\CkEditor;
use Chamilo\CoreBundle\Component\Editor\Connector;
use Chamilo\CoreBundle\Component\Utils\ChamiloApi;
use Chamilo\CoreBundle\Entity\AbstractResource;
use Chamilo\CoreBundle\Repository\ResourceFactory;
use Chamilo\CoreBundle\Security\Authorization\Voter\ResourceNodeVoter;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CoreBundle\Traits\ControllerTrait;
use Chamilo\CoreBundle\Traits\CourseControllerTrait;
use Chamilo\CoreBundle\Traits\ResourceControllerTrait;
use Chat;
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
    use ControllerTrait;
    use ResourceControllerTrait;
    use CourseControllerTrait;

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
            '@ChamiloCore/Editor/templates.html.twig',
            [
                'templates' => $templates,
            ]
        );
    }

    /**
     * @Route("/myfilemanager", methods={"GET"}, name="editor_myfiles")
     */
    public function editorFileManager(): Response
    {
        Chat::setDisableChat();
        $params = [
            'course_condition' => '?'.$this->getCourseUrlQuery(),
        ];

        return $this->render('@ChamiloCore/Editor/elfinder.html.twig', $params);
    }

    /**
     * @Route("/resources/{tool}/{type}/{parentId}", methods={"GET"}, name="resources_filemanager_temp")
     */
    public function customEditorFileManager(ResourceFactory $resourceFactory, Request $request, $tool, $type, int $parentId = 0): Response
    {
        $id = $request->get('id');

        $course = $this->getCourse();
        $session = $this->getCourseSession();
        $parent = $course->getResourceNode();
        $repository = $resourceFactory->getRepositoryService($tool, $type);
        $class = $repository->getRepository()->getClassName();

        if (!empty($parentId)) {
            $parent = $this->getResourceNodeRepository()->find($parentId);
        }

        $this->denyAccessUnlessGranted(
            ResourceNodeVoter::VIEW,
            $parent,
            $this->trans('Unauthorised access to resource')
        );

        $source = new Entity($class, 'editor');

        $qb = $repository->getResourcesByCourse($course, $session, null, $parent);

        // 3. Set QueryBuilder to the source.
        $source->initQueryBuilder($qb);
        $grid->setSource($source);

        $title = $grid->getColumn('title');
        $title->setSafe(false);

        $grid->setLimits(20);
        $grid->setHiddenColumns(['iid']);

        $titleColumn = $repository->getTitleColumn($grid);
        //$titleColumn->setTitle($this->trans('Name'));

        $routeParams = $this->getResourceParams($request);

        $titleColumn->manipulateRenderCell(
            function ($value, Row $row, $router) use ($routeParams, $request) {
                /** @var AbstractResource $entity */
                $entity = $row->getEntity();
                $resourceNode = $entity->getResourceNode();
                $id = $resourceNode->getId();

                $value = cut($value, 20);

                $myParams = $routeParams;
                $myParams['id'] = $id;
                $myParams['parentId'] = $id;

                unset($myParams[0]);

                $url = $router->generate(
                    'resources_filemanager',
                    $myParams
                );

                $class = '';
                if ($resourceNode->hasResourceFile()) {
                    $documentParams = $this->getResourceParams($request);
                    // use id instead of old path (like in Chamilo v1)
                    $documentParams['id'] = $resourceNode->getId();
                    $url = $router->generate(
                        'chamilo_core_resource_view',
                        $documentParams
                    );
                    $class = 'select_to_ckeditor';
                    //return $icon.'<a href="'.$url.'" class="select_to_ckeditor">'.$value.'</a>';
                }
                $icon = '<div class="big_icon"> <a href="'.$url.'" class="'.$class.'" > '.$resourceNode->getThumbnail($router).'</a></div>';

                return $icon.'<div class="content pt-2 pb-2"><a href="'.$url.'" class="'.$class.'" >'.$value.'</a></div>';
            }
        );

        return $grid->getGridResponse(
            '@ChamiloCore/Editor/custom.html.twig',
            [
                'id' => $id,
                'tool' => $tool,
                'type' => $type,
            ]
        );
    }

    /**
     * @Route("/connector", methods={"GET", "POST"}, name="editor_connector")
     *
     * @return Response
     */
    public function editorConnector(TranslatorInterface $translator, RouterInterface $router)
    {
        //$course = $this->getCourse();
        //$session = $this->getCourseSession();

        /** @var Connector $connector */
        /*$connector = new Connector(
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
            '@ChamiloCore/layout_empty.html.twig',
            ['content' => $content]
        );*/
    }

    /**
     * @Route("/config", methods={"GET"}, name="config_editor")
     *
     * @return Response
     */
    public function configEditorAction(Request $request, SettingsManager $settingsManager)
    {
        $moreButtonsInMaximizedMode = false;

        if ('true' === $settingsManager->getSetting('editor.more_buttons_maximized_mode')) {
            $moreButtonsInMaximizedMode = true;
        }

        $type = $request->get('type');
        $tool = $request->get('tool');

        $course = $this->getCourse();
        $nodeId = 0;
        if (null !== $course) {
            $nodeId = $course->getResourceNode()->getId();
        }

        $params = [
            // @todo replace api_get_bootstrap_and_font_awesome
            'bootstrap_css' => api_get_bootstrap_and_font_awesome(true, false),
            'css_editor' => ChamiloApi::getEditorBlockStylePath(),
            'more_buttons_in_max_mode' => $moreButtonsInMaximizedMode,
            'type' => $type,
            'tool' => $tool,
            'nodeId' => $nodeId,
        ];

        $renderedView = $this->renderView('@ChamiloCore/Editor/config_js.html.twig', $params);
        $response = new Response($renderedView);
        $response->headers->set('Content-Type', 'text/javascript');

        return $response;
    }
}
