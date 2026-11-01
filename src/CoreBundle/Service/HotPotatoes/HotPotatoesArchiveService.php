<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\HotPotatoes;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ResourceFile;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Helpers\CourseFromRequestHelper;
use Chamilo\CoreBundle\Helpers\IsAllowedToEditHelper;
use Chamilo\CoreBundle\Helpers\ResourceFileHelper;
use Chamilo\CoreBundle\Repository\ResourceNodeRepository;
use Chamilo\CoreBundle\Security\Authorization\Voter\CourseVoter;
use Chamilo\CoreBundle\Security\Authorization\Voter\GroupVoter;
use Chamilo\CoreBundle\Security\Authorization\Voter\SessionVoter;
use Chamilo\CourseBundle\Entity\CDocument;
use Chamilo\CourseBundle\Entity\CGroup;
use Chamilo\CourseBundle\Entity\CLpItem;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Hot Potatoes resources are CDocument resources containing a ZIP. The marker is
 * stored in ResourceFile metadata, not in a CQuiz or a user-controlled filename.
 */
final readonly class HotPotatoesArchiveService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private CourseFromRequestHelper $courseFromRequestHelper,
        private RequestStack $requestStack,
        private IsAllowedToEditHelper $isAllowedToEditHelper,
        private ResourceFileHelper $resourceFileHelper,
        private ResourceNodeRepository $resourceNodeRepository,
        private Security $security,
    ) {}

    public static function isHotPotatoesDocument(CDocument $document): bool
    {
        $node = $document->getResourceNode();
        if (null === $node) {
            return false;
        }

        foreach ($node->getResourceFiles() as $file) {
            if (isset($file->getMetadata()['chamilo_hotpotatoes']['entrypoints'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array{0: Course, 1: ?Session, 2: ?CGroup, 3: bool}
     */
    public function context(): array
    {
        $request = $this->requestStack->getCurrentRequest();
        if (null === $request || null === $this->courseFromRequestHelper->getCourseReference($request)) {
            throw new BadRequestHttpException('A valid course id is required.');
        }

        // Never rely on the mutable PHP session to identify this API request's course.
        // Other concurrent API requests can clear the session-based CidReq context.
        $course = $this->courseFromRequestHelper->resolveCourse($request);
        if (!$course instanceof Course) {
            throw new NotFoundHttpException('The requested course does not exist.');
        }
        if (!$this->security->isGranted(CourseVoter::VIEW, $course)) {
            throw new AccessDeniedHttpException('Access to this course is denied.');
        }

        $sessionId = $this->courseFromRequestHelper->getSessionId($request);
        $session = null;
        if (null !== $sessionId) {
            $session = $this->entityManager->getRepository(Session::class)->find($sessionId);
            if (!$session instanceof Session) {
                throw new NotFoundHttpException('The session does not exist.');
            }
            if (!$session->hasCourse($course)) {
                throw new AccessDeniedHttpException('The session does not belong to this course.');
            }
            $session->setCurrentCourse($course);
            if (!$this->security->isGranted(SessionVoter::VIEW, $session)) {
                throw new AccessDeniedHttpException('Access to this session is denied.');
            }
        }

        $groupId = $this->courseFromRequestHelper->getGroupId($request);
        $group = null;
        if (null !== $groupId) {
            $group = $this->entityManager->getRepository(CGroup::class)->find($groupId);
            if (!$group instanceof CGroup) {
                throw new NotFoundHttpException('The group does not exist.');
            }
            $node = $group->getResourceNode();
            if (null === $node || $node->getParent()?->getId() !== $course->getResourceNode()?->getId()) {
                throw new AccessDeniedHttpException('The group does not belong to this course.');
            }
            $group->setParent($course);
            if (!$this->security->isGranted(GroupVoter::VIEW, $group)) {
                throw new AccessDeniedHttpException('Access to this group is denied.');
            }
        }

        return [$course, $session, $group, $this->isAllowedToEditHelper->check(course: $course, session: $session)];
    }

    /**
     * @return list<CDocument>
     */
    public function listDocuments(Course $course, ?Session $session, ?CGroup $group): array
    {
        $qb = $this->entityManager->createQueryBuilder()
            ->select('DISTINCT document')
            ->from(CDocument::class, 'document')
            ->innerJoin('document.resourceNode', 'node')
            ->innerJoin('node.resourceLinks', 'link')
            ->andWhere('IDENTITY(link.course) = :courseId')
            ->andWhere('link.deletedAt IS NULL')
            ->setParameter('courseId', $course->getId())
            ->orderBy('document.title', 'ASC')
        ;
        if ($session instanceof Session) {
            $qb->andWhere('(IDENTITY(link.session) = :sid OR link.session IS NULL)')
                ->setParameter('sid', $session->getId())
            ;
        } else {
            $qb->andWhere('link.session IS NULL');
        }
        if ($group instanceof CGroup) {
            $qb->andWhere('IDENTITY(link.group) = :gid')->setParameter('gid', $group->getIid());
        } else {
            $qb->andWhere('link.group IS NULL');
        }

        return array_values(array_filter($qb->getQuery()->getResult(), self::isHotPotatoesDocument(...)));
    }

    /**
     * Match a specific active course/session/group link, including nullable fields.
     * ResourceNode::getResourceLinkByContext() does not constrain absent fields
     * and can return an inherited or deleted link.
     */
    public function findExactActiveLink(
        CDocument $document,
        Course $course,
        ?Session $session,
        ?CGroup $group,
    ): ?ResourceLink {
        $node = $document->getResourceNode();
        if (null === $node) {
            return null;
        }

        $links = [];
        foreach ($node->getResourceLinks() as $link) {
            if (null !== $link->getDeletedAt()
                || (int) ($link->getCourse()?->getId() ?? 0) !== (int) $course->getId()
                || (int) ($link->getSession()?->getId() ?? 0) !== (int) ($session?->getId() ?? 0)
                || (int) ($link->getGroup()?->getIid() ?? 0) !== (int) ($group?->getIid() ?? 0)
                || null !== $link->getUser()
                || null !== $link->getUserGroup()
            ) {
                continue;
            }
            $links[] = $link;
        }

        return 1 === \count($links) ? $links[0] : null;
    }

    /**
     * Count links from any learning path, not only the current course or session.
     * A shared archive must not be deleted while any LP item still uses it.
     */
    public function countLearningPathReferences(int $documentId): int
    {
        if ($documentId <= 0) {
            return 0;
        }

        return (int) $this->entityManager->createQueryBuilder()
            ->select('COUNT(item.iid)')
            ->from(CLpItem::class, 'item')
            ->andWhere('item.itemType IN (:types)')
            ->andWhere('item.path = :documentId')
            ->setParameter('types', ['hotpotatoes', 'document', 'video'])
            ->setParameter('documentId', (string) $documentId)
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    /**
     * @return array{0: CDocument, 1: ResourceFile}
     */
    public function findVisible(int $documentId, Course $course, ?Session $session, ?CGroup $group, bool $canManage): array
    {
        $document = $this->entityManager->getRepository(CDocument::class)->find($documentId);
        if (!$document instanceof CDocument || !self::isHotPotatoesDocument($document)) {
            throw new NotFoundHttpException('Hot Potatoes activity not found.');
        }

        $node = $document->getResourceNode();
        $link = $this->findExactActiveLink($document, $course, $session, $group);
        if (null === $link && null !== $session && null === $group) {
            $link = $this->findExactActiveLink($document, $course, null, null);
        }
        if (!$link instanceof ResourceLink || !$this->security->isGranted('VIEW', $node)) {
            throw new AccessDeniedHttpException('Hot Potatoes activity is not accessible in this context.');
        }
        if (!$canManage && ResourceLink::VISIBILITY_PUBLISHED !== $link->getVisibility()) {
            throw new AccessDeniedHttpException('Hot Potatoes activity is not published.');
        }

        $file = $this->resourceFileHelper->resolveResourceFileByAccessUrl($node);
        if (!$file instanceof ResourceFile || !isset($file->getMetadata()['chamilo_hotpotatoes']['entrypoints'])) {
            throw new NotFoundHttpException('Hot Potatoes archive is missing.');
        }

        return [$document, $file];
    }

    /**
     * @return resource
     */
    public function archiveStream(CDocument $document, ResourceFile $file)
    {
        return $this->resourceNodeRepository->getResourceNodeFileStream($document->getResourceNode(), $file);
    }
}
