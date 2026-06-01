<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\Pagination\TraversablePaginator;
use ApiPlatform\State\ProviderInterface;
use ArrayIterator;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Entity\ResourceNode;
use Chamilo\CoreBundle\Entity\ResourceShowCourseResourcesInSessionInterface;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\AccessUrlHelper;
use Chamilo\CoreBundle\Helpers\CidReqHelper;
use Chamilo\CoreBundle\Repository\ResourceLinkRepository;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CDocument;
use Chamilo\CourseBundle\Entity\CGroup;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Throwable;

/**
 * @implements ProviderInterface<CDocument>
 */
final class DocumentCollectionStateProvider implements ProviderInterface
{
    /**
     * Lazily resolved installation prefix (first 8 chars of branch_sync.unique_id).
     * Null until first use; initialised at most once per PHP process.
     */
    private ?string $installationPrefix = null;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly RequestStack $requestStack,
        private readonly SettingsManager $settingsManager,
        private readonly AccessUrlHelper $accessUrlHelper,
        private readonly Security $security,
        #[Autowire(service: 'chamilo.document_list')]
        private readonly CacheInterface $documentListCache,
        #[Autowire('%kernel.secret%')]
        private readonly string $appSecret,
        private readonly CidReqHelper $cidReqHelper,
    ) {}

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     *
     * @return array<CDocument>|CDocument|null
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array|object|null
    {
        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            return [];
        }

        $qb = $this->entityManager
            ->getRepository(CDocument::class)
            ->createQueryBuilder('d')
            ->innerJoin('d.resourceNode', 'rn')
            ->addSelect('rn')
            ->leftJoin('rn.resourceType', 'rt')
            ->addSelect('rt')
            ->leftJoin('rt.tool', 'tool')
            ->addSelect('tool')
            ->leftJoin('rn.creator', 'creator')
            ->addSelect('creator')
        ;

        $query = $request->query->all();
        $course = $this->cidReqHelper->getCourseEntity();
        $session = $this->cidReqHelper->getSessionEntity();
        $group = $this->cidReqHelper->getGroupEntity();

        $page = max(1, (int) ($query['page'] ?? 1));
        $itemsPerPage = (int) ($query['itemsPerPage'] ?? 20);
        if ($itemsPerPage <= 0) {
            $itemsPerPage = 20;
        }
        if ($itemsPerPage > 5000) {
            $itemsPerPage = 5000;
        }

        $hasContext = $course || $session || $group;

        // By default, documents must be visible in session context including base course content,
        // because CDocument implements ResourceShowCourseResourcesInSessionInterface.
        $includeBaseContent = $session
            && is_a(CDocument::class, ResourceShowCourseResourcesInSessionInterface::class, true);

        // Allow API clients to override behavior (withBaseContent=0/1).
        if (\array_key_exists('withBaseContent', $query)) {
            $includeBaseContent = 1 === (int) $query['withBaseContent'];
        }

        // Gradebook mode (e.g. gradebook=1)
        $isGradebook = !empty($query['gradebook']) && 1 === (int) $query['gradebook'];
        $showUsersFolders = 'true' === (string) ($this->settingsManager->getSetting('document.show_users_folders', true) ?? '');
        $showDefaultFolders = 'false' !== (string) ($this->settingsManager->getSetting('document.show_default_folders', true) ?? '');
        $showChatFolder = 'true' === (string) ($this->settingsManager->getSetting('chat.show_chat_folder', true) ?? '');

        $rawFiletype = $query['filetype'] ?? null;
        $filetypes = [];

        if (\is_array($rawFiletype)) {
            $filetypes = $rawFiletype;
        } elseif (null !== $rawFiletype && '' !== (string) $rawFiletype) {
            $filetypes = [(string) $rawFiletype];
        }

        // Normalize & unique
        $requestedFiletypes = array_values(array_unique(array_filter(array_map('strval', $filetypes))));

        // Compatibility: treat "html" and cloud links as subtypes of "file".
        $effectiveFiletypes = $requestedFiletypes;
        if (\in_array('file', $effectiveFiletypes, true)) {
            foreach (['html', 'link'] as $fileSubtype) {
                if (!\in_array($fileSubtype, $effectiveFiletypes, true)) {
                    $effectiveFiletypes[] = $fileSubtype;
                }
            }
        }

        // System folder subtypes
        $systemFolderTypes = ['user_folder', 'user_folder_ses', 'media_folder', 'chat_folder', 'cert_folder'];

        // If the client asks for "folder", include system folder subtypes as well.
        // This keeps folder browsing working after migration (system folders have custom filetypes).
        if (\in_array('folder', $effectiveFiletypes, true)) {
            $effectiveFiletypes = array_values(array_unique(array_merge($effectiveFiletypes, $systemFolderTypes)));
        }

        if (!empty($effectiveFiletypes)) {
            $qb
                ->andWhere($qb->expr()->in('d.filetype', ':filetypes'))
                ->setParameter('filetypes', $effectiveFiletypes)
            ;
        }

        // NOTE: this is for "certificate" filetype lists, not the certificates folder filetype.
        $wantsCertificateList = \in_array('certificate', $effectiveFiletypes, true);
        $showSystemCertificates = !empty($query['showSystemCertificates']) && 1 === (int) $query['showSystemCertificates'];

        if (!$showSystemCertificates && !($isGradebook && $wantsCertificateList)) {
            $meta = $this->entityManager->getClassMetadata(ResourceNode::class);
            if ($meta->hasField('path')) {
                $qb
                    ->andWhere('rn.path IS NULL OR rn.path NOT LIKE :certificatesPath')
                    ->setParameter('certificatesPath', '%/certificates-%')
                ;
            }
        }

        // Hide system folders depending on settings.
        $explicitSystemTypes = array_values(array_intersect($requestedFiletypes, $systemFolderTypes));
        $hiddenSystemTypes = [];

        // User folders
        if (!$showUsersFolders) {
            $hiddenSystemTypes[] = 'user_folder';
            $hiddenSystemTypes[] = 'user_folder_ses';
        } else {
            // When enabled, never show session-scoped shared folders in base course context.
            if (!$session) {
                $hiddenSystemTypes[] = 'user_folder_ses';
            }
        }

        // Default media folders
        if (!$showDefaultFolders) {
            $hiddenSystemTypes[] = 'media_folder';
        }

        // Chat history folder (controlled by chat.show_chat_folder)
        if (!$showChatFolder) {
            $hiddenSystemTypes[] = 'chat_folder';
        }

        // Certificates folder: hide unless explicitly requested.
        if (!$showSystemCertificates && !($isGradebook && $wantsCertificateList)) {
            $hiddenSystemTypes[] = 'cert_folder';
        }

        $hiddenSystemTypes = array_values(array_unique(array_diff($hiddenSystemTypes, $explicitSystemTypes)));

        if (!empty($hiddenSystemTypes)) {
            $qb
                ->andWhere($qb->expr()->notIn('d.filetype', ':hiddenFiletypes'))
                ->setParameter('hiddenFiletypes', $hiddenSystemTypes)
            ;
        }

        $loadNode = !empty($query['loadNode']) && 1 === (int) $query['loadNode'];

        $parentNodeId = 0;
        if (isset($query['resourceNode.parent'])) {
            $parentNodeId = (int) $query['resourceNode.parent'];
        } elseif (isset($query['resourceNode_parent'])) {
            $parentNodeId = (int) $query['resourceNode_parent'];
        }

        if ($hasContext) {
            // Contextual hierarchy based on ResourceLink.parent
            $qb->innerJoin('rn.resourceLinks', 'rl')->addSelect('rl');

            // Hide DRAFT-visibility links from non-teachers. Mirrors the
            // canonical rule in CourseLinkExtensionTrait::addVisibilityCondition
            // — needed here because the custom provider bypasses CDocumentExtension.
            $roles = $this->security->getUser()?->getRoles() ?? [];
            $allowDraft = $this->security->isGranted('ROLE_ADMIN')
                || \in_array('ROLE_CURRENT_COURSE_TEACHER', $roles, true)
                || \in_array('ROLE_CURRENT_COURSE_SESSION_TEACHER', $roles, true);

            if (!$allowDraft) {
                $qb
                    ->andWhere('rl.visibility != :visibilityDraft')
                    ->setParameter('visibilityDraft', ResourceLink::VISIBILITY_DRAFT)
                ;
            }

            if ($course) {
                $qb->andWhere('IDENTITY(rl.course) = :cid')->setParameter('cid', $course->getId());
            }

            if ($session) {
                if ($includeBaseContent) {
                    // Include both session content and base course content.
                    $qb
                        ->andWhere(
                            $qb->expr()->orX(
                                'IDENTITY(rl.session) = :sid',
                                'rl.session IS NULL',
                                'IDENTITY(rl.session) = 0'
                            )
                        )
                        ->setParameter('sid', $session->getId())
                    ;
                } else {
                    $qb
                        ->andWhere('IDENTITY(rl.session) = :sid')
                        ->setParameter('sid', $session->getId())
                    ;
                }
            } else {
                $qb->andWhere(
                    $qb->expr()->orX(
                        'rl.session IS NULL',
                        'IDENTITY(rl.session) = 0'
                    )
                );
            }

            if ($group) {
                $qb->andWhere('IDENTITY(rl.group) = :gid')->setParameter('gid', $group->getIid());
            } else {
                $qb->andWhere('rl.group IS NULL');
            }

            if ($loadNode) {
                if ($parentNodeId > 0) {
                    $folderNode = $this->entityManager->getRepository(ResourceNode::class)->find($parentNodeId);
                    if (null === $folderNode) {
                        // Folder not found -> nothing to list
                        return [];
                    }

                    /** @var ResourceLinkRepository $linkRepo */
                    $linkRepo = $this->entityManager->getRepository(ResourceLink::class);

                    // Resolve possible folder links:
                    // - session link (sid=current session)
                    // - base link (sid=NULL), when base content is enabled in session view
                    $parentLinkIds = [];

                    $sessionParentLink = $linkRepo->findParentLinkForContext(
                        $folderNode,
                        $course,
                        $session,
                        $group,
                        null,
                        null
                    );
                    if (null !== $sessionParentLink) {
                        $parentLinkIds[] = (int) $sessionParentLink->getId();
                    }

                    if ($session && $includeBaseContent && $course) {
                        $baseParentLink = $linkRepo->findParentLinkForContext(
                            $folderNode,
                            $course,
                            null,
                            $group,
                            null,
                            null
                        );
                        if (null !== $baseParentLink) {
                            $parentLinkIds[] = (int) $baseParentLink->getId();
                        }
                    }

                    $parentLinkIds = array_values(array_unique($parentLinkIds));

                    if (!empty($parentLinkIds)) {
                        // If contextual parent links exist, prioritize rl.parent.
                        // The rn.parent fallback must only include items without contextual hierarchy (rl.parent IS NULL),
                        // otherwise moved items might appear in old folders due to rn.parent not changing.
                        $qb
                            ->andWhere(
                                $qb->expr()->orX(
                                    'IDENTITY(rl.parent) IN (:parentLinkIds)',
                                    $qb->expr()->andX(
                                        'IDENTITY(rn.parent) = :parentNodeId',
                                        'rl.parent IS NULL'
                                    )
                                )
                            )
                            ->setParameter('parentLinkIds', $parentLinkIds)
                            ->setParameter('parentNodeId', $parentNodeId)
                        ;
                    } else {
                        // No parent link resolved -> fallback to legacy tree
                        $qb
                            ->andWhere('IDENTITY(rn.parent) = :parentNodeId')
                            ->setParameter('parentNodeId', $parentNodeId)
                        ;
                    }
                } else {
                    // Context root
                    $qb->andWhere('rl.parent IS NULL');
                }
            }

            $qb->distinct();
        } else {
            // No context: legacy behavior — still eager-load links for serialization/voter
            $qb->leftJoin('rn.resourceLinks', 'rl')->addSelect('rl');
            if ($parentNodeId > 0) {
                $qb
                    ->andWhere('IDENTITY(rn.parent) = :parentId')
                    ->setParameter('parentId', $parentNodeId)
                ;
            }
        }

        // Dynamic sort — map API field names to DQL expressions.
        // When the client sends order[resourceNode.title]=desc etc. we honour it;
        // fall back to title ASC when no order param is present.
        $sortMap = [
            'iid' => 'd.iid',
            'filetype' => 'd.filetype',
            'resourceNode.title' => 'rn.title',
            'resourceNode.createdAt' => 'rn.createdAt',
            'resourceNode.updatedAt' => 'rn.updatedAt',
            'resourceNode.firstResourceFile.size' => 'rf.size',
        ];

        $rawOrder = \is_array($query['order'] ?? null) ? $query['order'] : [];
        $orderClauses = [];
        $needsFileJoin = false;

        foreach ($rawOrder as $field => $direction) {
            $dqlExpr = $sortMap[(string) $field] ?? null;
            if (null === $dqlExpr) {
                continue;
            }
            $dir = 'DESC' === strtoupper((string) $direction) ? 'DESC' : 'ASC';
            $orderClauses[] = [$dqlExpr, $dir];
            if ('rf.size' === $dqlExpr) {
                $needsFileJoin = true;
            }
        }

        if (empty($orderClauses)) {
            $orderClauses[] = ['rn.title', 'ASC'];
        }

        // The file-size sort needs an extra join on the filter query (fetchQb already has it).
        if ($needsFileJoin) {
            $qb->leftJoin('rn.resourceFiles', 'rf');
        }

        $first = true;
        foreach ($orderClauses as [$orderExpr, $orderDir]) {
            if ($first) {
                $qb->orderBy($orderExpr, $orderDir);
                $first = false;
            } else {
                $qb->addOrderBy($orderExpr, $orderDir);
            }
        }

        // Build a stable cache key from every value that affects the result set.
        // Settings-derived booleans are already folded into $hiddenSystemTypes.
        // The key is scoped to:
        //   - the Chamilo installation (branch_sync.unique_id prefix) so that
        //     multiple Chamilo instances on the same server never share APCu entries;
        //   - the access_url ID so that multi-portal setups within one installation
        //     are also isolated.
        $accessUrlId = $this->accessUrlHelper->getCurrent()?->getId() ?? 1;
        $viewerProfileBucket = $this->getViewerProfileCacheBucket($course, $session);
        $sortedTypes = $effectiveFiletypes;
        sort($sortedTypes);
        $sortedHidden = $hiddenSystemTypes;
        sort($sortedHidden);
        $cacheKey = 'doc_list_'.$this->getInstallationPrefix().'_'.hash('md5', serialize([
            $accessUrlId,
            $viewerProfileBucket,
            $course?->getId() ?? 0,
            $session?->getId() ?? 0,
            $group?->getIid() ?? 0,
            $parentNodeId,
            $loadNode,
            $sortedTypes,
            $sortedHidden,
            $includeBaseContent,
            $isGradebook,
            $showSystemCertificates,
            $orderClauses,
            $page,
            $itemsPerPage,
        ]));

        // Cache the expensive context-filtered count + IID list for up to 120 s.
        // On cache hit we re-fetch the same page of documents by primary key,
        // which is a simple indexed lookup — no DISTINCT, no complex WHERE.
        $cached = $this->documentListCache->get(
            $cacheKey,
            static function (ItemInterface $item) use ($qb, $orderClauses, $page, $itemsPerPage): array {
                $item->expiresAfter(120);

                $countQb = clone $qb;
                $countQb->resetDQLPart('orderBy');
                $total = (int) $countQb
                    ->select('COUNT(DISTINCT d.iid)')
                    ->getQuery()
                    ->getSingleScalarResult()
                ;

                // SELECT DISTINCT requires every ORDER BY expression to appear in the SELECT list.
                // Build a comma-separated select that includes d.iid plus any extra sort columns.
                $iidSelect = 'd.iid';
                foreach ($orderClauses as [$orderExpr, $orderDir]) {
                    if ('d.iid' !== $orderExpr) {
                        $iidSelect .= ', '.$orderExpr;
                    }
                }

                $iidQb = clone $qb;
                $rows = $iidQb
                    ->select($iidSelect)
                    ->distinct()
                    ->setFirstResult(($page - 1) * $itemsPerPage)
                    ->setMaxResults($itemsPerPage)
                    ->getQuery()
                    ->getArrayResult()
                ;

                return ['total' => $total, 'iids' => array_column($rows, 'iid')];
            }
        );

        $iids = $cached['iids'];

        if (empty($iids)) {
            return new TraversablePaginator(new ArrayIterator([]), $page, $itemsPerPage, $cached['total']);
        }

        // Fetch the current page of documents by PK with all joins needed for
        // serialization, including resourceFiles (fixes N+1 on file sizes).
        $fetchQb = $this->entityManager
            ->getRepository(CDocument::class)
            ->createQueryBuilder('d')
            ->innerJoin('d.resourceNode', 'rn')->addSelect('rn')
            ->leftJoin('rn.resourceType', 'rt')->addSelect('rt')
            ->leftJoin('rt.tool', 'tool')->addSelect('tool')
            ->leftJoin('rn.creator', 'creator')->addSelect('creator')
            ->leftJoin('rn.resourceLinks', 'rl')->addSelect('rl')
            ->leftJoin('rn.resourceFiles', 'rf')->addSelect('rf')
            ->andWhere('d.iid IN (:iids)')
            ->setParameter('iids', $iids, ArrayParameterType::INTEGER)
        ;

        $results = $fetchQb->getQuery()->getResult();

        $this->translateSystemFolderTitles($results);

        // Restore the sort order from the cached IID list (SQL IN has no guaranteed order).
        $posMap = array_flip($iids);
        usort(
            $results,
            static fn (CDocument $a, CDocument $b): int => ($posMap[$a->getIid()] ?? 0) <=> ($posMap[$b->getIid()] ?? 0)
        );

        return new TraversablePaginator(
            new ArrayIterator($results),
            $page,
            $itemsPerPage,
            $cached['total']
        );
    }

    /**
     * Translate canonical system folder titles for display without changing persisted values.
     *
     * @param array<int, mixed> $documents
     */
    private function translateSystemFolderTitles(array $documents): void
    {
        foreach ($documents as $document) {
            if (!$document instanceof CDocument) {
                continue;
            }

            if ('folder' !== $document->getFiletype()) {
                continue;
            }

            if ('learning_path' !== $document->getTitle()) {
                continue;
            }

            $translatedTitle = get_lang('Learning paths');

            $document->setTitle($translatedTitle);

            $resourceNode = $document->getResourceNode();
            if (null === $resourceNode) {
                continue;
            }

            $resourceNode->setTitle($translatedTitle);
        }
    }

    /**
     * Returns the first 8 hex characters of the installation-unique identifier
     * stored in branch_sync.unique_id (a SHA1 generated at install/migration time).
     * This prefix distinguishes cache entries between different Chamilo installations
     * that share the same APCu memory on a single server.
     *
     * Falls back to the first 8 chars of %kernel.secret% if the table is empty or
     * unavailable (e.g. during a fresh install before fixtures run).
     *
     * The result is cached in a private property so the DB is queried at most once
     * per PHP process.
     */
    private function getInstallationPrefix(): string
    {
        if (null !== $this->installationPrefix) {
            return $this->installationPrefix;
        }

        $uniqueId = '';

        try {
            $uniqueId = (string) $this->entityManager->getConnection()->fetchOne(
                'SELECT unique_id FROM branch_sync ORDER BY id ASC LIMIT 1'
            );
        } catch (Throwable) {
            // Table may not exist during a fresh install — fall through to fallback.
        }

        $this->installationPrefix = substr($uniqueId ?: $this->appSecret, 0, 8);

        return $this->installationPrefix;
    }

    private function getViewerProfileCacheBucket(?Course $course, ?Session $session): string
    {
        $user = $this->security->getUser();

        if (!$user instanceof User) {
            return 'anonymous';
        }

        if ($this->security->isGranted('ROLE_ADMIN')) {
            return 'admin';
        }

        if ($session instanceof Session && $course) {
            $userIsGeneralCoach = $session->hasUserAsGeneralCoach($user);
            $userIsCourseCoach = $session->hasCourseCoachInCourse($user, $course);
            $userIsStudent = $session->hasUserInCourse($user, $course, Session::STUDENT);

            if ($userIsGeneralCoach || $userIsCourseCoach) {
                return 'session_teacher';
            }

            if ($userIsStudent) {
                return 'session_student';
            }
        }

        if ($course) {
            if ($course->hasUserAsTeacher($user)) {
                return 'teacher';
            }

            if ($course->hasSubscriptionByUser($user)) {
                return 'student';
            }
        }

        return 'authenticated';
    }
}
