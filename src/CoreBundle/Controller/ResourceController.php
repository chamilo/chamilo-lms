<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller;

use Chamilo\CoreBundle\Entity\AbstractResource;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ResourceInterface;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Entity\ResourceNode;
use Chamilo\CoreBundle\Form\Type\ResourceCommentType;
use Chamilo\CoreBundle\Repository\Node\IllustrationRepository;
use Chamilo\CoreBundle\Repository\ResourceWithLinkInterface;
use Chamilo\CoreBundle\Security\Authorization\Voter\ResourceNodeVoter;
use Chamilo\CoreBundle\Traits\ControllerTrait;
use Chamilo\CoreBundle\Traits\CourseControllerTrait;
use Chamilo\CoreBundle\Traits\ResourceControllerTrait;
use Chamilo\CourseBundle\Controller\CourseControllerInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;
use ZipStream\Option\Archive;
use ZipStream\ZipStream;

/**
 * Class ResourceController.
 *
 * @Route("/r")
 *
 * @author Julio Montoya <gugli100@gmail.com>.
 */
class ResourceController extends AbstractResourceController implements CourseControllerInterface
{
    use CourseControllerTrait;
    use ResourceControllerTrait;
    use ControllerTrait;

    private string $fileContentName = 'file_content';

    /**
     * @deprecated Use Vue
     *
     * @Route("/{tool}/{type}/{id}/new_folder", methods={"GET", "POST"}, name="chamilo_core_resource_new_folder")
     */
    /*public function newFolderAction(Request $request): Response
    {
        return $this->createResource($request, 'folder');
    }*/

    /**
     * @deprecated Use Vue
     *
     * @Route("/{tool}/{type}/{id}/new", methods={"GET", "POST"}, name="chamilo_core_resource_new")
     */
    /*public function newAction(Request $request): Response
    {
        return $this->createResource($request, 'file');
    }*/

    /**
     * @Route("/{tool}/{type}/{id}/disk_space", methods={"GET", "POST"}, name="chamilo_core_resource_disk_space")
     */
    public function diskSpaceAction(Request $request): Response
    {
        $nodeId = $request->get('id');
        $repository = $this->getRepositoryFromRequest($request);

        /** @var ResourceNode $resourceNode */
        $resourceNode = $repository->getResourceNodeRepository()->find($nodeId);

        $this->denyAccessUnlessGranted(
            ResourceNodeVoter::VIEW,
            $resourceNode,
            $this->trans('Unauthorised access to resource')
        );

        $this->setBreadCrumb($request, $resourceNode);

        $course = $this->getCourse();
        $totalSize = 0;
        if (null !== $course) {
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

        /*$groups = $course->getGroups();
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
        }*/

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
     * @deprecated Use Vue
     *
     * @Route("/{tool}/{type}/{id}/edit", methods={"GET", "POST"})
     */
    public function editAction(Request $request, IllustrationRepository $illustrationRepo): Response
    {
        $resourceNodeId = $request->get('id');

        $repository = $this->getRepositoryFromRequest($request);
        $resource = $repository->getResourceFromResourceNode($resourceNodeId);
        $this->denyAccessUnlessValidResource($resource);
        $settings = $repository->getResourceSettings();
        $resourceNode = $resource->getResourceNode();

        $this->denyAccessUnlessGranted(
            ResourceNodeVoter::EDIT,
            $resourceNode,
            $this->trans('Unauthorised access to resource')
        );

        $this->setBreadCrumb($request, $resourceNode);
        $resourceNodeParentId = $resourceNode->getId();

        $routeParams = $this->getResourceParams($request);
        $routeParams['id'] = $resourceNodeParentId;

        $form = $repository->getForm($this->container->get('form.factory'), $resource);

        if ($resourceNode->hasEditableTextContent() && $settings->isAllowToSaveEditorToResourceFile()) {
            /*$form->add(
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
            $form->get($this->fileContentName)->setData($content);*/
        }

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var AbstractResource|ResourceInterface $newResource */
            $newResource = $form->getData();

            if ($form->has($this->fileContentName)) {
                $data = $form->get($this->fileContentName)->getData();
                $repository->updateResourceFileContent($newResource, $data);
            }

            $repository->updateNodeForResource($newResource);

            if ($form->has('illustration')) {
                $illustration = $form->get('illustration')->getData();
                if ($illustration) {
                    $illustrationRepo->addIllustration($newResource, $this->getUser(), $illustration);
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
     * @deprecated use Vue
     *
     * Shows a resource information
     *
     * @Route("/{tool}/{type}/{id}/info", methods={"GET", "POST"}, name="chamilo_core_resource_info")
     */
    /*public function infoAction(Request $request): Response
    {
        $nodeId = $request->get('id');
        $repository = $this->getRepositoryFromRequest($request);

        $resource = $repository->getResourceFromResourceNode($nodeId);
        $this->denyAccessUnlessValidResource($resource);
        $resourceNode = $resource->getResourceNode();

        $this->denyAccessUnlessGranted(
            ResourceNodeVoter::VIEW,
            $resourceNode,
            $this->trans(sprintf('Unauthorised access to resource #%s', $nodeId))
        );

        $this->setBreadCrumb($request, $resourceNode);

        $tool = $request->get('tool');
        $type = $request->get('type');

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
    }*/

    /**
     * Preview a file. Mostly used when using a modal.
     *
     * @Route("/{tool}/{type}/{id}/preview", methods={"GET"}, name="chamilo_core_resource_preview")
     */
    /*public function previewAction(Request $request): Response
    {
        $nodeId = $request->get('id');
        $repository = $this->getRepositoryFromRequest($request);

        $resource = $repository->getResourceFromResourceNode($nodeId);
        $this->denyAccessUnlessValidResource($resource);

        $resourceNode = $resource->getResourceNode();
        $this->denyAccessUnlessGranted(
            ResourceNodeVoter::VIEW,
            $resourceNode,
            $this->trans('Unauthorised access to resource')
        );

        $this->setBreadCrumb($request, $resourceNode);

        $tool = $request->get('tool');
        $type = $request->get('type');

        $params = [
            'resource' => $resource,
            'tool' => $tool,
            'type' => $type,
        ];

        return $this->render($repository->getTemplates()->getFromAction(__FUNCTION__), $params);
    }*/

    /**
     * @deprecated use vue
     *
     * @Route("/{tool}/{type}/{id}/change_visibility", name="chamilo_core_resource_change_visibility")
     */
    public function changeVisibilityAction(Request $request): Response
    {
        $id = (int) $request->get('id');

        $repository = $this->getRepositoryFromRequest($request);

        $resource = $repository->getResourceFromResourceNode($id);
        $this->denyAccessUnlessValidResource($resource);
        /** @var AbstractResource $resource */
        $resourceNode = $resource->getResourceNode();

        $this->denyAccessUnlessGranted(
            ResourceNodeVoter::EDIT,
            $resourceNode,
            $this->trans('Unauthorised access to resource')
        );

        if ($this->hasCourse()) {
            $link = $resource->getFirstResourceLinkFromCourseSession($this->getCourse(), $this->getSession());
        } else {
            $link = $resource->getFirstResourceLink();
        }

        // Use repository to change settings easily.
        if ($link && ResourceLink::VISIBILITY_PUBLISHED === $link->getVisibility()) {
            $repository->setVisibilityDraft($resource);
        } else {
            $repository->setVisibilityPublished($resource);
        }

        $result = [
            'visibility' => $link->getVisibility(),
            'ok' => true,
        ];

        return new JsonResponse($result);
    }

    /**
     * @deprecated Use Vue + api platform
     *
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
     * Shows the associated resource file.
     *
     * @deprecated use vue
     *
     * @Route("/{tool}/{type}/{id}/view_resource", methods={"GET"}, name="chamilo_core_resource_view_resource")
     */
    /*public function viewResourceAction(Request $request, RouterInterface $router): Response
    {
        $id = $request->get('id');

        $resourceNode = $this->getResourceNodeRepository()->find($id);

        if (null === $resourceNode) {
            throw new FileNotFoundException('Resource not found');
        }

        $this->denyAccessUnlessGranted(
            ResourceNodeVoter::VIEW,
            $resourceNode,
            $this->trans('Unauthorised access to resource')
        );

        $repository = $this->getRepositoryFromRequest($request);

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
    }*/

    /**
     * View file of a resource node.
     *
     * @Route("/{tool}/{type}/{id}/view", methods={"GET"}, name="chamilo_core_resource_view")
     */
    public function viewAction(Request $request): Response
    {
        $id = $request->get('id');
        $filter = (string) $request->get('filter'); // See filters definitions in /config/services.yml.
        $resourceNode = $this->getResourceNodeRepository()->find($id);

        if (null === $resourceNode) {
            throw new FileNotFoundException('Resource not found');
        }

        return $this->processFile($request, $resourceNode, 'show', $filter);
    }

    /**
     * Redirect resource to link.
     *
     * @Route("/{tool}/{type}/{id}/link", methods={"GET"}, name="chamilo_core_resource_link")
     *
     * @return RedirectResponse|void
     */
    public function linkAction(Request $request, RouterInterface $router)
    {
        $id = $request->get('id');
        $resourceNode = $this->getResourceNodeRepository()->find($id);

        if (null === $resourceNode) {
            throw new FileNotFoundException('Resource not found');
        }

        $repo = $this->getRepositoryFromRequest($request);
        if ($repo instanceof ResourceWithLinkInterface) {
            $resource = $repo->getResourceFromResourceNode($resourceNode->getId());
            $url = $repo->getLink($resource, $router, $this->getCourseUrlQueryToArray());

            return $this->redirect($url);
        }

        $this->abort('No redirect');
    }

    /**
     * Download file of a resource node.
     *
     * @Route("/{tool}/{type}/{id}/download", methods={"GET"}, name="chamilo_core_resource_download")
     *
     * @return RedirectResponse|StreamedResponse
     */
    public function downloadAction(Request $request)
    {
        $id = (int) $request->get('id');
        $resourceNode = $this->getResourceNodeRepository()->find($id);

        if (null === $resourceNode) {
            throw new FileNotFoundException('Resource not found');
        }

        $repo = $this->getRepositoryFromRequest($request);

        $this->denyAccessUnlessGranted(
            ResourceNodeVoter::VIEW,
            $resourceNode,
            $this->trans('Unauthorised access to resource')
        );

        // If resource node has a file just download it. Don't download the children.
        if ($resourceNode->hasResourceFile()) {
            // Redirect to download single file.
            return $this->processFile($request, $resourceNode, 'download');
        }

        $zipName = $resourceNode->getSlug().'.zip';
        //$rootNodePath = $resourceNode->getPathForDisplay();
        $resourceNodeRepo = $repo->getResourceNodeRepository();
        $type = $repo->getResourceType();

        $criteria = Criteria::create()
            ->where(Criteria::expr()->neq('resourceFile', null)) // must have a file
            ->andWhere(Criteria::expr()->eq('resourceType', $type)) // only download same type
        ;

        $qb = $resourceNodeRepo->getChildrenQueryBuilder($resourceNode);
        $qb->addCriteria($criteria);
        /** @var ArrayCollection|ResourceNode[] $children */
        $children = $qb->getQuery()->getResult();
        $count = \count($children);
        if (0 === $count) {
            $params = $this->getResourceParams($request);
            $params['id'] = $id;

            $this->addFlash('warning', $this->trans('No files'));

            return $this->redirectToRoute('chamilo_core_resource_list', $params);
        }

        $response = new StreamedResponse(
            function () use ($zipName, $children, $repo): void {
                // Define suitable options for ZipStream Archive.
                $options = new Archive();
                $options->setContentType('application/octet-stream');
                //initialise zipstream with output zip filename and options.
                $zip = new ZipStream($zipName, $options);

                /** @var ResourceNode $node */
                foreach ($children as $node) {
                    $stream = $repo->getResourceNodeFileStream($node);
                    $fileName = $node->getResourceFile()->getOriginalName();
                    //$fileToDisplay = basename($node->getPathForDisplay());
                    //$fileToDisplay = str_replace($rootNodePath, '', $node->getPathForDisplay());
                    //error_log($fileToDisplay);
                    $zip->addFileFromStream($fileName, $stream);
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
     * @return mixed|StreamedResponse
     */
    private function processFile(Request $request, ResourceNode $resourceNode, string $mode = 'show', string $filter = '')
    {
        $this->denyAccessUnlessGranted(
            ResourceNodeVoter::VIEW,
            $resourceNode,
            $this->trans('Unauthorised view access to resource')
        );

        $resourceFile = $resourceNode->getResourceFile();

        if (null === $resourceFile) {
            throw new NotFoundHttpException($this->trans('File not found for resource'));
        }

        $fileName = $resourceNode->getResourceFile()->getOriginalName();
        $mimeType = $resourceFile->getMimeType();
        $resourceNodeRepo = $this->getResourceNodeRepository();

        switch ($mode) {
            case 'download':
                $forceDownload = true;

                break;
            case 'show':
            default:
                $forceDownload = false;
                // If it's an image then send it to Glide.
                if (str_contains($mimeType, 'image')) {
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

                    $fileName = $resourceNodeRepo->getFilename($resourceFile);

                    return $server->getImageResponse($fileName, $params);
                }

                // Modify the HTML content before displaying it.
                if (str_contains($mimeType, 'html')) {
                    $content = $resourceNodeRepo->getResourceNodeFileContent($resourceNode);

                    $response = new Response();
                    $disposition = $response->headers->makeDisposition(
                        ResponseHeaderBag::DISPOSITION_INLINE,
                        $fileName
                    );
                    $response->headers->set('Content-Disposition', $disposition);
                    $response->headers->set('Content-Type', 'text/html');

                    /*$crawler = new Crawler();
                    $crawler->addHtmlContent($content);
                    var_dump($crawler->filter('head')->count());
                    $head = $crawler->filter('head');
                    var_dump($head->html());exit;*/

                    // @todo move into a function/class
                    if ('true' === $this->getSettingsManager()->getSetting('editor.translate_html')) {
                        $user = $this->getUser();
                        if (null !== $user) {
                            // Overwrite user_json, otherwise it will be loaded by the TwigListener.php
                            $userJson = json_encode(['locale' => $user->getLocale()]);
                            $js = $this->renderView(
                                '@ChamiloCore/Layout/document.html.twig',
                                ['breadcrumb' => '', 'user_json' => $userJson]
                            );
                            // Insert inside the head tag.
                            $content = str_replace('</head>', $js.'</head>', $content);
                        }
                    }

                    $response->setContent($content);
                    /*$contents = $this->renderView('@ChamiloCore/Resource/view_html.twig', [
                        'category' => '...',
                    ]);*/

                    return $response;
                }

                break;
        }

        $stream = $resourceNodeRepo->getResourceNodeFileStream($resourceNode);

        $response = new StreamedResponse(
            function () use ($stream): void {
                stream_copy_to_stream($stream, fopen('php://output', 'wb'));
            }
        );

        //Transliterator::transliterate($fileName)
        $disposition = $response->headers->makeDisposition(
            $forceDownload ? ResponseHeaderBag::DISPOSITION_ATTACHMENT : ResponseHeaderBag::DISPOSITION_INLINE,
            $fileName
        );
        $response->headers->set('Content-Disposition', $disposition);
        $response->headers->set('Content-Type', $mimeType ?: 'application/octet-stream');

        return $response;
    }
}
