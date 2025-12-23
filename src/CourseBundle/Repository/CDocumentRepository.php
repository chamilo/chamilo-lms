<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Repository;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ResourceFile;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Entity\ResourceNode;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CoreBundle\Repository\ResourceNodeRepository;
use Chamilo\CoreBundle\Repository\ResourceRepository;
use Chamilo\CourseBundle\Entity\CDocument;
use Chamilo\CourseBundle\Entity\CGroup;
use Chamilo\CourseBundle\Entity\CLp;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use RuntimeException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Throwable;

/**
 * @extends ResourceRepository<CDocument>
 */
final class CDocumentRepository extends ResourceRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CDocument::class);
    }

    public function getParent(CDocument $document): ?CDocument
    {
        $resourceParent = $document->getResourceNode()->getParent();

        if (null !== $resourceParent) {
            return $this->findOneBy(['resourceNode' => $resourceParent->getId()]);
        }

        return null;
    }

    public function getFolderSize(ResourceNode $resourceNode, Course $course, ?Session $session = null): int
    {
        return $this->getResourceNodeRepository()->getSize($resourceNode, $this->getResourceType(), $course, $session);
    }

    /**
     * @return CDocument[]
     */
    public function findDocumentsByAuthor(int $userId)
    {
        $qb = $this->createQueryBuilder('d');

        return $qb
            ->innerJoin('d.resourceNode', 'node')
            ->innerJoin('node.resourceLinks', 'l')
            ->where('l.user = :user')
            ->setParameter('user', $userId)
            ->getQuery()
            ->getResult()
        ;
    }

    public function countUserDocuments(User $user, Course $course, ?Session $session = null, ?CGroup $group = null): int
    {
        $qb = $this->getResourcesByCourseLinkedToUser($user, $course, $session, $group);
        $qb->select('count(resource)');
        $this->addFileTypeQueryBuilder('file', $qb);

        return $this->getCount($qb);
    }

    protected function addFileTypeQueryBuilder(string $fileType, ?QueryBuilder $qb = null): QueryBuilder
    {
        $qb = $this->getOrCreateQueryBuilder($qb);

        return $qb
            ->andWhere('resource.filetype = :filetype')
            ->setParameter('filetype', $fileType)
        ;
    }

    /**
     * Register the original SCORM ZIP under:
     *   <course root> / Learning paths / SCORM - {lp_id} - {lp_title} / {lp_title}.zip
     */
    public function registerScormZip(Course $course, ?Session $session, CLp $lp, UploadedFile $zip): void
    {
        $em = $this->em();

        // Ensure "Learning paths" directly under the course resource node
        $lpTop = $this->ensureLearningPathSystemFolder($course, $session);

        // Subfolder per LP
        $lpFolderTitle = \sprintf('SCORM - %d - %s', $lp->getIid(), $this->safeTitle($lp->getTitle()));
        $lpFolder = $this->ensureFolder(
            $course,
            $lpTop,
            $lpFolderTitle,
            ResourceLink::VISIBILITY_DRAFT,
            $session
        );

        // ZIP file under the LP folder
        $this->createFileInFolder(
            $course,
            $lpFolder,
            $zip,
            \sprintf('SCORM ZIP for LP #%d', $lp->getIid()),
            ResourceLink::VISIBILITY_DRAFT,
            $session
        );

        $em->flush();
    }

    /**
     * Remove the LP subfolder "SCORM - {lp_id} - ..." under "Learning paths".
     */
    public function purgeScormZip(Course $course, CLp $lp): void
    {
        $em = $this->em();
        $prefix = \sprintf('SCORM - %d - ', $lp->getIid());

        $courseRoot = $course->getResourceNode();
        if ($courseRoot) {
            // SCORM folder directly under course root
            if ($this->tryDeleteFirstFolderByTitlePrefix($courseRoot, $prefix)) {
                $em->flush();

                return;
            }

            // Or under "Learning paths"
            $lpTop = $this->findChildNodeByTitle($courseRoot, 'Learning paths');
            if ($lpTop && $this->tryDeleteFirstFolderByTitlePrefix($lpTop, $prefix)) {
                $em->flush();

                return;
            }
        }
    }

    /**
     * Try to delete the first child folder whose title starts with $prefix under $parent.
     * Returns true if something was removed.
     */
    private function tryDeleteFirstFolderByTitlePrefix(ResourceNode $parent, string $prefix): bool
    {
        $em = $this->em();
        $qb = $em->createQueryBuilder()
            ->select('rn')
            ->from(ResourceNode::class, 'rn')
            ->where('rn.parent = :parent')
            ->andWhere('rn.title LIKE :prefix')
            ->setParameters(['parent' => $parent, 'prefix' => $prefix.'%'])
            ->setMaxResults(1)
        ;

        /** @var ResourceNode|null $node */
        $node = $qb->getQuery()->getOneOrNullResult();
        if ($node) {
            $em->remove($node);

            return true;
        }

        return false;
    }

    /**
     * Find the course Documents root node.
     *
     * Primary: parent = course.resourceNode
     * Fallback (legacy): parent IS NULL
     */
    public function getCourseDocumentsRootNode(Course $course): ?ResourceNode
    {
        $em = $this->em();
        $rt = $this->getResourceType();
        $courseNode = $course->getResourceNode();

        if ($courseNode) {
            $node = $em->createQuery(
                'SELECT rn
                   FROM Chamilo\CoreBundle\Entity\ResourceNode rn
                   JOIN rn.resourceType rt
                   JOIN rn.resourceLinks rl
                  WHERE rn.parent = :parent
                    AND rt = :rtype
                    AND rl.course = :course
               ORDER BY rn.id ASC'
            )
                ->setParameters([
                    'parent' => $courseNode,
                    'rtype' => $rt,
                    'course' => $course,
                ])
                ->setMaxResults(1)
                ->getOneOrNullResult()
            ;

            if ($node) {
                return $node;
            }
        }

        // Fallback for historical data (Documents root directly under NULL)
        return $em->createQuery(
            'SELECT rn
               FROM Chamilo\CoreBundle\Entity\ResourceNode rn
               JOIN rn.resourceType rt
               JOIN rn.resourceLinks rl
              WHERE rn.parent IS NULL
                AND rt = :rtype
                AND rl.course = :course
           ORDER BY rn.id ASC'
        )
            ->setParameters(['rtype' => $rt, 'course' => $course])
            ->setMaxResults(1)
            ->getOneOrNullResult()
        ;
    }

    /**
     * Ensure the course "Documents" root node exists.
     * Now parent = course.resourceNode (so the tool lists it under the course).
     */
    public function ensureCourseDocumentsRootNode(Course $course): ResourceNode
    {
        if ($root = $this->getCourseDocumentsRootNode($course)) {
            return $root;
        }

        $em = $this->em();
        $type = $this->getResourceType();

        /** @var User|null $user */
        $user = api_get_user_entity();

        $node = new ResourceNode();
        $node->setTitle('Documents');
        $node->setResourceType($type);
        $node->setPublic(false);

        if ($course->getResourceNode()) {
            $node->setParent($course->getResourceNode());
        }

        if ($user) {
            $node->setCreator($user);
        }

        $link = new ResourceLink();
        $link->setCourse($course);
        $link->setVisibility(ResourceLink::VISIBILITY_PUBLISHED);
        $node->addResourceLink($link);

        $em->persist($node);
        $em->flush();

        return $node;
    }

    /**
     * Create (if missing) a folder under $parent using the CDocument API path.
     * The folder is attached under the given $parent (not the course root),
     * and linked to the course (cid) and optionally the session (sid).
     */
    public function ensureFolder(
        Course $course,
        ResourceNode $parent,
        string $folderTitle,
        int $visibility = ResourceLink::VISIBILITY_DRAFT,
        ?Session $session = null
    ): ResourceNode {
        try {
            if ($child = $this->findChildNodeByTitle($parent, $folderTitle)) {
                return $child;
            }

            /** @var User|null $user */
            $user = api_get_user_entity();
            $creatorId = $user?->getId();

            $doc = new CDocument();
            $doc->setTitle($folderTitle);
            $doc->setFiletype('folder');
            $doc->setParentResourceNode($parent->getId());

            $link = [
                'cid' => $course->getId(),
                'visibility' => $visibility,
            ];
            if ($session && method_exists($session, 'getId')) {
                $link['sid'] = $session->getId();
            }
            $doc->setResourceLinkArray([$link]);

            if ($user) {
                $doc->setCreator($user);
            }

            $em = $this->em();
            $em->persist($doc);
            $em->flush();

            return $doc->getResourceNode();
        } catch (Throwable $e) {
            error_log('[CDocumentRepo.ensureFolder] ERROR '.$e->getMessage());

            throw $e;
        }
    }

    /**
     * Create a file under $parent using the CDocument API path.
     * The file is linked to the course (cid) and optionally the session (sid).
     */
    public function createFileInFolder(
        Course $course,
        ResourceNode $parent,
        UploadedFile $uploaded,
        string $comment,
        int $visibility,
        ?Session $session = null
    ): ResourceNode {
        /** @var User|null $user */
        $user = api_get_user_entity();

        $title = $uploaded->getClientOriginalName();

        $doc = new CDocument();
        $doc->setTitle($title);
        $doc->setFiletype('file');
        $doc->setComment($comment);
        $doc->setParentResourceNode($parent->getId());

        $link = [
            'cid' => $course->getId(),
            'visibility' => $visibility,
        ];
        if ($session && method_exists($session, 'getId')) {
            $link['sid'] = $session->getId();
        }
        $doc->setResourceLinkArray([$link]);

        $doc->setUploadFile($uploaded);

        if ($user) {
            $doc->setCreator($user);
        }

        $em = $this->em();
        $em->persist($doc);
        $em->flush();

        return $doc->getResourceNode();
    }

    public function findChildNodeByTitle(ResourceNode $parent, string $title): ?ResourceNode
    {
        return $this->em()
            ->getRepository(ResourceNode::class)
            ->findOneBy([
                'parent' => $parent->getId(),
                'title' => $title,
            ])
        ;
    }

    /**
     * Ensure "Learning paths" exists directly under the course resource node.
     * Links are created for course (and optional session) context.
     */
    public function ensureLearningPathSystemFolder(Course $course, ?Session $session = null): ResourceNode
    {
        $courseRoot = $course->getResourceNode();
        if (!$courseRoot instanceof ResourceNode) {
            throw new RuntimeException('Course has no ResourceNode root.');
        }

        // Try common i18n variants first
        $candidates = array_values(array_unique(array_filter([
            \function_exists('get_lang') ? get_lang('Learning paths') : null,
            \function_exists('get_lang') ? get_lang('Learning path') : null,
            'Learning paths',
            'Learning path',
        ])));

        foreach ($candidates as $title) {
            if ($child = $this->findChildNodeByTitle($courseRoot, $title)) {
                return $child;
            }
        }

        // Create "Learning paths" directly under the course root
        return $this->ensureFolder(
            $course,
            $courseRoot,
            'Learning paths',
            ResourceLink::VISIBILITY_DRAFT,
            $session
        );
    }

    /**
     * Recursively list all files (not folders) under a CDocument folder by its iid.
     * Returns items ready for exporters.
     */
    public function listFilesByParentIid(int $parentIid): array
    {
        $em = $this->getEntityManager();

        /** @var CDocument|null $parentDoc */
        $parentDoc = $this->findOneBy(['iid' => $parentIid]);
        if (!$parentDoc instanceof CDocument) {
            return [];
        }

        $parentNode = $parentDoc->getResourceNode();
        if (!$parentNode instanceof ResourceNode) {
            return [];
        }

        $out = [];
        $stack = [$parentNode->getId()];

        $projectDir = Container::$container->get('kernel')->getProjectDir();
        $resourceBase = rtrim($projectDir, '/').'/var/upload/resource';

        /** @var ResourceNodeRepository $rnRepo */
        $rnRepo = Container::$container->get(ResourceNodeRepository::class);

        while ($stack) {
            $pid = array_pop($stack);

            $qb = $em->createQueryBuilder()
                ->select('d', 'rn')
                ->from(CDocument::class, 'd')
                ->innerJoin('d.resourceNode', 'rn')
                ->andWhere('rn.parent = :pid')
                ->setParameter('pid', $pid)
            ;

            /** @var CDocument[] $children */
            $children = $qb->getQuery()->getResult();

            foreach ($children as $doc) {
                $filetype = (string) $doc->getFiletype();
                $rn = $doc->getResourceNode();

                if ('folder' === $filetype) {
                    if ($rn) {
                        $stack[] = $rn->getId();
                    }

                    continue;
                }

                if ('file' === $filetype) {
                    $fullPath = (string) $doc->getFullPath(); // e.g. "document/Folder/file.ext"
                    $relPath = preg_replace('#^document/+#', '', $fullPath) ?? $fullPath;

                    $absPath = null;
                    $size = 0;

                    if ($rn) {
                        $file = $rn->getFirstResourceFile();

                        /** @var ResourceFile|null $file */
                        if ($file) {
                            $storedRel = (string) $rnRepo->getFilename($file);
                            if ('' !== $storedRel) {
                                $candidate = $resourceBase.$storedRel;
                                if (is_readable($candidate)) {
                                    $absPath = $candidate;
                                    $size = (int) $file->getSize();
                                    if ($size <= 0 && is_file($candidate)) {
                                        $st = @stat($candidate);
                                        $size = $st ? (int) $st['size'] : 0;
                                    }
                                }
                            }
                        }
                    }

                    $out[] = [
                        'id' => (int) $doc->getIid(),
                        'path' => $relPath,
                        'size' => (int) $size,
                        'title' => (string) $doc->getTitle(),
                        'abs_path' => $absPath,
                    ];
                }
            }
        }

        return $out;
    }

    public function ensureChatSystemFolder(Course $course, ?Session $session = null): ResourceNode
    {
        return $this->ensureChatSystemFolderUnderCourseRoot($course, $session);
    }

    public function findChildDocumentFolderByTitle(ResourceNode $parent, string $title): ?ResourceNode
    {
        $em = $this->em();
        $docRt = $this->getResourceType();
        $qb = $em->createQueryBuilder()
            ->select('rn')
            ->from(ResourceNode::class, 'rn')
            ->innerJoin(CDocument::class, 'd', 'WITH', 'd.resourceNode = rn')
            ->where('rn.parent = :parent AND rn.title = :title AND rn.resourceType = :rt AND d.filetype = :ft')
            ->setParameters([
                'parent' => $parent,
                'title' => $title,
                'rt' => $docRt,
                'ft' => 'folder',
            ])
            ->setMaxResults(1)
        ;

        /** @var ResourceNode|null $node */
        return $qb->getQuery()->getOneOrNullResult();
    }

    public function ensureChatSystemFolderUnderCourseRoot(Course $course, ?Session $session = null): ResourceNode
    {
        $em = $this->em();

        try {
            $courseRoot = $course->getResourceNode();
            if (!$courseRoot) {
                error_log('[CDocumentRepo.ensureChatSystemFolderUnderCourseRoot] ERROR: Course has no ResourceNode root.');

                throw new RuntimeException('Course has no ResourceNode root.');
            }
            if ($child = $this->findChildDocumentFolderByTitle($courseRoot, 'chat_conversations')) {
                return $child;
            }

            if ($docsRoot = $this->getCourseDocumentsRootNode($course)) {
                if ($legacy = $this->findChildDocumentFolderByTitle($docsRoot, 'chat_conversations')) {
                    $legacy->setParent($courseRoot);
                    $em->persist($legacy);
                    $em->flush();

                    $rnRepo = Container::$container->get(ResourceNodeRepository::class);
                    if (method_exists($rnRepo, 'rebuildPaths')) {
                        $rnRepo->rebuildPaths($courseRoot);
                    }

                    return $legacy;
                }
            }

            return $this->ensureFolder(
                $course,
                $courseRoot,
                'chat_conversations',
                ResourceLink::VISIBILITY_DRAFT,
                $session
            );
        } catch (Throwable $e) {
            error_log('[CDocumentRepo.ensureChatSystemFolderUnderCourseRoot] ERROR '.$e->getMessage());

            throw $e;
        }
    }

    public function findChildDocumentFileByTitle(ResourceNode $parent, string $title): ?ResourceNode
    {
        $em = $this->em();
        $docRt = $this->getResourceType();

        $qb = $em->createQueryBuilder()
            ->select('rn')
            ->from(ResourceNode::class, 'rn')
            ->innerJoin(CDocument::class, 'd', 'WITH', 'd.resourceNode = rn')
            ->where('rn.parent = :parent')
            ->andWhere('rn.title = :title')
            ->andWhere('rn.resourceType = :rt')
            ->andWhere('d.filetype = :ft')
            ->setParameters([
                'parent' => $parent,
                'title' => $title,
                'rt' => $docRt,
                'ft' => 'file',
            ])
            ->setMaxResults(1)
        ;

        /** @var ResourceNode|null $node */
        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * Return absolute filesystem path for a file CDocument if resolvable; null otherwise.
     */
    public function getAbsolutePathForDocument(CDocument $doc): ?string
    {
        $rn = $doc->getResourceNode();
        if (!$rn) {
            return null;
        }

        /** @var ResourceNodeRepository $rnRepo */
        $rnRepo = Container::$container->get(ResourceNodeRepository::class);
        $file = $rn->getFirstResourceFile();

        /** @var ResourceFile|null $file */
        if (!$file) {
            return null;
        }

        $storedRel = (string) $rnRepo->getFilename($file);
        if ('' === $storedRel) {
            return null;
        }

        $projectDir = Container::$container->get('kernel')->getProjectDir();
        $resourceBase = rtrim($projectDir, '/').'/var/upload/resource';

        $candidate = $resourceBase.$storedRel;

        return is_readable($candidate) ? $candidate : null;
    }

    private function safeTitle(string $name): string
    {
        $name = trim($name);
        $name = str_replace(['/', '\\'], '-', $name);

        return preg_replace('/\s+/', ' ', $name) ?: 'Untitled';
    }

    private function em(): EntityManagerInterface
    {
        /** @var EntityManagerInterface $em */
        return $this->getEntityManager();
    }

    /**
     * Returns the document folders for a given course/session/group context,
     * as [ document_iid => "Full/Path/To/Folder" ].
     *
     * This implementation uses ResourceLink as the main source,
     * assuming ResourceLink has a parent (context-aware hierarchy).
     */
    public function getAllFoldersForContext(
        Course $course,
        ?Session $session = null,
        ?CGroup $group = null,
        bool $canSeeInvisible = false,
        bool $getInvisibleList = false
    ): array {
        $em = $this->getEntityManager();

        $qb = $em->createQueryBuilder()
            ->select('d')
            ->from(CDocument::class, 'd')
            ->innerJoin('d.resourceNode', 'rn')
            ->innerJoin('rn.resourceLinks', 'rl')
            ->where('rl.course = :course')
            ->andWhere('d.filetype = :folderType')
            ->andWhere('rl.deletedAt IS NULL')
            ->setParameter('course', $course)
            ->setParameter('folderType', 'folder')
        ;

        // Session filter
        if (null !== $session) {
            $qb
                ->andWhere('rl.session = :session')
                ->setParameter('session', $session)
            ;
        } else {
            // In C2 many "global course documents" have session = NULL
            $qb->andWhere('rl.session IS NULL');
        }

        // Group filter
        if (null !== $group) {
            $qb
                ->andWhere('rl.group = :group')
                ->setParameter('group', $group)
            ;
        } else {
            $qb->andWhere('rl.group IS NULL');
        }

        // Visibility
        if (!$canSeeInvisible) {
            if ($getInvisibleList) {
                // Only non-published folders (hidden/pending/etc.)
                $qb
                    ->andWhere('rl.visibility <> :published')
                    ->setParameter('published', ResourceLink::VISIBILITY_PUBLISHED)
                ;
            } else {
                // Only visible folders
                $qb
                    ->andWhere('rl.visibility = :published')
                    ->setParameter('published', ResourceLink::VISIBILITY_PUBLISHED)
                ;
            }
        }
        // If $canSeeInvisible = true, do not filter by visibility (see everything).

        /** @var CDocument[] $documents */
        $documents = $qb->getQuery()->getResult();

        if (empty($documents)) {
            return [];
        }

        // 1) Index by ResourceLink id to be able to rebuild the path using the parent link
        $linksById = [];

        foreach ($documents as $doc) {
            if (!$doc instanceof CDocument) {
                continue;
            }

            $node = $doc->getResourceNode();
            if (!$node instanceof ResourceNode) {
                continue;
            }

            $links = $node->getResourceLinks();
            if (!$links instanceof Collection) {
                continue;
            }

            $matchingLink = null;

            foreach ($links as $candidate) {
                if (!$candidate instanceof ResourceLink) {
                    continue;
                }

                // Deleted links must be ignored
                if (null !== $candidate->getDeletedAt()) {
                    continue;
                }

                // Match same course
                if ($candidate->getCourse()?->getId() !== $course->getId()) {
                    continue;
                }

                // Match same session context
                if (null !== $session) {
                    if ($candidate->getSession()?->getId() !== $session->getId()) {
                        continue;
                    }
                } else {
                    if (null !== $candidate->getSession()) {
                        continue;
                    }
                }

                // Match same group context
                if (null !== $group) {
                    if ($candidate->getGroup()?->getIid() !== $group->getIid()) {
                        continue;
                    }
                } else {
                    if (null !== $candidate->getGroup()) {
                        continue;
                    }
                }

                // Visibility filter (when not allowed to see invisible items)
                if (!$canSeeInvisible) {
                    $visibility = $candidate->getVisibility();

                    if ($getInvisibleList) {
                        // We only want non-published items
                        if (ResourceLink::VISIBILITY_PUBLISHED === $visibility) {
                            continue;
                        }
                    } else {
                        // We only want published items
                        if (ResourceLink::VISIBILITY_PUBLISHED !== $visibility) {
                            continue;
                        }
                    }
                }

                $matchingLink = $candidate;

                break;
            }

            if (!$matchingLink instanceof ResourceLink) {
                // No valid link for this context, skip
                continue;
            }

            $linksById[$matchingLink->getId()] = [
                'doc' => $doc,
                'link' => $matchingLink,
                'node' => $node,
                'parent_id' => $matchingLink->getParent()?->getId(),
                'title' => $node->getTitle(),
            ];
        }

        if (empty($linksById)) {
            return [];
        }

        // 2) Build full folder paths per context (using ResourceLink.parent)
        $pathCache = [];
        $folders = [];

        foreach ($linksById as $id => $data) {
            $path = $this->buildFolderPathForLink($id, $linksById, $pathCache);

            if ('' === $path) {
                continue;
            }

            /** @var CDocument $doc */
            $doc = $data['doc'];

            // Keep the key as CDocument iid (as before)
            $folders[$doc->getIid()] = $path;
        }

        if (empty($folders)) {
            return [];
        }

        // Natural sort so that paths appear in a human-friendly order
        natsort($folders);

        // If the caller explicitly requested the invisible list, the filtering was done above
        return $folders;
    }

    /**
     * Rebuild the "Parent folder/Child folder/..." path for a folder ResourceLink,
     * walking up the parent chain until a link without parent is found.
     *
     * Uses a small cache to avoid recalculating the same paths many times.
     *
     * @param array<int, array<string,mixed>> $linksById
     * @param array<int, string>              $pathCache
     */
    private function buildFolderPathForLink(
        int $id,
        array $linksById,
        array &$pathCache
    ): string {
        if (isset($pathCache[$id])) {
            return $pathCache[$id];
        }

        if (!isset($linksById[$id])) {
            return $pathCache[$id] = '';
        }

        $current = $linksById[$id];
        $segments = [$current['title']];

        $parentId = $current['parent_id'] ?? null;
        $guard = 0;

        while (null !== $parentId && isset($linksById[$parentId]) && $guard < 50) {
            $parent = $linksById[$parentId];
            array_unshift($segments, $parent['title']);
            $parentId = $parent['parent_id'] ?? null;
            $guard++;
        }

        $path = implode('/', $segments);

        return $pathCache[$id] = $path;
    }

    /**
     * Compute document storage usage breakdown for a course.
     *
     * - Counts only the "document" tool (resource_type_group = document resource type id).
     * - Deduplicates by ResourceFile ID to avoid double counting when the same file is linked multiple times.
     * - Classifies each file into the most specific context:
     *   group > session > course
     *
     * @return array{
     *   course: int,
     *   sessions: int,
     *   groups: int,
     *   used: int
     * }
     */
    public function getDocumentUsageBreakdownByCourse(Course $course): array
    {
        $courseId = (int) $course->getId();
        $typeGroupId = (int) $this->getResourceType()->getId();

        $conn = $this->getEntityManager()->getConnection();

        $sql = <<<'SQL'
SELECT
    rf.id        AS file_id,
    rf.size      AS file_size,
    rl.session_id AS session_id,
    rl.group_id   AS group_id
FROM resource_file rf
INNER JOIN resource_node rn ON rn.id = rf.resource_node_id
INNER JOIN resource_link rl ON rl.resource_node_id = rn.id
WHERE rl.deleted_at IS NULL
  AND rl.c_id = :courseId
  AND rl.resource_type_group = :typeGroupId
  AND rf.size IS NOT NULL
SQL;

        $rows = $conn->fetchAllAssociative($sql, [
            'courseId' => $courseId,
            'typeGroupId' => $typeGroupId,
        ]);

        $fileSizes = [];   // file_id => size
        $hasSession = [];  // file_id => bool
        $hasGroup = [];    // file_id => bool

        foreach ($rows as $row) {
            $fileId = (int) ($row['file_id'] ?? 0);
            if ($fileId <= 0) {
                continue;
            }

            $size = (int) ($row['file_size'] ?? 0);

            if (!isset($fileSizes[$fileId])) {
                $fileSizes[$fileId] = $size;
                $hasSession[$fileId] = false;
                $hasGroup[$fileId] = false;
            }

            $sid = (int) ($row['session_id'] ?? 0);
            $gid = (int) ($row['group_id'] ?? 0);

            if ($sid > 0) {
                $hasSession[$fileId] = true;
            }
            if ($gid > 0) {
                $hasGroup[$fileId] = true;
            }
        }

        $bytesCourse = 0;
        $bytesSessions = 0;
        $bytesGroups = 0;

        foreach ($fileSizes as $fileId => $size) {
            if (($hasGroup[$fileId] ?? false) === true) {
                $bytesGroups += $size;

                continue;
            }

            if (($hasSession[$fileId] ?? false) === true) {
                $bytesSessions += $size;

                continue;
            }

            $bytesCourse += $size;
        }

        $used = $bytesCourse + $bytesSessions + $bytesGroups;

        return [
            'course' => $bytesCourse,
            'sessions' => $bytesSessions,
            'groups' => $bytesGroups,
            'used' => $used,
        ];
    }
}
