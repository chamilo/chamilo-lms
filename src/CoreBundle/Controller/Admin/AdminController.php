<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller\Admin;

use Chamilo\CoreBundle\Component\Composer\ScriptHandler;
use Chamilo\CoreBundle\Controller\BaseController;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Entity\ResourceType;
use Chamilo\CoreBundle\Helpers\AccessUrlHelper;
use Chamilo\CoreBundle\Helpers\QueryCacheHelper;
use Chamilo\CoreBundle\Helpers\TempUploadHelper;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
use Chamilo\CoreBundle\Repository\ResourceFileRepository;
use Chamilo\CoreBundle\Repository\ResourceNodeRepository;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
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
        private readonly AccessUrlHelper $accessUrlHelper
    ) {}

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/register-campus', name: 'admin_register_campus', methods: ['POST'])]
    public function registerCampus(Request $request, SettingsManager $settingsManager): Response
    {
        $requestData = $request->toArray();
        $doNotListCampus = (bool) $requestData['donotlistcampus'];

        $settingsManager->setUrl($this->accessUrlHelper->getCurrent());
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

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/test-cache-all-users', name: 'chamilo_core_user_test_cache_all_users')]
    public function testCacheAllUsers(UserRepository $userRepository): JsonResponse
    {
        // Without cache
        $startNoCache = microtime(true);
        $usersNoCache = $userRepository->findAllUsers(false);
        $timeNoCache = microtime(true) - $startNoCache;

        // With cache
        $startCache = microtime(true);
        $resultCached = $userRepository->findAllUsers(true);
        $timeCache = microtime(true) - $startCache;

        // Check if we have a key (we do if cache was used)
        $usersCache = $resultCached['data'] ?? $resultCached;

        $cacheKey = $resultCached['cache_key'] ?? null;

        return $this->json([
            'without_cache' => [
                'count' => \count($usersNoCache),
                'execution_time' => $timeNoCache,
            ],
            'with_cache' => [
                'count' => \count($usersCache),
                'execution_time' => $timeCache,
                'cache_key' => $cacheKey,
            ],
        ]);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route(path: '/test-cache-all-users/invalidate', name: 'chamilo_core_user_test_cache_all_users_invalidate')]
    public function invalidateCacheAllUsers(QueryCacheHelper $queryCacheHelper): JsonResponse
    {
        $cacheKey = $queryCacheHelper->getCacheKey('findAllUsers', []);
        $queryCacheHelper->invalidate('findAllUsers');

        return $this->json([
            'message' => 'Cache for users invalidated!',
            'invalidated_cache_key' => $cacheKey,
        ]);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/cleanup-temp-uploads', name: 'admin_cleanup_temp_uploads', methods: ['GET'])]
    public function showCleanupTempUploads(
        TempUploadHelper $tempUploadHelper,
    ): Response {
        $stats = $tempUploadHelper->stats(); // ['files' => int, 'bytes' => int]

        return $this->render('@ChamiloCore/Admin/cleanup_temp_uploads.html.twig', [
            'tempDir' => $tempUploadHelper->getTempDir(),
            'stats' => $stats,
            'defaultOlderThan' => 0, // 0 = delete all
        ]);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/cleanup-temp-uploads', name: 'admin_cleanup_temp_uploads_run', methods: ['POST'])]
    public function runCleanupTempUploads(
        Request          $request,
        TempUploadHelper $tempUploadHelper,
    ): Response {
        // CSRF
        $token = (string) $request->request->get('_token', '');
        if (!$this->isCsrfTokenValid('cleanup_temp_uploads', $token)) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        // Read inputs
        $olderThan = (int) ($request->request->get('older_than', 0));
        $dryRun    = (bool) $request->request->get('dry_run', false);

        // 1) Purge temp uploads/cache (configurable dir via helper parameter)
        $purge = $tempUploadHelper->purge(olderThanMinutes: $olderThan, dryRun: $dryRun);

        if ($dryRun) {
            $this->addFlash('success', sprintf(
                'DRY RUN: %d files (%.2f MB) would be removed from %s.',
                $purge['files'],
                $purge['bytes'] / 1048576,
                $tempUploadHelper->getTempDir()
            ));
        } else {
            $this->addFlash('success', sprintf(
                'Temporary uploads/cache cleaned: %d files removed (%.2f MB) in %s.',
                $purge['files'],
                $purge['bytes'] / 1048576,
                $tempUploadHelper->getTempDir()
            ));
        }

        // 2) Remove legacy build main.js and hashed variants (best effort)
        $publicBuild = $this->getParameter('kernel.project_dir').'/public/build';
        if (is_dir($publicBuild) && is_readable($publicBuild)) {
            @unlink($publicBuild.'/main.js');
            $files = @scandir($publicBuild) ?: [];
            foreach ($files as $f) {
                if (preg_match('/^main\..*\.js$/', $f)) {
                    @unlink($publicBuild.'/'.$f);
                }
            }
        }

        // 3) Rebuild styles/assets like original archive_cleanup.php
        try {
            ScriptHandler::dumpCssFiles();
            $this->addFlash('success', 'The styles and assets in the web/ folder have been refreshed.');
        } catch (\Throwable $e) {
            $this->addFlash('error', 'The styles and assets could not be refreshed. Ensure public/ is writable.');
            error_log($e->getMessage());
        }

        return $this->redirectToRoute('admin_cleanup_temp_uploads', [], Response::HTTP_SEE_OTHER);
    }
}
