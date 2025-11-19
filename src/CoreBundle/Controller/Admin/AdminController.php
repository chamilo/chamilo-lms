<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller\Admin;

use Chamilo\CoreBundle\Component\Composer\ScriptHandler;
use Chamilo\CoreBundle\Controller\BaseController;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ResourceFile;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Entity\ResourceType;
use Chamilo\CoreBundle\Helpers\AccessUrlHelper;
use Chamilo\CoreBundle\Helpers\QueryCacheHelper;
use Chamilo\CoreBundle\Helpers\TempUploadHelper;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
use Chamilo\CoreBundle\Repository\ResourceFileRepository;
use Chamilo\CoreBundle\Repository\ResourceNodeRepository;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Throwable;

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
        $totalPages = $totalItems > 0 ? (int) ceil($totalItems / self::ITEMS_PER_PAGE) : 1;

        $fileUrls = [];
        $filePaths = [];
        $orphanFlags = [];
        $linksCount = [];

        foreach ($files as $file) {
            $resourceNode = $file->getResourceNode();
            $count = 0;

            if ($resourceNode) {
                $fileUrls[$file->getId()] = $this->resourceNodeRepository->getResourceFileUrl($resourceNode);

                // Count how many ResourceLinks still point to this node
                $links = $resourceNode->getResourceLinks();
                $count = $links ? $links->count() : 0;
            } else {
                $fileUrls[$file->getId()] = null;
            }

            $filePaths[$file->getId()] = '/upload/resource'.$this->resourceNodeRepository->getFilename($file);

            $linksCount[$file->getId()] = $count;
            $orphanFlags[$file->getId()] = 0 === $count;
        }

        return $this->render('@ChamiloCore/Admin/files_info.html.twig', [
            'files' => $files,
            'fileUrls' => $fileUrls,
            'filePaths' => $filePaths,
            'totalPages' => $totalPages,
            'currentPage' => $page,
            'search' => $search,
            'orphanFlags' => $orphanFlags,
            'linksCount' => $linksCount,
        ]);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/files_info/attach', name: 'admin_files_info_attach', methods: ['POST'])]
    public function attachOrphanFileToCourse(
        Request $request,
        ResourceFileRepository $resourceFileRepository,
        CourseRepository $courseRepository,
        EntityManagerInterface $em
    ): Response {
        $token = (string) $request->request->get('_token', '');
        if (!$this->isCsrfTokenValid('attach_orphan_file', $token)) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $fileId = $request->request->getInt('resource_file_id', 0);
        $courseCode = trim((string) $request->request->get('course_code', ''));

        $page = $request->request->getInt('page', 1);
        $search = (string) $request->request->get('search', '');

        if ($fileId <= 0) {
            $this->addFlash('error', 'Missing resource file identifier.');

            return $this->redirectToRoute('admin_files_info', [
                'page' => $page,
                'search' => $search,
            ]);
        }

        if ('' === $courseCode) {
            $this->addFlash('error', 'Please provide a course code.');

            return $this->redirectToRoute('admin_files_info', [
                'page' => $page,
                'search' => $search,
            ]);
        }

        /** @var ResourceFile|null $resourceFile */
        $resourceFile = $resourceFileRepository->find($fileId);
        if (!$resourceFile) {
            $this->addFlash('error', 'Resource file not found.');

            return $this->redirectToRoute('admin_files_info', [
                'page' => $page,
                'search' => $search,
            ]);
        }

        $resourceNode = $resourceFile->getResourceNode();
        $linksCount = $resourceNode ? $resourceNode->getResourceLinks()->count() : 0;
        if ($linksCount > 0) {
            // Safety check: this file is not orphan anymore.
            $this->addFlash('warning', 'This file is no longer orphan and cannot be attached.');

            return $this->redirectToRoute('admin_files_info', [
                'page' => $page,
                'search' => $search,
            ]);
        }

        /** @var Course|null $course */
        $course = $courseRepository->findOneBy(['code' => $courseCode]);
        if (!$course) {
            $this->addFlash('error', sprintf('Course with code "%s" was not found.', $courseCode));

            return $this->redirectToRoute('admin_files_info', [
                'page' => $page,
                'search' => $search,
            ]);
        }

        if (!$resourceNode) {
            $this->addFlash('error', 'This resource file has no resource node and cannot be attached.');

            return $this->redirectToRoute('admin_files_info', [
                'page' => $page,
                'search' => $search,
            ]);
        }

        // re-parent the ResourceNode to the course documents root
        if (method_exists($course, 'getResourceNode')) {
            $courseRootNode = $course->getResourceNode();

            if ($courseRootNode) {
                $resourceNode->setParent($courseRootNode);
            }
        }

        // Create a new ResourceLink so that the file appears in the course context
        $link = new ResourceLink();
        $link->setResourceNode($resourceNode);
        $link->setCourse($course);
        $link->setSession(null);
        $em->persist($link);
        $em->flush();

        $this->addFlash(
            'success',
            sprintf(
                'File "%s" has been attached to course "%s" (hidden in the documents root).',
                (string) ($resourceFile->getOriginalName() ?? $resourceFile->getTitle() ?? $resourceFile->getId()),
                (string) $course->getTitle()
            )
        );

        return $this->redirectToRoute('admin_files_info', [
            'page' => $page,
            'search' => $search,
        ]);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/files_info/delete', name: 'admin_files_info_delete', methods: ['POST'])]
    public function deleteOrphanFile(
        Request $request,
        ResourceFileRepository $resourceFileRepository,
        EntityManagerInterface $em
    ): Response {
        $token = (string) $request->request->get('_token', '');
        if (!$this->isCsrfTokenValid('delete_orphan_file', $token)) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $fileId = $request->request->getInt('resource_file_id', 0);
        $page = $request->request->getInt('page', 1);
        $search = (string) $request->request->get('search', '');

        if ($fileId <= 0) {
            $this->addFlash('error', 'Missing resource file identifier.');

            return $this->redirectToRoute('admin_files_info', [
                'page' => $page,
                'search' => $search,
            ]);
        }

        $resourceFile = $resourceFileRepository->find($fileId);
        if (!$resourceFile) {
            $this->addFlash('error', 'Resource file not found.');

            return $this->redirectToRoute('admin_files_info', [
                'page' => $page,
                'search' => $search,
            ]);
        }

        $resourceNode = $resourceFile->getResourceNode();
        $linksCount = $resourceNode ? $resourceNode->getResourceLinks()->count() : 0;
        if ($linksCount > 0) {
            $this->addFlash('warning', 'This file is still used by at least one course/session and cannot be deleted.');

            return $this->redirectToRoute('admin_files_info', [
                'page' => $page,
                'search' => $search,
            ]);
        }

        // Compute physical path in var/upload/resource (adapt if you use another directory).
        $relativePath = $this->resourceNodeRepository->getFilename($resourceFile);
        $storageRoot = $this->getParameter('kernel.project_dir').'/var/upload/resource';
        $absolutePath = $storageRoot.$relativePath;

        if (is_file($absolutePath) && is_writable($absolutePath)) {
            @unlink($absolutePath);
        }

        // Optionally remove the resource node as well if it is really orphan.
        if ($resourceNode) {
            $em->remove($resourceNode);
        }

        $em->remove($resourceFile);
        $em->flush();

        $this->addFlash('success', 'Orphan file and its physical content have been deleted definitively.');

        return $this->redirectToRoute('admin_files_info', [
            'page' => $page,
            'search' => $search,
        ]);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/resources_info', name: 'admin_resources_info', methods: ['GET'])]
    public function listResourcesInfo(
        Request $request,
        ResourceNodeRepository $resourceNodeRepo,
        EntityManagerInterface $em
    ): Response {
        $resourceTypeId = $request->query->getInt('type');
        $resourceTypes = $em->getRepository(ResourceType::class)->findAll();

        $courses = [];
        $showUsers = false;
        $typeTitle = null;

        if ($resourceTypeId > 0) {
            /** @var ResourceType|null $rt */
            $rt = $em->getRepository(ResourceType::class)->find($resourceTypeId);
            $typeTitle = $rt?->getTitle();

            /** Load ResourceLinks for the selected type */
            /** @var ResourceLink[] $resourceLinks */
            $resourceLinks = $em->getRepository(ResourceLink::class)->createQueryBuilder('rl')
                ->join('rl.resourceNode', 'rn')
                ->where('rn.resourceType = :type')
                ->setParameter('type', $resourceTypeId)
                ->getQuery()
                ->getResult()
            ;

            /** Aggregate by course/session key */
            $seen = [];
            $keysMeta = [];
            foreach ($resourceLinks as $link) {
                $course = $link->getCourse();
                if (!$course) {
                    continue;
                }
                $session = $link->getSession();
                $node = $link->getResourceNode();

                $cid = $course->getId();
                $sid = $session?->getId() ?? 0;
                $key = self::makeKey($cid, $sid);

                if (!isset($seen[$key])) {
                    $seen[$key] = [
                        'type' => $sid ? 'session' : 'course',
                        'id' => $sid ?: $cid,
                        'courseId' => $cid,
                        'sessionId' => $sid,
                        'title' => $sid ? ($session->getTitle().' - '.$course->getTitle()) : $course->getTitle(),
                        'url' => $sid
                            ? '/course/'.$cid.'/home?sid='.$sid
                            : '/course/'.$cid.'/home',
                        'count' => 0,
                        'items' => [],
                        'users' => [],
                        'firstCreatedAt' => $node->getCreatedAt(),
                    ];
                    $keysMeta[$key] = ['cid' => $cid, 'sid' => $sid];
                }

                $seen[$key]['count']++;
                $seen[$key]['items'][] = $node->getTitle();

                if ($node->getCreatedAt() < $seen[$key]['firstCreatedAt']) {
                    $seen[$key]['firstCreatedAt'] = $node->getCreatedAt();
                }
            }

            /* Populate users depending on the resource type */
            if (!empty($seen)) {
                $usersMap = $this->fetchUsersForType($typeTitle, $em, $keysMeta);
                foreach ($usersMap as $key => $names) {
                    if (isset($seen[$key]) && $names) {
                        $seen[$key]['users'] = array_values(array_unique($names));
                    }
                }
                // Show the "Users" column only if there's any user to display
                $showUsers = array_reduce($seen, fn ($acc, $row) => $acc || !empty($row['users']), false);
            }

            /** Normalize output */
            $courses = array_values(array_map(function ($row) {
                $row['items'] = array_values(array_unique($row['items']));

                return $row;
            }, $seen));

            usort($courses, fn ($a, $b) => strnatcasecmp($a['title'], $b['title']));
        }

        return $this->render('@ChamiloCore/Admin/resources_info.html.twig', [
            'resourceTypes' => $resourceTypes,
            'selectedType' => $resourceTypeId,
            'courses' => $courses,
            'showUsers' => $showUsers,
            'typeTitle' => $typeTitle,
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
        Request $request,
        TempUploadHelper $tempUploadHelper,
    ): Response {
        // CSRF
        $token = (string) $request->request->get('_token', '');
        if (!$this->isCsrfTokenValid('cleanup_temp_uploads', $token)) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        // Read inputs
        $olderThan = (int) $request->request->get('older_than', 0);
        $dryRun = (bool) $request->request->get('dry_run', false);

        // Purge temp uploads/cache (configurable dir via helper parameter)
        $purge = $tempUploadHelper->purge(olderThanMinutes: $olderThan, dryRun: $dryRun);

        if ($dryRun) {
            $this->addFlash('success', \sprintf(
                'DRY RUN: %d files (%.2f MB) would be removed from %s.',
                $purge['files'],
                $purge['bytes'] / 1048576,
                $tempUploadHelper->getTempDir()
            ));
        } else {
            $this->addFlash('success', \sprintf(
                'Temporary uploads/cache cleaned: %d files removed (%.2f MB) in %s.',
                $purge['files'],
                $purge['bytes'] / 1048576,
                $tempUploadHelper->getTempDir()
            ));
        }

        // Remove legacy build main.js and hashed variants (best effort)
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

        // Rebuild styles/assets like original archive_cleanup.php
        try {
            ScriptHandler::dumpCssFiles();
            $this->addFlash('success', 'The styles and assets in the web/ folder have been refreshed.');
        } catch (Throwable $e) {
            $this->addFlash('error', 'The styles and assets could not be refreshed. Ensure public/ is writable.');
            error_log($e->getMessage());
        }

        return $this->redirectToRoute('admin_cleanup_temp_uploads', [], Response::HTTP_SEE_OTHER);
    }

    /**
     * Returns a map key => [user names...] depending on the selected resource type.
     *
     * @param array<string,array{cid:int,sid:int}> $keysMeta
     *
     * @return array<string,string[]>
     */
    private function fetchUsersForType(?string $typeTitle, EntityManagerInterface $em, array $keysMeta): array
    {
        $type = \is_string($typeTitle) ? strtolower($typeTitle) : '';

        return match ($type) {
            'dropbox' => $this->fetchDropboxRecipients($em, $keysMeta),
            // 'student_publications' => $this->fetchStudentPublicationsUsers($em, $keysMeta), // TODO
            default => $this->fetchUsersFromResourceLinks($em, $keysMeta),
        };
    }

    /**
     * Default behavior: list users tied to ResourceLink.user (user-scoped visibility).
     *
     * @param array<string,array{cid:int,sid:int}> $keysMeta
     *
     * @return array<string,string[]>
     */
    private function fetchUsersFromResourceLinks(EntityManagerInterface $em, array $keysMeta): array
    {
        if (!$keysMeta) {
            return [];
        }

        // Load resource links having a user and group them by (cid,sid)
        $q = $em->createQuery(
            'SELECT rl, c, s, u
           FROM Chamilo\CoreBundle\Entity\ResourceLink rl
           LEFT JOIN rl.course c
           LEFT JOIN rl.session s
           LEFT JOIN rl.user u
          WHERE rl.user IS NOT NULL'
        );

        /** @var ResourceLink[] $links */
        $links = $q->getResult();

        $out = [];
        foreach ($links as $rl) {
            $cid = $rl->getCourse()?->getId();
            if (!$cid) {
                continue;
            }
            $sid = $rl->getSession()?->getId() ?? 0;
            $key = self::makeKey($cid, $sid);
            if (!isset($keysMeta[$key])) {
                continue; // ignore links not present in the current table
            }

            $name = $rl->getUser()?->getFullName();
            if ($name) {
                $out[$key][] = $name;
            }
        }
        // Dedupe
        foreach ($out as $k => $arr) {
            $out[$k] = array_values(array_unique(array_filter($arr)));
        }

        return $out;
    }

    /**
     * Dropbox-specific: list real recipients from c_dropbox_person (joined with c_dropbox_file and user).
     *
     * @param array<string,array{cid:int,sid:int}> $keysMeta
     *
     * @return array<string,string[]>
     */
    private function fetchDropboxRecipients(EntityManagerInterface $em, array $keysMeta): array
    {
        if (!$keysMeta) {
            return [];
        }

        $cids = array_values(array_unique(array_map(fn ($m) => (int) $m['cid'], $keysMeta)));
        if (!$cids) {
            return [];
        }

        $conn = $em->getConnection();
        $sql = "SELECT
                    p.c_id                       AS cid,
                    f.session_id                 AS sid,
                    CONCAT(u.firstname, ' ', u.lastname) AS uname
                FROM c_dropbox_person p
                INNER JOIN c_dropbox_file f
                        ON f.iid = p.file_id
                       AND f.c_id = p.c_id
                INNER JOIN `user` u
                        ON u.id = p.user_id
                WHERE p.c_id IN (:cids)
        ";

        $rows = $conn->executeQuery($sql, ['cids' => $cids], ['cids' => Connection::PARAM_INT_ARRAY])->fetchAllAssociative();

        $out = [];
        foreach ($rows as $r) {
            $cid = (int) ($r['cid'] ?? 0);
            $sid = (int) ($r['sid'] ?? 0);
            $key = self::makeKey($cid, $sid);
            if (!isset($keysMeta[$key])) {
                continue; // ignore entries not displayed in the table
            }
            $uname = trim((string) ($r['uname'] ?? ''));
            if ('' !== $uname) {
                $out[$key][] = $uname;
            }
        }
        // Dedupe
        foreach ($out as $k => $arr) {
            $out[$k] = array_values(array_unique(array_filter($arr)));
        }

        return $out;
    }

    /**
     * Helper to build the aggregation key for course/session rows.
     */
    private static function makeKey(int $cid, int $sid): string
    {
        return $sid > 0 ? ('s'.$sid.'-'.$cid) : ('c'.$cid);
    }
}
