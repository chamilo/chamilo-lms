<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller;

use Chamilo\CoreBundle\Entity\AbstractResource;
use Chamilo\CoreBundle\Entity\ResourceInterface;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Entity\ResourceNode;
use Chamilo\CoreBundle\Form\Type\ResourceCommentType;
use Chamilo\CoreBundle\Repository\IllustrationRepository;
use Chamilo\CoreBundle\Repository\ResourceRepository;
use Chamilo\CoreBundle\Repository\ResourceWithLinkInterface;
use Chamilo\CoreBundle\Security\Authorization\Voter\ResourceNodeVoter;
use Chamilo\CoreBundle\Traits\ControllerTrait;
use Chamilo\CoreBundle\Traits\CourseControllerTrait;
use Chamilo\CoreBundle\Traits\ResourceControllerTrait;
use Chamilo\CourseBundle\Controller\CourseControllerInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\QueryBuilder;
use FOS\CKEditorBundle\Form\Type\CKEditorType;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Router;
use Symfony\Component\Routing\RouterInterface;
use ZipStream\Option\Archive;
use ZipStream\ZipStream;

/**
 * Class ResourceController.
 *
 * @Route("/resources2")
 *
 * @author Julio Montoya <gugli100@gmail.com>.
 */
class ResourceController extends AbstractResourceController implements CourseControllerInterface
{
    use CourseControllerTrait;
    use ResourceControllerTrait;
    use ControllerTrait;

    private $fileContentName = 'file_content';

    /**
     * @Route("/{tool}/{type}", name="chamilo_core_resource_index")
     *
     * Example: /document/files (See the 'tool' and the 'resource_type' DB tables.)
     * For the tool value check the Tool entity.
     * For the type value check the ResourceType entity.
     */
    public function indexAction(Request $request): Response
    {
        $tool = $request->get('tool');
        $type = $request->get('type');

        $parentResourceNode = $this->getParentResourceNode($request);
        $repository = $this->getRepositoryFromRequest($request);
        $settings = $repository->getResourceSettings();

        /*$grid = $this->getGrid(
            $request,
            $repository,
            $grid,
            $parentResourceNode->getId(),
            'chamilo_core_resource_index'
        );*/

        // The base resource node is the course.
        $id = $parentResourceNode->getId();

        //return $grid->getGridResponse(
        return $this->render(
            $repository->getTemplates()->getFromAction(__FUNCTION__),
            [
                'tool' => $tool,
                'type' => $type,
                'id' => $id,
                'parent_resource_node' => $parentResourceNode,
                'resource_settings' => $settings,
            ]
        );
    }

    public function getGrid(Request $request, ResourceRepository $repository, int $resourceNodeId, string $routeName): Grid
    {
        $class = $repository->getRepository()->getClassName();

        // The group 'resource' is set in the @GRID\Source annotation in the entity.
        $source = new Entity($class, 'resource');
        /** @var ResourceNode $parentNode */
        $parentNode = $repository->getResourceNodeRepository()->find($resourceNodeId);

        $this->denyAccessUnlessGranted(
            ResourceNodeVoter::VIEW,
            $parentNode,
            $this->trans('Unauthorised access to resource')
        );

        $settings = $repository->getResourceSettings();
        $course = $this->getCourse();
        $session = $this->getSession();
        /** @var QueryBuilder $qb */
        $qb = $repository->getResources($this->getUser(), $parentNode, $course, $session, null);

        //$qb->getQuery()->setFetchMode(\Doctrine\ORM\Mapping\ClassMetadata::FETCH_EAGER);

        // 3. Set QueryBuilder to the source.
        $source->initQueryBuilder($qb);
        $grid->setSource($source);

        $resourceParams = $this->getResourceParams($request);

        if (0 === $resourceParams['id']) {
            $resourceParams['id'] = $resourceNodeId;
        }

        $grid->setRouteUrl($this->generateUrl($routeName, $resourceParams));

        $routeParams = $resourceParams;
        $routeParams['id'] = null;

        /** @var Column $titleColumn */
        $titleColumn = $repository->getTitleColumn($grid);

        $titleColumn->setSafe(false); // allows links in the title

        // Title link.
        $titleColumn->setTitle($this->trans('Name'));
        $titleColumn->manipulateRenderCell(
            function ($value, Row $row, $router) use ($routeParams, $settings) {
                /** @var Router $router */
                /** @var ResourceInterface $entity */
                $entity = $row->getEntity();
                $resourceNode = $entity->getResourceNode();
                $id = $resourceNode->getId();

                $myParams = $routeParams;
                $myParams['id'] = $id;
                unset($myParams[0]);

                $icon = $resourceNode->getIcon().' &nbsp;';

                if ($resourceNode->hasResourceFile()) {
                    // Process node that contains a file, process previews.
                    if ($resourceNode->isResourceFileAnImage()) {
                        $url = $router->generate('chamilo_core_resource_view_file', $myParams);

                        return $icon.'<a data-fancybox="gallery" href="'.$url.'">'.$value.'</a>';
                    }

                    if ($resourceNode->isResourceFileAVideo()) {
                        $url = $router->generate('chamilo_core_resource_view_file', $myParams);

                        return '
                        <video width="640" height="320" controls id="video'.$id.'" controls preload="metadata" style="display:none;">
                            <source src="'.$url.'" type="video/mp4">
                            Your browser doesn\'t support HTML5 video tag.
                        </video>
                        '.$icon.' <a data-fancybox="gallery"  data-width="640" data-height="360" href="#video'.$id.'">'.$value.'</a>';
                    }

                    $url = $router->generate('chamilo_core_resource_preview', $myParams);

                    return $icon.
                        '<a data-fancybox="gallery" data-type="iframe" data-src="'.$url.'" href="javascript:;" >'.
                        $value.'</a>';
                } else {
                    if ($settings->isAllowNodeCreation()) {
                        $url = $router->generate(
                            'chamilo_core_resource_list',
                            $myParams
                        );
                    } else {
                        $url = $router->generate('chamilo_core_resource_view_resource', $myParams);
                    }

                    return $icon.'<a href="'.$url.'">'.$value.'</a>';
                }
            }
        );

        /*if ($grid->hasColumn('filetype')) {
            $grid->getColumn('filetype')->manipulateRenderCell(
                function ($value, Row $row, $router) {
                    $entity = $row->getEntity();
                    $resourceNode = $entity->getResourceNode();

                    if ($resourceNode->hasResourceFile()) {
                        $file = $resourceNode->getResourceFile();

                        return $file->getMimeType();
                    }

                    return $this->trans('Folder');
                }
            );
        }*/

        if ($grid->hasColumn('iid')) {
            $grid->setHiddenColumns(['iid']);
        }

        // Delete mass action.
        if ($this->isGranted(ResourceNodeVoter::DELETE, $parentNode)) {
            $routeParams['icon'] = 'far fa-trash-alt';
            $deleteMassAction = new MassAction(
                'Delete',
                'ChamiloCoreBundle:Resource:deleteMass',
                true,
                $routeParams
            );
            $grid->addMassAction($deleteMassAction);
        }

        // Info action.
        $myRowAction = new RowAction(
            $this->trans('Info'),
            'chamilo_core_resource_info',
            false,
            '_self',
            [
                'class' => 'btn btn-secondary info_action resource_info_row',
                'icon' => 'fa-info-circle',
                'iframe' => false,
            ]
        );

        $setNodeParameters = function (RowAction $action, Row $row) use ($routeParams) {
            $id = $row->getEntity()->getResourceNode()->getId();
            $routeParams['id'] = $id;
            $action->setRouteParameters($routeParams);
            $attributes = $action->getAttributes();
            $attributes['data-action'] = $action->getRoute();
            $attributes['data-action-id'] = $action->getRoute().'_'.$id;
            $attributes['data-node-id'] = $id;
            $attributes['title'] = $this->trans('Info').' '.$row->getEntity()->getResourceName();
            $action->setAttributes($attributes);

            return $action;
        };

        $myRowAction->addManipulateRender($setNodeParameters);
        $grid->addRowAction($myRowAction);

        // Download action.
        $myRowAction = new RowAction(
            $this->trans('Download'),
            'chamilo_core_resource_download',
            false,
            '_self',
            [
                'class' => 'btn btn-secondary download_action',
                'icon' => 'fa-download',
                'title' => $this->trans('Download'),
            ]
        );

        $setNodeDownloadParameters = function (RowAction $action, Row $row) use ($routeParams) {
            /** @var ResourceNode $resourceNode */
            $resourceNode = $row->getEntity()->getResourceNode();
            if (false === $resourceNode->hasResourceFile()) {
                return null;
            }
            $id = $row->getEntity()->getResourceNode()->getId();
            $routeParams['id'] = $id;
            $action->setRouteParameters($routeParams);
            $attributes = $action->getAttributes();
            $action->setAttributes($attributes);

            return $action;
        };
        $myRowAction->addManipulateRender($setNodeDownloadParameters);
        $grid->addRowAction($myRowAction);

        // Set EDIT/DELETE
        $setNodeParameters = function (RowAction $action, Row $row) use ($routeParams) {
            $id = $row->getEntity()->getResourceNode()->getId();
            $allowedEdit = $this->isGranted(ResourceNodeVoter::EDIT, $row->getEntity()->getResourceNode());

            if (false === $allowedEdit) {
                return null;
            }

            $routeParams['id'] = $id;

            $action->setRouteParameters($routeParams);
            $attributes = $action->getAttributes();
            //$attributes['data-action'] = $action->getRoute();
            //$attributes['data-action-id'] = $action->getRoute().'_'.$id;
            //$attributes['data-node-id'] = $id;
            $action->setAttributes($attributes);

            return $action;
        };

        if ($this->isGranted(ResourceNodeVoter::EDIT, $parentNode)) {
            // Enable/Disable
            $myRowAction = new RowAction(
                '',
                'chamilo_core_resource_change_visibility',
                false,
                '_self'
            );

            $setVisibleParameters = function (RowAction $action, Row $row) use ($routeParams) {
                /** @var AbstractResource $resource */
                $resource = $row->getEntity();
                $resourceNode = $resource->getResourceNode();
                $allowedEdit = $this->isGranted(ResourceNodeVoter::EDIT, $resourceNode);

                if (false === $allowedEdit) {
                    return null;
                }

                $id = $resourceNode->getId();
                $icon = 'fa-eye-slash';

                if ($this->hasCourse()) {
                    $link = $resource->getCourseSessionResourceLink($this->getCourse(), $this->getSession());
                } else {
                    $link = $resource->getFirstResourceLink();
                }
                if (null === $link) {
                    return null;
                }

                if (ResourceLink::VISIBILITY_PUBLISHED === $link->getVisibility()) {
                    $icon = 'fa-eye';
                }
                $routeParams['id'] = $id;
                $action->setRouteParameters($routeParams);
                $attributes = [
                    'class' => 'btn btn-secondary change_visibility',
                    'data-id' => $id,
                    'icon' => $icon,
                ];
                $action->setAttributes($attributes);

                return $action;
            };

            $myRowAction->addManipulateRender($setVisibleParameters);
            $grid->addRowAction($myRowAction);

            if ($settings->isAllowResourceEdit()) {
                // Edit action.
                $myRowAction = new RowAction(
                    $this->trans('Edit'),
                    'chamilo_core_resource_edit',
                    false,
                    '_self',
                    ['class' => 'btn btn-secondary', 'icon' => 'fa fa-pen']
                );
                $myRowAction->addManipulateRender($setNodeParameters);
                $grid->addRowAction($myRowAction);
            }

            // More action.
            /*$myRowAction = new RowAction(
                $this->trans('More'),
                'chamilo_core_resource_preview',
                false,
                '_self',
                ['class' => 'btn btn-secondary edit_resource', 'icon' => 'fa fa-ellipsis-h']
            );

            $myRowAction->addManipulateRender($setNodeParameters);
            $grid->addRowAction($myRowAction);*/

            // Delete action.
            $myRowAction = new RowAction(
                $this->trans('Delete'),
                'chamilo_core_resource_delete',
                true,
                '_self',
                [
                    'class' => 'btn btn-danger',
                    //'data_hidden' => true,
                ]
            );
            $myRowAction->addManipulateRender($setNodeParameters);
            $grid->addRowAction($myRowAction);
        }
        /*$grid->addExport(new CSVExport($this->trans('CSV export'), 'export', ['course' => $courseIdentifier]));
        $grid->addExport(
            new ExcelExport(
                $this->trans('Excel export'),
                'export',
                ['course' => $courseIdentifier]
            )
        );*/

        return $grid;
    }

    /**
     * @Route("/{tool}/{type}/{id}/list", name="chamilo_core_resource_list")
     *
     * If node has children show it
     */
    public function listAction(Request $request): Response
    {
        $tool = $request->get('tool');
        $type = $request->get('type');
        $resourceNodeId = $request->get('id');

        $repository = $this->getRepositoryFromRequest($request);
        $settings = $repository->getResourceSettings();

        $grid = $this->getGrid($request, $repository, $grid, $resourceNodeId, 'chamilo_core_resource_list');
        $parentResourceNode = $this->getParentResourceNode($request);

        $this->setBreadCrumb($request, $parentResourceNode);

        //return $grid->getGridResponse(
        return $this->render(
            $repository->getTemplates()->getFromAction(__FUNCTION__),
            [
                'parent_id' => $resourceNodeId,
                'tool' => $tool,
                'type' => $type,
                'id' => $resourceNodeId,
                'parent_resource_node' => $parentResourceNode,
                'resource_settings' => $settings,
            ]
        );
    }

    /**
     * @Route("/{tool}/{type}/{id}/new_folder", methods={"GET", "POST"}, name="chamilo_core_resource_new_folder")
     */
    public function newFolderAction(Request $request): Response
    {
        return $this->createResource($request, 'folder');
    }

    /**
     * @Route("/{tool}/{type}/{id}/new", methods={"GET", "POST"}, name="chamilo_core_resource_new")
     */
    public function newAction(Request $request): Response
    {
        return $this->createResource($request, 'file');
    }

    /**
     * @Route("/{tool}/{type}/{id}/disk_space", methods={"GET", "POST"}, name="chamilo_core_resource_disk_space")
     */
    public function diskSpaceAction(Request $request): Response
    {
        $nodeId = $request->get('id');
        $repository = $this->getRepositoryFromRequest($request);

        /** @var ResourceNode $resourceNode */
        $resourceNode = $repository->getResourceNodeRepository()->find($nodeId);

        $this->setBreadCrumb($request, $resourceNode);

        $this->denyAccessUnlessGranted(
            ResourceNodeVoter::VIEW,
            $resourceNode,
            $this->trans('Unauthorised access to resource')
        );

        $course = $this->getCourse();
        $totalSize = 0;
        if ($course) {
            $totalSize = $course->getDiskQuota();
        }

        $size = $repository->getResourceNodeRepository()->getSize(
            $resourceNode,
            $repository->getResourceType(),
            $course
        );

        $labels[] = $course->getTitle();
        $data[] = $size;
        $sessions = $course->getSessions();

        foreach ($sessions as $sessionRelCourse) {
            $session = $sessionRelCourse->getSession();

            $labels[] = $course->getTitle().' - '.$session->getName();
            $size = $repository->getResourceNodeRepository()->getSize(
                $resourceNode,
                $repository->getResourceType(),
                $course,
                $session
            );
            $data[] = $size;
        }

        $groups = $course->getGroups();
        foreach ($groups as $group) {
            $labels[] = $course->getTitle().' - '.$group->getName();
            $size = $repository->getResourceNodeRepository()->getSize(
                $resourceNode,
                $repository->getResourceType(),
                $course,
                null,
                $group
            );
            $data[] = $size;
        }

        $used = array_sum($data);
        $labels[] = $this->trans('Free');
        $data[] = $totalSize - $used;

        return $this->render(
            $repository->getTemplates()->getFromAction(__FUNCTION__),
            [
                'resourceNode' => $resourceNode,
                'labels' => $labels,
                'data' => $data,
            ]
        );
    }

    /**
     * @Route("/{tool}/{type}/{id}/edit", methods={"GET", "POST"})
     */
    public function editAction(Request $request, IllustrationRepository $illustrationRepository): Response
    {
        $resourceNodeId = $request->get('id');

        $repository = $this->getRepositoryFromRequest($request);
        $resource = $repository->getResourceFromResourceNode($resourceNodeId);
        $this->denyAccessUnlessValidResource($resource);
        $settings = $repository->getResourceSettings();
        $resourceNode = $resource->getResourceNode();

        $this->setBreadCrumb($request, $resourceNode);

        $this->denyAccessUnlessGranted(
            ResourceNodeVoter::EDIT,
            $resourceNode,
            $this->trans('Unauthorised access to resource')
        );

        $resourceNodeParentId = $resourceNode->getId();

        $routeParams = $this->getResourceParams($request);
        $routeParams['id'] = $resourceNodeParentId;

        $form = $repository->getForm($this->container->get('form.factory'), $resource);

        if ($resourceNode->hasEditableContent() && $settings->isAllowToSaveEditorToResourceFile()) {
            $form->add(
                $this->fileContentName,
                CKEditorType::class,
                [
                    'mapped' => false,
                    'config' => [
                        'filebrowserImageBrowseRoute' => 'resources_filemanager',
                        'filebrowserImageBrowseRouteParameters' => $routeParams,
                    ],
                ]
            );
            $content = $repository->getResourceNodeFileContent($resourceNode);
            $form->get($this->fileContentName)->setData($content);
        }

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var AbstractResource $newResource */
            $newResource = $form->getData();

            if ($form->has($this->fileContentName)) {
                $data = $form->get($this->fileContentName)->getData();
                $repository->updateResourceFileContent($newResource, $data);
            }

            $repository->updateNodeForResource($newResource);

            if ($form->has('illustration')) {
                $illustration = $form->get('illustration')->getData();
                if ($illustration) {
                    $file = $illustrationRepository->addIllustration($newResource, $this->getUser(), $illustration);
                    $em = $illustrationRepository->getEntityManager();
                    $em->persist($file);
                    $em->flush();
                }
            }

            $this->addFlash('success', $this->trans('Updated'));

            $resourceNodeParentId = $newResource->getResourceNode()->getParent()->getId();
            $routeParams['id'] = $resourceNodeParentId;

            return $this->redirectToRoute('chamilo_core_resource_list', $routeParams);
        }

        return $this->render(
            $repository->getTemplates()->getFromAction(__FUNCTION__),
            [
                'form' => $form->createView(),
                'parent' => $resourceNodeParentId,
            ]
        );
    }

    /**
     * Shows a resource information.
     *
     * @Route("/{tool}/{type}/{id}/info", methods={"GET", "POST"}, name="chamilo_core_resource_info")
     */
    public function infoAction(Request $request): Response
    {
        $nodeId = $request->get('id');
        $repository = $this->getRepositoryFromRequest($request);

        /** @var AbstractResource $resource */
        $resource = $repository->getResourceFromResourceNode($nodeId);
        $this->denyAccessUnlessValidResource($resource);

        $resourceNode = $resource->getResourceNode();
        $this->setBreadCrumb($request, $resourceNode);

        $this->denyAccessUnlessGranted(
            ResourceNodeVoter::VIEW,
            $resourceNode,
            $this->trans('Unauthorised access to resource')
        );

        $tool = $request->get('tool');
        $type = $request->get('type');

        //$illustration = $illustrationRepository->getIllustrationUrlFromNode($resourceNode);
        $form = $this->createForm(ResourceCommentType::class, null);

        $params = [
            'resource' => $resource,
            'course' => $this->getCourse(),
         //   'illustration' => $illustration,
            'tool' => $tool,
            'type' => $type,
            'comment_form' => $form->createView(),
        ];

        return $this->render(
            $repository->getTemplates()->getFromAction(__FUNCTION__, $request->isXmlHttpRequest()),
            $params
        );
    }

    /**
     * Preview a file. Mostly used when using a modal.
     *
     * @Route("/{tool}/{type}/{id}/preview", methods={"GET"}, name="chamilo_core_resource_preview")
     */
    public function previewAction(Request $request): Response
    {
        $nodeId = $request->get('id');
        $repository = $this->getRepositoryFromRequest($request);

        /** @var AbstractResource $resource */
        $resource = $repository->getResourceFromResourceNode($nodeId);
        $this->denyAccessUnlessValidResource($resource);

        $resourceNode = $resource->getResourceNode();
        $this->setBreadCrumb($request, $resourceNode);

        $this->denyAccessUnlessGranted(
            ResourceNodeVoter::VIEW,
            $resourceNode,
            $this->trans('Unauthorised access to resource')
        );

        $tool = $request->get('tool');
        $type = $request->get('type');

        $params = [
            'resource' => $resource,
            'tool' => $tool,
            'type' => $type,
        ];

        return $this->render($repository->getTemplates()->getFromAction(__FUNCTION__), $params);
    }

    /**
     * @Route("/{tool}/{type}/{id}/change_visibility", name="chamilo_core_resource_change_visibility")
     */
    public function changeVisibilityAction(Request $request): Response
    {
        $id = $request->get('id');

        $repository = $this->getRepositoryFromRequest($request);

        /** @var AbstractResource $resource */
        $resource = $repository->getResourceFromResourceNode($id);
        $this->denyAccessUnlessValidResource($resource);

        $resourceNode = $resource->getResourceNode();

        $this->denyAccessUnlessGranted(
            ResourceNodeVoter::EDIT,
            $resourceNode,
            $this->trans('Unauthorised access to resource')
        );

        /** @var ResourceLink $link */
        if ($this->hasCourse()) {
            $link = $resource->getCourseSessionResourceLink($this->getCourse(), $this->getSession());
        } else {
            $link = $resource->getFirstResourceLink();
        }

        $icon = 'fa-eye';
        // Use repository to change settings easily.
        if ($link && ResourceLink::VISIBILITY_PUBLISHED === $link->getVisibility()) {
            $repository->setVisibilityDraft($resource);
            $icon = 'fa-eye-slash';
        } else {
            $repository->setVisibilityPublished($resource);
        }

        $result = ['icon' => $icon];

        return new JsonResponse($result);
    }

    /**
     * @Route("/{tool}/{type}/{id}/delete", name="chamilo_core_resource_delete")
     */
    public function deleteAction(Request $request): Response
    {
        $em = $this->getDoctrine()->getManager();

        $id = $request->get('id');
        $resourceNode = $this->getDoctrine()->getRepository(ResourceNode::class)->find($id);
        $parentId = $resourceNode->getParent()->getId();

        $this->denyAccessUnlessGranted(
            ResourceNodeVoter::DELETE,
            $resourceNode,
            $this->trans('Unauthorised access to resource')
        );

        $children = $resourceNode->getChildren();

        if (!empty($children)) {
            /** @var ResourceNode $child */
            foreach ($children as $child) {
                $em->remove($child);
            }
        }

        $em->remove($resourceNode);
        $this->addFlash('success', $this->trans('Deleted').': '.$resourceNode->getSlug());
        $em->flush();

        $routeParams = $this->getResourceParams($request);
        $routeParams['id'] = $parentId;

        return $this->redirectToRoute('chamilo_core_resource_list', $routeParams);
    }

    /**
     * @Route("/{tool}/{type}/{id}/delete_mass", methods={"DELETE"}, name="chamilo_core_resource_delete_mass")
     */
    public function deleteMassAction($primaryKeys, $allPrimaryKeys, Request $request): Response
    {
        $em = $this->getDoctrine()->getManager();
        $repo = $this->getRepositoryFromRequest($request);

        $parentId = 0;
        foreach ($primaryKeys as $id) {
            $resource = $repo->find($id);
            $resourceNode = $resource->getResourceNode();

            if (null === $resourceNode) {
                continue;
            }

            $this->denyAccessUnlessGranted(
                ResourceNodeVoter::DELETE,
                $resourceNode,
                $this->trans('Unauthorised access to resource')
            );

            $parentId = $resourceNode->getParent()->getId();
            $em->remove($resource);
        }

        $this->addFlash('success', $this->trans('Deleted'));
        $em->flush();

        $routeParams = $this->getResourceParams($request);
        $routeParams['id'] = $parentId;

        return $this->redirectToRoute('chamilo_core_resource_list', $routeParams);
    }

    /**
     * Shows the associated resource file.
     *
     * @Route("/{tool}/{type}/{id}/view", methods={"GET"}, name="chamilo_core_resource_view_file")
     */
    public function viewAction(Request $request, RouterInterface $router): Response
    {
        $id = $request->get('id');
        $filter = $request->get('filter'); // See filters definitions in /config/services.yml
        $mode = $request->get('mode');
        $em = $this->getDoctrine();
        /** @var ResourceNode $resourceNode */
        $resourceNode = $em->getRepository('ChamiloCoreBundle:ResourceNode')->find($id);

        if (null === $resourceNode) {
            throw new FileNotFoundException('Resource not found');
        }

        $repo = $this->getRepositoryFromRequest($request);

        if ($repo instanceof ResourceWithLinkInterface) {
            $resource = $repo->getResourceFromResourceNode($resourceNode->getId());
            $url = $repo->getLink($resource, $router, $this->getCourseUrlQueryToArray());

            return $this->redirect($url);
        }

        return $this->showFile($request, $resourceNode, $mode, $filter);
    }

    /**
     * Shows the associated resource file.
     *
     * @Route("/{tool}/{type}/{id}/view_resource", methods={"GET"}, name="chamilo_core_resource_view_resource")
     */
    public function viewResourceAction(Request $request, RouterInterface $router): Response
    {
        $id = $request->get('id');
        $em = $this->getDoctrine();

        /** @var ResourceNode $resourceNode */
        $resourceNode = $em->getRepository('ChamiloCoreBundle:ResourceNode')->find($id);

        $this->denyAccessUnlessGranted(
            ResourceNodeVoter::VIEW,
            $resourceNode,
            $this->trans('Unauthorised access to resource')
        );

        $repository = $this->getRepositoryFromRequest($request);

        /** @var AbstractResource $resource */
        $resource = $repository->getResourceFromResourceNode($id);

        $tool = $request->get('tool');
        $type = $request->get('type');
        $this->setBreadCrumb($request, $resourceNode);

        $params = [
            'resource' => $resource,
            'tool' => $tool,
            'type' => $type,
        ];

        return $this->render($repository->getTemplates()->getFromAction(__FUNCTION__), $params);

        //return $this->showFile($request, $resourceNode, $mode, $filter);
    }

    /**
     * @Route("/{tool}/{type}/{id}/download", methods={"GET"}, name="chamilo_core_resource_download")
     */
    public function downloadAction(Request $request)
    {
        $resourceNodeId = (int) $request->get('id');
        $courseNode = $this->getCourse()->getResourceNode();

        $repo = $this->getRepositoryFromRequest($request);

        if (empty($resourceNodeId)) {
            $resourceNode = $courseNode;
        } else {
            $resourceNode = $repo->getResourceNodeRepository()->find($resourceNodeId);
        }

        $this->denyAccessUnlessGranted(
            ResourceNodeVoter::VIEW,
            $resourceNode,
            $this->trans('Unauthorised access to resource')
        );

        // If resource node has a file just download it. Don't download the children.
        if ($resourceNode->hasResourceFile()) {
            // Redirect to download single file.
            return $this->showFile($request, $resourceNode, 'download');
        }

        $zipName = $resourceNode->getSlug().'.zip';
        $rootNodePath = $resourceNode->getPathForDisplay();
        $resourceNodeRepo = $repo->getResourceNodeRepository();

        $criteria = Criteria::create()
            ->where(Criteria::expr()->neq('resourceFile', null)) // must have a file
            // ->andWhere(Criteria::expr()->eq('resourceType', $type))
        ;

        /** @var ArrayCollection|ResourceNode[] $children */
        /** @var QueryBuilder $children */
        $qb = $resourceNodeRepo->getChildrenQueryBuilder($resourceNode);
        $qb->addCriteria($criteria);
        $children = $qb->getQuery()->getResult();
        $count = count($children);
        if (0 === $count) {
            $params = $this->getResourceParams($request);
            $params['id'] = $resourceNodeId;

            $this->addFlash('warning', $this->trans('No files'));

            return $this->redirectToRoute('chamilo_core_resource_list', $params);
        }

        $response = new StreamedResponse(
            function () use ($rootNodePath, $zipName, $children, $repo) {
                // Define suitable options for ZipStream Archive.
                $options = new Archive();
                $options->setContentType('application/octet-stream');
                //initialise zipstream with output zip filename and options.
                $zip = new ZipStream($zipName, $options);

                /** @var ResourceNode $node */
                foreach ($children as $node) {
                    $stream = $repo->getResourceNodeFileStream($node);
                    $fileToDisplay = str_replace($rootNodePath, '', $node->getPathForDisplay());
                    $zip->addFileFromStream($fileToDisplay, $stream);
                }
                $zip->finish();
            }
        );

        $disposition = $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $zipName //Transliterator::transliterate($zipName)
        );
        $response->headers->set('Content-Disposition', $disposition);
        $response->headers->set('Content-Type', 'application/octet-stream');

        return $response;
    }

    /**
     * Upload form.
     *
     * @Route("/{tool}/{type}/{id}/upload", name="chamilo_core_resource_upload", methods={"GET", "POST"},
     *                                      options={"expose"=true})
     */
    public function uploadAction(Request $request, $tool, $type, $id): Response
    {
        $repository = $this->getRepositoryFromRequest($request);
        $resourceNode = $repository->getResourceNodeRepository()->find($id);

        $this->denyAccessUnlessGranted(
            ResourceNodeVoter::EDIT,
            $resourceNode,
            $this->trans('Unauthorised access to resource')
        );

        $this->setBreadCrumb($request, $resourceNode);

        $routeParams = $this->getResourceParams($request);
        $routeParams['tool'] = $tool;
        $routeParams['type'] = $type;
        $routeParams['id'] = $id;

        return $this->render($repository->getTemplates()->getFromAction(__FUNCTION__), $routeParams);
    }

    /**
     * @param string $fileType
     *
     * @return RedirectResponse|Response
     */
    private function createResource(Request $request, $fileType = 'file')
    {
        $resourceNodeParentId = $request->get('id');
        $repository = $this->getRepositoryFromRequest($request);

        // Default parent node is course.
        $parentNode = $this->getParentResourceNode($request);

        $this->setBreadCrumb($request, $parentNode);

        $this->denyAccessUnlessGranted(
            ResourceNodeVoter::CREATE,
            $parentNode,
            $this->trans('Unauthorised access to resource')
        );

        $form = $repository->getForm($this->container->get('form.factory'), null);
        $settings = $repository->getResourceSettings();

        if ('file' === $fileType && $settings->isAllowToSaveEditorToResourceFile()) {
            $resourceParams = $this->getResourceParams($request);
            $form->add(
                $this->fileContentName,
                CKEditorType::class,
                [
                    'mapped' => false,
                    'config' => [
                        'filebrowserImageBrowseRoute' => 'resources_filemanager',
                        'filebrowserImageBrowseRouteParameters' => $resourceParams,
                        'fullPage' => true,
                    ],
                ]
            );
        }

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $course = $this->getCourse()->getId();
            $course = $this->getDoctrine()->getRepository('ChamiloCoreBundle:Course')->find($course);
            $session = $this->getSession();

            /** @var ResourceInterface $newResource */
            $newResource = $repository->setResourceProperties($form, $course, $session, $fileType);

            $file = null;
            if ('file' === $fileType && $settings->isAllowToSaveEditorToResourceFile()) {
                $content = $form->get($this->fileContentName)->getViewData();
                $newResource->setTitle($newResource->getTitle().'.html');
                $fileName = $newResource->getTitle();

                $handle = tmpfile();
                fwrite($handle, $content);
                $meta = stream_get_meta_data($handle);
                $file = new UploadedFile($meta['uri'], $fileName, 'text/html', null, true);
            }

            $repository->addResourceToCourseWithParent(
                $newResource,
                $parentNode,
                ResourceLink::VISIBILITY_PUBLISHED,
                $this->getUser(),
                $course,
                $session,
                null,
                $file
            );

            $em->flush();

            // Loops all sharing options
            /*foreach ($shareList as $share) {
                $idList = [];
                if (isset($share['search'])) {
                    $idList = explode(',', $share['search']);
                }

                $resourceRight = null;
                if (isset($share['mask'])) {
                    $resourceRight = new ResourceRight();
                    $resourceRight
                        ->setMask($share['mask'])
                        ->setRole($share['role'])
                    ;
                }

                // Build links
                switch ($share['sharing']) {
                    case 'everyone':
                        $repository->addResourceToEveryone(
                            $resourceNode,
                            $resourceRight
                        );
                        break;
                    case 'course':
                        $repository->addResourceToCourse(
                            $resourceNode,
                            $course,
                            $resourceRight
                        );
                        break;
                    case 'session':
                        $repository->addResourceToSession(
                            $resourceNode,
                            $course,
                            $session,
                            $resourceRight
                        );
                        break;
                    case 'user':
                        // Only for me
                        if (isset($share['only_me'])) {
                            $repository->addResourceOnlyToMe($resourceNode);
                        } else {
                            // To other users
                            $repository->addResourceToUserList($resourceNode, $idList);
                        }
                        break;
                    case 'group':
                        // @todo
                        break;
                }*/
            //}
            $em->flush();
            $this->addFlash('success', $this->trans('Saved'));

            $params = $this->getResourceParams($request);
            $params['id'] = $resourceNodeParentId;

            return $this->redirectToRoute(
                'chamilo_core_resource_list',
                $params
            );
        }

        switch ($fileType) {
            case 'folder':
                $template = $repository->getTemplates()->getFromAction('newFolderAction');

                break;
            case 'file':
                $template = $repository->getTemplates()->getFromAction('newAction');

                break;
        }

        $routeParams = $this->getResourceParams($request);
        $routeParams['form'] = $form->createView();
        $routeParams['parent'] = $resourceNodeParentId;
        $routeParams['file_type'] = $fileType;

        return $this->render($template, $routeParams);
    }

    /**
     * @param string $mode
     * @param string $filter
     *
     * @return mixed|StreamedResponse
     */
    private function showFile(Request $request, ResourceNode $resourceNode, $mode = 'show', $filter = '')
    {
        $this->denyAccessUnlessGranted(
            ResourceNodeVoter::VIEW,
            $resourceNode,
            $this->trans('Unauthorised access to resource')
        );

        $repo = $this->getRepositoryFromRequest($request);
        $resourceFile = $resourceNode->getResourceFile();

        if (!$resourceFile) {
            throw new NotFoundHttpException($this->trans('File not found for resource'));
        }

        $fileName = $resourceNode->getSlug();
        $mimeType = $resourceFile->getMimeType();

        switch ($mode) {
            case 'download':
                $forceDownload = true;

                break;
            case 'show':
            default:
                $forceDownload = false;
                // If it's an image then send it to Glide.
                if (false !== strpos($mimeType, 'image')) {
                    $glide = $this->getGlide();
                    $server = $glide->getServer();
                    $params = $request->query->all();

                    // The filter overwrites the params from GET.
                    if (!empty($filter)) {
                        $params = $glide->getFilters()[$filter] ?? [];
                    }

                    // The image was cropped manually by the user, so we force to render this version,
                    // no matter other crop parameters.
                    $crop = $resourceFile->getCrop();
                    if (!empty($crop)) {
                        $params['crop'] = $crop;
                    }

                    $fileName = $repo->getResourceNodeRepository()->getFilename($resourceFile);

                    return $server->getImageResponse($fileName, $params);
                }

                break;
        }

        $stream = $repo->getResourceNodeFileStream($resourceNode);

        $response = new StreamedResponse(
            function () use ($stream): void {
                stream_copy_to_stream($stream, fopen('php://output', 'wb'));
            }
        );
        $disposition = $response->headers->makeDisposition(
            $forceDownload ? ResponseHeaderBag::DISPOSITION_ATTACHMENT : ResponseHeaderBag::DISPOSITION_INLINE,
            $fileName
        //Transliterator::transliterate($fileName)
        );
        $response->headers->set('Content-Disposition', $disposition);
        $response->headers->set('Content-Type', $mimeType ?: 'application/octet-stream');

        return $response;
    }
}
