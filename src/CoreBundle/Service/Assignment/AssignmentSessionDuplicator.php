<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\Assignment;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Entity\ResourceNode;
use Chamilo\CoreBundle\Entity\ResourceRight;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\AccessUrlHelper;
use Chamilo\CoreBundle\Helpers\AccessUrlScopeHelper;
use Chamilo\CourseBundle\Entity\CStudentPublication;
use Chamilo\CourseBundle\Entity\CStudentPublicationAssignment;
use Chamilo\CourseBundle\Entity\CStudentPublicationRelDocument;
use Chamilo\CourseBundle\Entity\CStudentPublicationRelUser;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use LogicException;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Throwable;

/**
 * Internal duplication primitive. Callers must authorize the requested course,
 * session and source before invoking it. Never call it from a GET provider.
 * Group-scoped assignments require a separate group mapping and are rejected.
 */
final readonly class AssignmentSessionDuplicator
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private Security $security,
        private AccessUrlHelper $accessUrlHelper,
        private AccessUrlScopeHelper $accessUrlScope,
    ) {}

    /**
     * Administrative imports must not copy resources from an unrelated portal.
     * LP callers authorize their own course/session context instead.
     */
    public function assertAdministrativeCourseScope(Course $course): void
    {
        if (!$this->accessUrlHelper->isMultiple()) {
            return;
        }

        $actor = $this->security->getUser();
        if ($actor instanceof User) {
            if ($this->security->isGranted('ROLE_ADMIN') && $this->accessUrlScope->isCourseManaged($actor, (int) $course->getId())) {
                return;
            }

            $url = $this->accessUrlHelper->getCurrent();
            if (null !== $url && $url->hasUser($actor) && $url->hasCourse($course)) {
                return;
            }
        }

        throw new AccessDeniedHttpException('The course is outside the current administrative portal scope.');
    }

    /**
     * Copy base assignments for an already subscribed course. Callers own the outer transaction.
     */
    public function duplicateCourseAssignments(Course $course, Session $session): void
    {
        $assignments = $this->entityManager->getRepository(CStudentPublication::class)->createQueryBuilder('assignment')
            ->join('assignment.resourceNode', 'node')
            ->join('node.resourceLinks', 'link')
            ->where("assignment.filetype = 'folder'")
            ->andWhere('assignment.publicationParent IS NULL')
            ->andWhere('assignment.active = 1')
            ->andWhere('link.course = :course AND link.session IS NULL AND link.deletedAt IS NULL')
            ->setParameter('course', (int) $course->getId())
            ->orderBy('assignment.iid', 'ASC')
            ->getQuery()
            ->getResult()
        ;

        foreach ($assignments as $assignment) {
            $this->getOrCreate($assignment, $course, $session);
        }
    }

    public function duplicateGradebookAssignment(ResourceNode $node, Course $course, Session $session): CStudentPublication
    {
        $original = $this->entityManager->getRepository(CStudentPublication::class)->findOneBy(['resourceNode' => $node]);
        if (!$original instanceof CStudentPublication) {
            throw new InvalidArgumentException('The gradebook assignment could not be found.');
        }

        return $this->getOrCreate($original, $course, $session);
    }

    public function findExactCourseLink(CStudentPublication $assignment, Course $course, ?Session $session): ?ResourceLink
    {
        foreach ($assignment->getResourceNode()?->getResourceLinks() ?? [] as $link) {
            if ($link->getCourse()?->getId() === $course->getId()
                && $link->getSession()?->getId() === $session?->getId()
                && null === $link->getGroup()
                && null === $link->getUserGroup()
                && null === $link->getUser()
                && null === $link->getDeletedAt()
            ) {
                return $link;
            }
        }

        return null;
    }

    public function isDuplicableSource(CStudentPublication $original, Course $course): bool
    {
        $baseLink = $this->findExactCourseLink($original, $course, null);
        if ('folder' !== $original->getFiletype()
            || null !== $original->getPublicationParent()
            || !\in_array($original->getActive(), [0, 1], true)
            || 0 !== $original->getGroupCategoryWorkId()
            || 0 !== $original->getPostGroupId()
            || null !== $original->getGroupCategory()
            || null === $baseLink
            || null !== $baseLink->getStartVisibilityAt()
            || null !== $baseLink->getEndVisibilityAt()
        ) {
            return false;
        }

        // Never broaden the scope of a source with additional group/user links.
        foreach ($original->getResourceNode()->getResourceLinks() as $link) {
            if (null === $link->getDeletedAt() && $link !== $baseLink) {
                return false;
            }
        }

        return true;
    }

    public function findExisting(CStudentPublication $original, Course $course, Session $session): ?CStudentPublication
    {
        return $this->findCopy($original, $course, $session, false);
    }

    /**
     * The original is locked for the duration of both the lookup and the insert.
     */
    public function getOrCreate(CStudentPublication $original, Course $course, Session $session): CStudentPublication
    {
        if (!$this->entityManager->contains($original)
            || !$this->entityManager->contains($course)
            || !$this->entityManager->contains($session)
            || null === $original->getIid()
            || null === $course->getId()
            || null === $session->getId()
            || !$session->hasCourse($course)
        ) {
            throw new InvalidArgumentException('A managed assignment, course and subscribed session are required.');
        }

        $this->entityManager->beginTransaction();

        try {
            // Reload under the lock: a legacy soft deletion may have changed
            // the database while the original was already managed in memory.
            $this->entityManager->refresh($original, LockMode::PESSIMISTIC_WRITE);
            $baseLink = $this->findExactCourseLink($original, $course, null);
            if (null === $baseLink || !$this->isDuplicableSource($original, $course)) {
                throw new InvalidArgumentException('The source must be an active, unrestricted-context base assignment without a visibility schedule or additional resource links.');
            }

            $existing = $this->findCopy($original, $course, $session, true);
            if (null !== $existing) {
                $this->entityManager->commit();

                return $existing;
            }

            // The learner who triggers lazy creation must never become the owner of
            // the new assignment. Keep the original owner and creator instead.
            $copyLink = (new ResourceLink())
                ->setCourse($course)
                ->setSession($session)
                ->setVisibility($baseLink->getVisibility())
            ;
            foreach ($baseLink->getResourceRights() as $right) {
                $copyLink->addResourceRight((new ResourceRight())
                    ->setRole($right->getRole())
                    ->setMask((int) $right->getMask()));
            }

            $copy = (new CStudentPublication())
                ->setTitle($original->getTitle())
                ->setDescription($original->getDescription() ?? '')
                ->setActive($original->getActive())
                ->setAccepted($original->getAccepted() ?? true)
                ->setFiletype('folder')
                ->setQualification($original->getQualification())
                ->setWeight($original->getWeight())
                ->setAllowTextAssignment($original->getAllowTextAssignment())
                ->setDuration($original->getDuration())
                ->setStudentDeleteOwnPublication($original->getStudentDeleteOwnPublication())
                ->setDefaultVisibility($original->getDefaultVisibility())
                ->setExtensions($original->getExtensions())
                ->setUser($original->getUser())
                ->setCreator($original->getCreator() ?? $original->getUser())
                ->setPublicationParent($original)
                ->setParent($course)
                ->addLink($copyLink)
            ;

            $copy->setAssignment(new CStudentPublicationAssignment());
            if (null !== $original->getAssignment()) {
                $copy->getAssignment()->setEnableQualification($original->getAssignment()->getEnableQualification());
            }
            $copy->setViewProperties(true);
            $this->entityManager->persist($copy);
            $this->entityManager->flush();

            $copy->setHasProperties((int) $copy->getAssignment()->getIid());
            $copy->getResourceNode()->setLanguage($original->getResourceNode()->getLanguage());

            // Copy associations, not submission rows, results, comments or calendar events.
            foreach ($this->entityManager->getRepository(CStudentPublicationRelUser::class)->findBy(['publication' => $original]) as $relation) {
                $this->entityManager->persist((new CStudentPublicationRelUser())
                    ->setPublication($copy)
                    ->setUser($relation->getUser()));
            }
            foreach ($this->entityManager->getRepository(CStudentPublicationRelDocument::class)->findBy(['publication' => $original]) as $relation) {
                $this->entityManager->persist((new CStudentPublicationRelDocument())
                    ->setPublication($copy)
                    ->setDocument($relation->getDocument()));
            }

            $this->entityManager->flush();
            $this->entityManager->commit();

            return $copy;
        } catch (Throwable $exception) {
            $this->entityManager->rollback();

            throw $exception;
        }
    }

    private function findCopy(CStudentPublication $original, Course $course, Session $session, bool $lock): ?CStudentPublication
    {
        $query = $this->entityManager->getRepository(CStudentPublication::class)->createQueryBuilder('assignment')
            ->join('assignment.resourceNode', 'node')
            ->join('node.resourceLinks', 'link')
            ->where('assignment.publicationParent = :original')
            ->andWhere("assignment.filetype = 'folder'")
            ->andWhere('assignment.active IN (0, 1)')
            ->andWhere('link.course = :course')
            ->andWhere('link.session = :session')
            ->andWhere('link.group IS NULL AND link.userGroup IS NULL AND link.user IS NULL AND link.deletedAt IS NULL')
            ->setParameter('original', (int) $original->getIid())
            ->setParameter('course', (int) $course->getId())
            ->setParameter('session', (int) $session->getId())
            ->getQuery()
        ;

        if ($lock) {
            // A locking read also sees a copy committed by a preceding concurrent writer.
            $query->setLockMode(LockMode::PESSIMISTIC_WRITE);
        }

        $copies = $query->getResult();
        if (\count($copies) > 1) {
            throw new LogicException('Multiple active copies of the assignment exist in the same session.');
        }

        return $copies[0] ?? null;
    }
}
