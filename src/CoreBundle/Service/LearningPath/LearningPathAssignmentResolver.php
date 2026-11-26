<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\LearningPath;

use Chamilo\CoreBundle\Entity\AbstractResource;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\SessionVisibilityHelper;
use Chamilo\CoreBundle\Service\Assignment\AssignmentSessionDuplicator;
use Chamilo\CoreBundle\Service\Assignment\MobileAssignmentSubmissionAccess;
use Chamilo\CourseBundle\Entity\CGroup;
use Chamilo\CourseBundle\Entity\CLpItem;
use Chamilo\CourseBundle\Entity\CStudentPublication;
use Chamilo\CourseBundle\Repository\CStudentPublicationRepository;
use InvalidArgumentException;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

/** Resolves assignment items after the runtime has checked access to the learning path. */
final readonly class LearningPathAssignmentResolver
{
    public function __construct(
        private CStudentPublicationRepository $repository,
        private AssignmentSessionDuplicator $duplicator,
        private MobileAssignmentSubmissionAccess $submissionAccess,
        private SessionVisibilityHelper $sessionVisibilityHelper,
        private Security $security,
    ) {}

    public function resolve(CLpItem $item, Course $course, ?Session $session, ?CGroup $group, bool $canManage): ?CStudentPublication
    {
        $assignment = $this->getAssignment($item);
        if (!$assignment instanceof CStudentPublication) {
            return null;
        }
        if (null !== $session && !$session->hasCourse($course)) {
            return null;
        }
        if (null !== $this->findExactLink($assignment, $course, $session, $group)) {
            return $this->isReadable($assignment, $course, $session, $group, $canManage) ? $assignment : null;
        }
        if (null === $session || null !== $group || !$this->isBaseAssignmentItem($item, $assignment, $course)) {
            return null;
        }

        // A GET never creates a copy and never returns the base assignment in a session.
        $copy = $this->duplicator->findExisting($assignment, $course, $session);

        return null !== $copy && $this->isReadable($copy, $course, $session, null, $canManage) ? $copy : null;
    }

    /**
     * Called only by the authorized runtime item POST, before recording item progress.
     */
    public function prepare(CLpItem $item, Course $course, ?Session $session, ?CGroup $group, User $user, bool $canEdit): void
    {
        if (null === $session || null !== $group || $canEdit) {
            return;
        }
        $assignment = $this->getAssignment($item);
        if (!$assignment instanceof CStudentPublication
            || null !== $this->findExactLink($assignment, $course, $session, null)
            || !$this->isBaseAssignmentItem($item, $assignment, $course)
        ) {
            return;
        }
        if (!$session->hasCourse($course) || !$session->hasUserInCourse($user, $course, Session::STUDENT)) {
            throw new AccessDeniedHttpException('You are not enrolled in this course session.');
        }
        if (null !== $this->duplicator->findExisting($assignment, $course, $session)) {
            return;
        }
        // Do not create resources for read-only sessions, hidden assignments or learners
        // excluded by the assignment's individual recipient list.
        if (!\in_array($this->sessionVisibilityHelper->getSessionVisibility($session, $user), [Session::AVAILABLE, Session::VISIBLE], true)
            || !$this->isReadable($assignment, $course, null, null, false)
        ) {
            throw new AccessDeniedHttpException('This assignment is not available for duplication in the current session.');
        }

        try {
            $this->duplicator->getOrCreate($assignment, $course, $session);
        } catch (InvalidArgumentException $exception) {
            throw new UnprocessableEntityHttpException('This assignment requires manual duplication in the session.', $exception);
        }
    }

    private function getAssignment(CLpItem $item): ?CStudentPublication
    {
        $path = (string) $item->getPath();
        if (!\in_array(strtolower(trim($item->getItemType())), ['student_publication', 'assignments'], true)
            || !ctype_digit($path) || (int) $path <= 0
        ) {
            return null;
        }
        $assignment = $this->repository->find((int) $path);

        return $assignment instanceof CStudentPublication && 'folder' === $assignment->getFiletype()
            && \in_array($assignment->getActive(), [0, 1], true) ? $assignment : null;
    }

    private function isBaseAssignmentItem(CLpItem $item, CStudentPublication $assignment, Course $course): bool
    {
        return null === $assignment->getPublicationParent()
            && null !== $this->findExactLink($assignment, $course, null, null)
            && null !== $this->findExactLink($item->getLp(), $course, null, null);
    }

    private function findExactLink(AbstractResource $resource, Course $course, ?Session $session, ?CGroup $group): ?ResourceLink
    {
        foreach ($resource->getResourceNode()?->getResourceLinks() ?? [] as $link) {
            if ($link->getCourse()?->getId() === $course->getId()
                && $link->getSession()?->getId() === $session?->getId()
                && $link->getGroup()?->getIid() === $group?->getIid()
                && null === $link->getUserGroup()
                && null === $link->getUser()
                && null === $link->getDeletedAt()
            ) {
                return $link;
            }
        }

        return null;
    }

    private function isReadable(CStudentPublication $assignment, Course $course, ?Session $session, ?CGroup $group, bool $canManage): bool
    {
        $node = $assignment->getResourceNode();
        if (null === $node || !$this->security->isGranted('VIEW', $node)) {
            return false;
        }
        if ($canManage) {
            return true;
        }

        try {
            $this->submissionAccess->resolveVisibleAssignment(
                (int) $assignment->getIid(),
                $course,
                $session,
                (int) ($group?->getIid() ?? 0),
            );
        } catch (NotFoundHttpException) {
            return false;
        }

        return true;
    }
}
