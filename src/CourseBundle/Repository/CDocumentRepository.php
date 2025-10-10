<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Repository;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Entity\ResourceNode;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Repository\ResourceRepository;
use Chamilo\CourseBundle\Entity\CDocument;
use Chamilo\CourseBundle\Entity\CGroup;
use Chamilo\CourseBundle\Entity\CLp;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use RuntimeException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

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
        // Return if a child with this title already exists under the parent
        if ($child = $this->findChildNodeByTitle($parent, $folderTitle)) {
            return $child;
        }

        /** @var User|null $user */
        $user = api_get_user_entity();

        $doc = new CDocument();
        $doc->setTitle($folderTitle);
        $doc->setFiletype('folder');

        // IMPORTANT: attach to the given parent, not to the course root
        $doc->setParentResourceNode($parent->getId());

        // Link to course (and optional session)
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
     * Fetches all document data for the given user/group using Doctrine ORM.
     *
     * @return CDocument[]
     */
    public function getAllDocumentDataByUserAndGroup(
        Course $course,
        string $path = '/',
        int $toGroupId = 0,
        ?int $toUserId = null,
        bool $search = false,
        ?Session $session = null
    ): array {
        $qb = $this->createQueryBuilder('d');

        $qb->innerJoin('d.resourceNode', 'rn')
            ->innerJoin('rn.resourceLinks', 'rl')
            ->where('rl.course = :course')
            ->setParameter('course', $course)
        ;

        // Session filtering
        if ($session) {
            $qb->andWhere('(rl.session = :session OR rl.session IS NULL)')
                ->setParameter('session', $session)
            ;
        } else {
            $qb->andWhere('rl.session IS NULL');
        }

        // Path filtering - convert document.lib.php logic to Doctrine
        if ('/' !== $path) {
            // The original uses LIKE with path patterns
            $pathPattern = rtrim($path, '/').'/%';
            $qb->andWhere('rn.title LIKE :pathPattern OR rn.title = :exactPath')
                ->setParameter('pathPattern', $pathPattern)
                ->setParameter('exactPath', ltrim($path, '/'))
            ;

            // Exclude deeper nested paths if not searching
            if (!$search) {
                // Exclude paths with additional slashes beyond the current level
                $excludePattern = rtrim($path, '/').'/%/%';
                $qb->andWhere('rn.title NOT LIKE :excludePattern')
                    ->setParameter('excludePattern', $excludePattern)
                ;
            }
        }

        // User/Group filtering
        if (null !== $toUserId) {
            if ($toUserId > 0) {
                $qb->andWhere('rl.user = :userId')
                    ->setParameter('userId', $toUserId)
                ;
            } else {
                $qb->andWhere('rl.user IS NULL');
            }
        } else {
            if ($toGroupId > 0) {
                $qb->andWhere('rl.group = :groupId')
                    ->setParameter('groupId', $toGroupId)
                ;
            } else {
                $qb->andWhere('rl.group IS NULL');
            }
        }

        // Exclude deleted documents (like %_DELETED_% in original)
        $qb->andWhere('rn.title NOT LIKE :deletedPattern')
            ->setParameter('deletedPattern', '%_DELETED_%')
        ;

        // Order by creation date (equivalent to last.iid DESC)
        $qb->orderBy('rn.createdAt', 'DESC')
            ->addOrderBy('rn.id', 'DESC')
        ;

        return $qb->getQuery()->getResult();
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
}
