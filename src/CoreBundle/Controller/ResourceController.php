<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller;

use Chamilo\CoreBundle\Entity\AbstractResource;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Entity\ResourceNode;
use Chamilo\CoreBundle\Form\Type\ResourceCommentType;
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
 * @author Julio Montoya <gugli100@gmail.com>.
 */
#[Route('/r')]
class ResourceController extends AbstractResourceController implements CourseControllerInterface
{
    use CourseControllerTrait;
    use ResourceControllerTrait;
    use ControllerTrait;

    private string $fileContentName = 'file_content';

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
            '@ChamiloCore/Resource/disk_space.html.twig',
            [
                'resourceNode' => $resourceNode,
                'labels' => $labels,
                'data' => $data,
            ]
        );
    }

    /**
     * Shows resource information.
     *
     * @Route("/{tool}/{type}/{id}/info", methods={"GET", "POST"}, name="chamilo_core_resource_info")
     */
    public function infoAction(Request $request): Response
    {
        $nodeId = (int) $request->get('id');
        $repository = $this->getRepositoryFromRequest($request);

        $resource = $repository->getResourceFromResourceNode($nodeId);
        $this->denyAccessUnlessValidResource($resource);

        $resourceNode = $resource->getResourceNode();

        $this->denyAccessUnlessGranted(
            ResourceNodeVoter::VIEW,
            $resourceNode,
            $this->trans(sprintf('Unauthorised access to resource #%s', $nodeId))
        );

        //$this->setBreadCrumb($request, $resourceNode);

        $tool = $request->get('tool');
        $type = $request->get('type');

        $form = $this->createForm(ResourceCommentType::class, null);

        $params = [
            'resource' => $resource,
            'course' => $this->getCourse(),
            'tool' => $tool,
            'type' => $type,
            'comment_form' => $form->createView(),
        ];

        return $this->render(
            '@ChamiloCore/Resource/info.html.twig',
            $params
        );
    }

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
     * View file of a resource node.
     */
    #[Route('/{tool}/{type}/{id}/view', name: 'chamilo_core_resource_view', methods: ['GET'])]
    public function viewAction(Request $request): Response
    {
        $id = $request->get('id');
        $filter = (string) $request->get('filter'); // See filters definitions in /config/services.yml.
        $resourceNode = $this->getResourceNodeRepository()->findOneBy(['uuid' => $id]);

        if (null === $resourceNode) {
            throw new FileNotFoundException($this->trans('Resource not found'));
        }

        return $this->processFile($request, $resourceNode, 'show', $filter);
    }

    /**
     * Redirect resource to link.
     *
     * @return RedirectResponse|void
     */
    #[Route('/{tool}/{type}/{id}/link', name: 'chamilo_core_resource_link', methods: ['GET'])]
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
     * @return RedirectResponse|StreamedResponse
     */
    #[Route('/{tool}/{type}/{id}/download', name: 'chamilo_core_resource_download', methods: ['GET'])]
    public function downloadAction(Request $request)
    {
        $id = $request->get('id');
        $resourceNode = $this->getResourceNodeRepository()->findOneBy(['uuid' => $id]);

        if (null === $resourceNode) {
            throw new FileNotFoundException($this->trans('Resource not found'));
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

                    $response = $server->getImageResponse($fileName, $params);

                    $disposition = $response->headers->makeDisposition(
                        ResponseHeaderBag::DISPOSITION_INLINE,
                        basename($fileName)
                    );
                    $response->headers->set('Content-Disposition', $disposition);

                    return $response;
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
                    if ('true' === $this->getSettingsManager()->getSetting('course.enable_bootstrap_in_documents_html')) {
                        // It adds the bootstrap and awesome css
                        $links = '<link href="'.api_get_path(WEB_PATH).'libs/bootstrap/bootstrap.min.css" rel="stylesheet">';
                        $links .= '<link href="'.api_get_path(WEB_PATH).'libs/bootstrap/font-awesome.min.css" rel="stylesheet">';
                        // Insert inside the head tag.
                        $content = str_replace('</head>', $links.'</head>', $content);
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
