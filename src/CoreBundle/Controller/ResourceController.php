<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Entity\ResourceNode;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Repository\ResourceNodeRepository;
use Chamilo\CoreBundle\Repository\ResourceWithLinkInterface;
use Chamilo\CoreBundle\Repository\TrackEDownloadsRepository;
use Chamilo\CoreBundle\Security\Authorization\Voter\ResourceNodeVoter;
use Chamilo\CoreBundle\ServiceHelper\UserHelper;
use Chamilo\CoreBundle\Tool\ToolChain;
use Chamilo\CoreBundle\Traits\ControllerTrait;
use Chamilo\CoreBundle\Traits\CourseControllerTrait;
use Chamilo\CoreBundle\Traits\GradebookControllerTrait;
use Chamilo\CoreBundle\Traits\ResourceControllerTrait;
use Chamilo\CourseBundle\Controller\CourseControllerInterface;
use Chamilo\CourseBundle\Entity\CTool;
use Chamilo\CourseBundle\Repository\CLinkRepository;
use Chamilo\CourseBundle\Repository\CShortcutRepository;
use Chamilo\CourseBundle\Repository\CToolRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Serializer\SerializerInterface;
use ZipStream\Option\Archive;
use ZipStream\ZipStream;

/**
 * @author Julio Montoya <gugli100@gmail.com>.
 */
#[Route('/r')]
class ResourceController extends AbstractResourceController implements CourseControllerInterface
{
    use ControllerTrait;
    use CourseControllerTrait;
    use GradebookControllerTrait;
    use ResourceControllerTrait;

    public function __construct(
        private readonly UserHelper $userHelper,
        private readonly ResourceNodeRepository $resourceNodeRepository,
    ) {}

    #[Route(path: '/{tool}/{type}/{id}/disk_space', methods: ['GET', 'POST'], name: 'chamilo_core_resource_disk_space')]
    public function diskSpace(Request $request): Response
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

            $labels[] = $course->getTitle().' - '.$session->getTitle();
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
            $labels[] = $course->getTitle().' - '.$group->getTitle();
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
     * View file of a resource node.
     */
    #[Route('/{tool}/{type}/{id}/view', name: 'chamilo_core_resource_view', methods: ['GET'])]
    public function view(Request $request, TrackEDownloadsRepository $trackEDownloadsRepository): Response
    {
        $id = $request->get('id');
        $filter = (string) $request->get('filter'); // See filters definitions in /config/services.yml.
        $resourceNode = $this->getResourceNodeRepository()->findOneBy(['uuid' => $id]);

        if (null === $resourceNode) {
            throw new FileNotFoundException($this->trans('Resource not found'));
        }

        $user = $this->userHelper->getCurrent();
        $firstResourceLink = $resourceNode->getResourceLinks()->first();
        $firstResourceFile = $resourceNode->getResourceFiles()->first();
        if ($firstResourceLink && $user && $firstResourceFile) {
            $url = $firstResourceFile->getOriginalName();
            $trackEDownloadsRepository->saveDownload($user, $firstResourceLink, $url);
        }

        $cid = (int) $request->query->get('cid');
        $sid = (int) $request->query->get('sid');
        $allUserInfo = null;
        if ($cid && $user) {
            $allUserInfo = $this->getAllInfoToCertificate(
                $user->getId(),
                $cid,
                $sid,
                false
            );
        }

        return $this->processFile($request, $resourceNode, 'show', $filter, $allUserInfo);
    }

    /**
     * Redirect resource to link.
     *
     * @return RedirectResponse|void
     */
    #[Route('/{tool}/{type}/{id}/link', name: 'chamilo_core_resource_link', methods: ['GET'])]
    public function link(Request $request, RouterInterface $router, CLinkRepository $cLinkRepository): RedirectResponse
    {
        $tool = $request->get('tool');
        $type = $request->get('type');
        $id = $request->get('id');
        $resourceNode = $this->getResourceNodeRepository()->find($id);

        if (null === $resourceNode) {
            throw new FileNotFoundException('Resource not found');
        }

        if ('course_tool' === $tool && 'links' === $type) {
            $cLink = $cLinkRepository->findOneBy(['resourceNode' => $resourceNode]);
            if ($cLink) {
                $url = $cLink->getUrl();

                return $this->redirect($url);
            }

            throw new FileNotFoundException('CLink not found for the given resource node');
        } else {
            $repo = $this->getRepositoryFromRequest($request);
            if ($repo instanceof ResourceWithLinkInterface) {
                $resource = $repo->getResourceFromResourceNode($resourceNode->getId());
                $url = $repo->getLink($resource, $router, $this->getCourseUrlQueryToArray());

                return $this->redirect($url);
            }

            $this->abort('No redirect');
        }
    }

    /**
     * Download file of a resource node.
     */
    #[Route('/{tool}/{type}/{id}/download', name: 'chamilo_core_resource_download', methods: ['GET'])]
    public function download(Request $request, TrackEDownloadsRepository $trackEDownloadsRepository): Response
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
            $user = $this->userHelper->getCurrent();
            $firstResourceLink = $resourceNode->getResourceLinks()->first();
            if ($firstResourceLink) {
                $url = $resourceNode->getResourceFiles()->first()->getOriginalName();
                $trackEDownloadsRepository->saveDownload($user, $firstResourceLink, $url);
            }

            // Redirect to download single file.
            return $this->processFile($request, $resourceNode, 'download');
        }

        $zipName = $resourceNode->getSlug().'.zip';
        // $rootNodePath = $resourceNode->getPathForDisplay();
        $resourceNodeRepo = $repo->getResourceNodeRepository();
        $type = $repo->getResourceType();

        $criteria = Criteria::create()
            ->where(Criteria::expr()->neq('resourceFiles', null)) // must have a file
            ->andWhere(Criteria::expr()->eq('resourceType', $type)) // only download same type
        ;

        $qb = $resourceNodeRepo->getChildrenQueryBuilder($resourceNode);
        $qbAlias = $qb->getRootAliases()[0];

        $qb
            ->leftJoin(\sprintf('%s.resourceFiles', $qbAlias), 'resourceFiles') // must have a file
            ->addCriteria($criteria)
        ;

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
                // initialise zipstream with output zip filename and options.
                $zip = new ZipStream($zipName, $options);

                /** @var ResourceNode $node */
                foreach ($children as $node) {
                    $stream = $repo->getResourceNodeFileStream($node);
                    $fileName = $node->getResourceFiles()->first()->getOriginalName();
                    // $fileToDisplay = basename($node->getPathForDisplay());
                    // $fileToDisplay = str_replace($rootNodePath, '', $node->getPathForDisplay());
                    // error_log($fileToDisplay);
                    $zip->addFileFromStream($fileName, $stream);
                }
                $zip->finish();
            }
        );

        // Convert the file name to ASCII using iconv
        $zipName = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $zipName);

        $disposition = $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $zipName // Transliterator::transliterate($zipName)
        );
        $response->headers->set('Content-Disposition', $disposition);
        $response->headers->set('Content-Type', 'application/octet-stream');

        return $response;
    }

    #[Route('/{tool}/{type}/{id}/change_visibility', name: 'chamilo_core_resource_change_visibility', methods: ['POST'])]
    public function changeVisibility(
        Request $request,
        EntityManagerInterface $entityManager,
        SerializerInterface $serializer,
        Security $security,
    ): Response {
        $user = $security->getUser();
        $isAdmin = ($user->hasRole('ROLE_SUPER_ADMIN') || $user->hasRole('ROLE_ADMIN'));
        $isCourseTeacher = ($user->hasRole('ROLE_CURRENT_COURSE_TEACHER') || $user->hasRole('ROLE_CURRENT_COURSE_SESSION_TEACHER'));

        if (!($isCourseTeacher || $isAdmin)) {
            throw new AccessDeniedHttpException();
        }

        $session = null;
        if ($this->getSession()) {
            $sessionId = $this->getSession()->getId();
            $session = $entityManager->getRepository(Session::class)->find($sessionId);
        }
        $courseId = $this->getCourse()->getId();
        $course = $entityManager->getRepository(Course::class)->find($courseId);
        $id = $request->attributes->getInt('id');
        $resourceNode = $this->getResourceNodeRepository()->findOneBy(['id' => $id]);

        if (null === $resourceNode) {
            throw new NotFoundHttpException($this->trans('Resource not found'));
        }

        $link = null;
        foreach ($resourceNode->getResourceLinks() as $resourceLink) {
            if ($resourceLink->getSession() === $session) {
                $link = $resourceLink;

                break;
            }
        }

        if (null === $link) {
            $link = new ResourceLink();
            $link->setResourceNode($resourceNode)
                ->setSession($session)
                ->setCourse($course)
                ->setVisibility(ResourceLink::VISIBILITY_DRAFT)
            ;
            $entityManager->persist($link);
        } else {
            if (ResourceLink::VISIBILITY_PUBLISHED === $link->getVisibility()) {
                $link->setVisibility(ResourceLink::VISIBILITY_DRAFT);
            } else {
                $link->setVisibility(ResourceLink::VISIBILITY_PUBLISHED);
            }
        }

        $entityManager->flush();

        $json = $serializer->serialize(
            $link,
            'json',
            [
                'groups' => ['ctool:read'],
            ]
        );

        return JsonResponse::fromJsonString($json);
    }

    #[Route(
        '/{tool}/{type}/change_visibility/{visibility}',
        name: 'chamilo_core_resource_change_visibility_all',
        methods: ['POST']
    )]
    public function changeVisibilityAll(
        Request $request,
        CToolRepository $toolRepository,
        CShortcutRepository $shortcutRepository,
        ToolChain $toolChain,
        EntityManagerInterface $entityManager,
        Security $security
    ): Response {
        $user = $security->getUser();
        $isAdmin = ($user->hasRole('ROLE_SUPER_ADMIN') || $user->hasRole('ROLE_ADMIN'));
        $isCourseTeacher = ($user->hasRole('ROLE_CURRENT_COURSE_TEACHER') || $user->hasRole('ROLE_CURRENT_COURSE_SESSION_TEACHER'));

        if (!($isCourseTeacher || $isAdmin)) {
            throw new AccessDeniedHttpException();
        }

        $visibility = $request->attributes->get('visibility');

        $session = null;
        if ($this->getSession()) {
            $sessionId = $this->getSession()->getId();
            $session = $entityManager->getRepository(Session::class)->find($sessionId);
        }
        $courseId = $this->getCourse()->getId();
        $course = $entityManager->getRepository(Course::class)->find($courseId);

        $result = $toolRepository->getResourcesByCourse($course, $session)
            ->addSelect('tool')
            ->innerJoin('resource.tool', 'tool')
            ->getQuery()
            ->getResult()
        ;

        $skipTools = ['course_tool', 'chat', 'notebook', 'wiki'];

        /** @var CTool $item */
        foreach ($result as $item) {
            if (\in_array($item->getTitle(), $skipTools, true)) {
                continue;
            }
            $toolModel = $toolChain->getToolFromName($item->getTool()->getTitle());

            if (!\in_array($toolModel->getCategory(), ['authoring', 'interaction'], true)) {
                continue;
            }

            $resourceNode = $item->getResourceNode();

            /** @var ResourceLink $link */
            $link = null;
            foreach ($resourceNode->getResourceLinks() as $resourceLink) {
                if ($resourceLink->getSession() === $session) {
                    $link = $resourceLink;

                    break;
                }
            }

            if (null === $link) {
                $link = new ResourceLink();
                $link->setResourceNode($resourceNode)
                    ->setSession($session)
                    ->setCourse($course)
                    ->setVisibility(ResourceLink::VISIBILITY_DRAFT)
                ;
                $entityManager->persist($link);
            }

            if ('show' === $visibility) {
                $link->setVisibility(ResourceLink::VISIBILITY_PUBLISHED);
            } elseif ('hide' === $visibility) {
                $link->setVisibility(ResourceLink::VISIBILITY_DRAFT);
            }
        }

        $entityManager->flush();

        return new Response(null, Response::HTTP_NO_CONTENT);
    }

    private function processFile(Request $request, ResourceNode $resourceNode, string $mode = 'show', string $filter = '', ?array $allUserInfo = null): mixed
    {
        $this->denyAccessUnlessGranted(
            ResourceNodeVoter::VIEW,
            $resourceNode,
            $this->trans('Unauthorised view access to resource')
        );

        $resourceFile = $resourceNode->getResourceFiles()->first();

        if (!$resourceFile) {
            throw $this->createNotFoundException($this->trans('File not found for resource'));
        }

        $fileName = $resourceFile->getOriginalName();
        $fileSize = $resourceFile->getSize();
        $mimeType = $resourceFile->getMimeType();
        [$start, $end, $length] = $this->getRange($request, $fileSize);
        $resourceNodeRepo = $this->getResourceNodeRepository();

        // Convert the file name to ASCII using iconv
        $fileName = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $fileName);

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

                    $filePath = $resourceNodeRepo->getFilename($resourceFile);

                    $response = $server->getImageResponse($filePath, $params);

                    $disposition = $response->headers->makeDisposition(
                        ResponseHeaderBag::DISPOSITION_INLINE,
                        $fileName
                    );
                    $response->headers->set('Content-Disposition', $disposition);

                    return $response;
                }

                // Modify the HTML content before displaying it.
                if (str_contains($mimeType, 'html')) {
                    $content = $resourceNodeRepo->getResourceNodeFileContent($resourceNode);

                    if (null !== $allUserInfo) {
                        $tagsToReplace = $allUserInfo[0];
                        $replacementValues = $allUserInfo[1];
                        $content = str_replace($tagsToReplace, $replacementValues, $content);
                    }

                    $response = new Response();
                    $disposition = $response->headers->makeDisposition(
                        ResponseHeaderBag::DISPOSITION_INLINE,
                        $fileName
                    );
                    $response->headers->set('Content-Disposition', $disposition);
                    $response->headers->set('Content-Type', 'text/html');

                    // @todo move into a function/class
                    if ('true' === $this->getSettingsManager()->getSetting('editor.translate_html')) {
                        $user = $this->userHelper->getCurrent();
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

                    return $response;
                }

                break;
        }

        $response = new StreamedResponse(
            function () use ($resourceNode, $start, $length): void {
                $this->streamFileContent($resourceNode, $start, $length);
            }
        );

        $disposition = $response->headers->makeDisposition(
            $forceDownload ? ResponseHeaderBag::DISPOSITION_ATTACHMENT : ResponseHeaderBag::DISPOSITION_INLINE,
            $fileName
        );
        $response->headers->set('Content-Disposition', $disposition);
        $response->headers->set('Content-Type', $mimeType ?: 'application/octet-stream');
        $response->headers->set('Content-Length', (string) $length);
        $response->headers->set('Accept-Ranges', 'bytes');
        $response->headers->set('Content-Range', "bytes $start-$end/$fileSize");
        $response->setStatusCode(
            $start > 0 || $end < $fileSize - 1 ? Response::HTTP_PARTIAL_CONTENT : Response::HTTP_OK
        );

        return $response;
    }

    private function getRange(Request $request, int $fileSize): array
    {
        $range = $request->headers->get('Range');

        if ($range) {
            [, $range] = explode('=', $range, 2);
            [$start, $end] = explode('-', $range);

            $start = (int) $start;
            $end = ('' === $end) ? $fileSize - 1 : (int) $end;

            $length = $end - $start + 1;
        } else {
            $start = 0;
            $end = $fileSize - 1;
            $length = $fileSize;
        }

        return [$start, $end, $length];
    }

    private function streamFileContent(ResourceNode $resourceNode, int $start, int $length): void
    {
        $stream = $this->resourceNodeRepository->getResourceNodeFileStream($resourceNode);

        fseek($stream, $start);

        $bytesSent = 0;

        while ($bytesSent < $length && !feof($stream)) {
            $buffer = fread($stream, min(1024 * 8, $length - $bytesSent));

            echo $buffer;

            $bytesSent += \strlen($buffer);
        }

        fclose($stream);
    }
}
