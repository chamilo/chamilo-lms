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
            ->getResult();
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
            ->setParameter('filetype', $fileType);
    }

    /**
     * Register the original SCORM ZIP under:
     *   /Learning paths/SCORM - {lp_id} - {lp_title}/{lp_title}.zip
     * Folder is teacher-only (DRAFT visibility by default).
     */
    public function registerScormZip(Course $course, CLp $lp, UploadedFile $zip): void
    {
        $em    = $this->em();
        $root  = $this->ensureCourseDocumentsRootNode($course);

        // Top folder for learning paths (use PUBLISHED if you want to see it without teacher role)
        $lpTop = $this->ensureFolder(
            $course,
            $root,
            'Learning paths',
            ResourceLink::VISIBILITY_DRAFT
        );

        // Subfolder per LP
        $lpFolderTitle = \sprintf('SCORM - %d - %s', $lp->getIid(), $this->safeTitle($lp->getTitle()));
        $lpFolder = $this->ensureFolder(
            $course,
            $lpTop,
            $lpFolderTitle,
            ResourceLink::VISIBILITY_DRAFT
        );

        // Store the ZIP as a "file" document under the LP folder
        $this->createFileInFolder(
            $course,
            $lpFolder,
            $zip,
            \sprintf('SCORM ZIP for LP #%d', $lp->getIid()),
            ResourceLink::VISIBILITY_DRAFT
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

        // Newer layout: folders directly under the course root
        $courseRoot = $course->getResourceNode();
        if ($courseRoot) {
            // SCORM folder directly under course root
            if ($this->tryDeleteFirstFolderByTitlePrefix($courseRoot, $prefix)) {
                $em->flush();
                return;
            }

            // SCORM folder under "Learning paths" (which itself is under course root)
            $lpTop = $this->findChildNodeByTitle($courseRoot, 'Learning paths');
            if ($lpTop && $this->tryDeleteFirstFolderByTitlePrefix($lpTop, $prefix)) {
                $em->flush();
                return;
            }
        }

        // Legacy layout: under Documents → Learning paths
        $docsRoot = $this->getCourseDocumentsRootNode($course);
        if ($docsRoot) {
            $lpTop = $this->findChildNodeByTitle($docsRoot, 'Learning paths');
            if ($lpTop && $this->tryDeleteFirstFolderByTitlePrefix($lpTop, $prefix)) {
                $em->flush();
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
            ->setMaxResults(1);

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
        $em  = $this->em();
        $rt  = $this->getResourceType();
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
                    'rtype'  => $rt,
                    'course' => $course,
                ])
                ->setMaxResults(1)
                ->getOneOrNullResult();

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
            ->getOneOrNullResult();
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

        $em   = $this->em();
        $type = $this->getResourceType();
        /** @var User|null $user */
        $user = \api_get_user_entity();

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
     * Create (if missing) a folder under $parent using the API path:
     * create a CDocument(folder) + setParentResourceNode + setResourceLinkArray and let
     * the ResourceListener build the ResourceNode.
     */
    public function ensureFolder(
        Course $course,
        ResourceNode $parent,
        string $folderTitle,
        int $visibility = ResourceLink::VISIBILITY_DRAFT
    ): ResourceNode {
        if ($child = $this->findChildNodeByTitle($parent, $folderTitle)) {
            return $child;
        }

        /** @var User|null $user */
        $user = \api_get_user_entity();

        $doc = new CDocument();
        $doc->setTitle($folderTitle);
        $doc->setFiletype('folder');
        $doc->setParentResourceNode($course->getResourceNode()->getId());
        $doc->setResourceLinkArray([[
            'cid' => $course->getId(),
            'visibility' => $visibility,
        ]]);
        if ($user) {
            $doc->setCreator($user);
        }

        $em = $this->em();
        $em->persist($doc);
        $em->flush();

        return $doc->getResourceNode();
    }

    /**
     * Create a file under $parent using the API path:
     * CDocument(file) + setUploadFile + setParentResourceNode + setResourceLinkArray.
     */
    public function createFileInFolder(
        Course $course,
        ResourceNode $parent,
        UploadedFile $uploaded,
        string $comment,
        int $visibility
    ): ResourceNode {
        /** @var User|null $user */
        $user = \api_get_user_entity();

        $title = $uploaded->getClientOriginalName();

        $doc = new CDocument();
        $doc->setTitle($title);
        $doc->setFiletype('file');
        $doc->setComment($comment);
        $doc->setParentResourceNode($parent->getId());
        $doc->setResourceLinkArray([[
            'cid' => $course->getId(),
            'visibility' => $visibility,
        ]]);
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
            ->findOneBy(['parent' => $parent, 'title' => $title]);
    }

    private function safeTitle(string $name): string
    {
        $name = \trim($name);
        $name = \str_replace(['/', '\\'], '-', $name);
        return \preg_replace('/\s+/', ' ', $name) ?: 'Untitled';
    }

    private function em(): EntityManagerInterface
    {
        /** @var EntityManagerInterface $em */
        $em = $this->getEntityManager();
        return $em;
    }

}
