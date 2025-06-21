<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller\Admin;

use Chamilo\CoreBundle\Controller\BaseController;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Entity\ResourceType;
use Chamilo\CoreBundle\Repository\ResourceFileRepository;
use Chamilo\CoreBundle\Repository\ResourceNodeRepository;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CoreBundle\Utils\AccessUrlUtil;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin')]
class AdminController extends BaseController
{
    private const ITEMS_PER_PAGE = 50;

    public function __construct(
        private readonly ResourceNodeRepository $resourceNodeRepository,
        private readonly AccessUrlUtil $accessUrlUtil
    ) {}

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/register-campus', name: 'admin_register_campus', methods: ['POST'])]
    public function registerCampus(Request $request, SettingsManager $settingsManager): Response
    {
        $requestData = $request->toArray();
        $doNotListCampus = (bool) $requestData['donotlistcampus'];

        $settingsManager->setUrl($this->accessUrlUtil->getCurrent());
        $settingsManager->updateSetting('platform.registered', 'true');

        $settingsManager->updateSetting(
            'platform.donotlistcampus',
            $doNotListCampus ? 'true' : 'false'
        );

        return new Response('', Response::HTTP_NO_CONTENT);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/files_info', name: 'admin_files_info', methods: ['GET'])]
    public function listFilesInfo(Request $request, ResourceFileRepository $resourceFileRepository): Response
    {
        $page = $request->query->getInt('page', 1);
        $search = $request->query->get('search', '');
        $offset = ($page - 1) * self::ITEMS_PER_PAGE;

        $files = $resourceFileRepository->searchFiles($search, $offset, self::ITEMS_PER_PAGE);
        $totalItems = $resourceFileRepository->countFiles($search);
        $totalPages = $totalItems > 0 ? ceil($totalItems / self::ITEMS_PER_PAGE) : 1;

        $fileUrls = [];
        $filePaths = [];
        foreach ($files as $file) {
            $resourceNode = $file->getResourceNode();
            if ($resourceNode) {
                $fileUrls[$file->getId()] = $this->resourceNodeRepository->getResourceFileUrl($resourceNode);
                $creator = $resourceNode->getCreator();
            } else {
                $fileUrls[$file->getId()] = null;
                $creator = null;
            }
            $filePaths[$file->getId()] = '/upload/resource'.$this->resourceNodeRepository->getFilename($file);
        }

        return $this->render('@ChamiloCore/Admin/files_info.html.twig', [
            'files' => $files,
            'fileUrls' => $fileUrls,
            'filePaths' => $filePaths,
            'totalPages' => $totalPages,
            'currentPage' => $page,
            'search' => $search,
        ]);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/resources_info', name: 'admin_resources_info', methods: ['GET'])]
    public function listResourcesInfo(Request $request, ResourceNodeRepository $resourceNodeRepo, EntityManagerInterface $em): Response
    {
        $resourceTypeId = $request->query->getInt('type');
        $resourceTypes = $em->getRepository(ResourceType::class)->findAll();

        $courses = [];
        if ($resourceTypeId > 0) {
            $resourceLinks = $em->getRepository(ResourceLink::class)->createQueryBuilder('rl')
                ->join('rl.resourceNode', 'rn')
                ->where('rn.resourceType = :type')
                ->setParameter('type', $resourceTypeId)
                ->getQuery()
                ->getResult()
            ;

            $seen = [];
            foreach ($resourceLinks as $link) {
                $course = $link->getCourse();
                $session = $link->getSession();
                $node = $link->getResourceNode();

                if (!$course) {
                    continue;
                }

                $key = $session
                    ? 's'.$session->getId().'-'.$course->getId()
                    : 'c'.$course->getId();

                if (!isset($seen[$key])) {
                    $seen[$key] = [
                        'type' => $session ? 'session' : 'course',
                        'id' => $session ? $session->getId() : $course->getId(),
                        'title' => $session ? $session->getTitle().' - '.$course->getTitle() : $course->getTitle(),
                        'url' => $session
                            ? '/course/'.$course->getId().'/home?sid='.$session->getId()
                            : '/course/'.$course->getId().'/home',
                        'count' => 0,
                        'items' => [],
                        'firstCreatedAt' => $node->getCreatedAt(),
                    ];
                }

                $seen[$key]['count']++;
                $seen[$key]['items'][] = $node->getTitle();

                if ($node->getCreatedAt() < $seen[$key]['firstCreatedAt']) {
                    $seen[$key]['firstCreatedAt'] = $node->getCreatedAt();
                }
            }

            $courses = array_values($seen);
            usort($courses, fn ($a, $b) => strnatcasecmp($a['title'], $b['title']));
        }

        return $this->render('@ChamiloCore/Admin/resources_info.html.twig', [
            'resourceTypes' => $resourceTypes,
            'selectedType' => $resourceTypeId,
            'courses' => $courses,
        ]);
    }
}
