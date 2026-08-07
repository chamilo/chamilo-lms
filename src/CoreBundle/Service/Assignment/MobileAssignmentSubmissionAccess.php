<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\Assignment;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\CourseRelUser;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\SessionRelCourseRelUser;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\UserHelper;
use Chamilo\CoreBundle\Security\Authorization\Voter\CourseVoter;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CStudentPublication;
use Chamilo\CourseBundle\Repository\CStudentPublicationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final readonly class MobileAssignmentSubmissionAccess
{
    public function __construct(
        private CStudentPublicationRepository $studentPublicationRepository,
        private EntityManagerInterface $entityManager,
        private SettingsManager $settingsManager,
        private Security $security,
        private UserHelper $userHelper,
    ) {}

    /**
     * @return array{user: User, course: Course, session: ?Session}
     */
    public function resolveCourseContext(int $courseId, ?int $sessionId): array
    {
        $user = $this->userHelper->getCurrent();

        if (!$user instanceof User || null === $user->getId()) {
            throw new AccessDeniedHttpException('An authenticated student is required.');
        }

        $course = $this->entityManager->find(Course::class, $courseId);

        if (!$course instanceof Course || !$this->security->isGranted(CourseVoter::VIEW, $course)) {
            throw new AccessDeniedHttpException('You do not have access to this course.');
        }

        $session = null;

        if (null !== $sessionId) {
            $session = $this->entityManager->find(Session::class, $sessionId);

            if (!$session instanceof Session) {
                throw new NotFoundHttpException('Session not found.');
            }
        }

        $this->assertStudentSubscribed($user, $course, $session);

        return [
            'user' => $user,
            'course' => $course,
            'session' => $session,
        ];
    }

    public function resolveVisibleAssignment(
        int $assignmentId,
        Course $course,
        ?Session $session,
    ): CStudentPublication {
        $assignment = $this->findVisibleAssignment($assignmentId, $course, $session);
        if ($assignment instanceof CStudentPublication) {
            return $assignment;
        }

        if ($session instanceof Session) {
            $candidate = $this->studentPublicationRepository->find($assignmentId);
            $sessionLink = $candidate instanceof CStudentPublication
                ? $candidate->getResourceNode()?->getResourceLinkByContext($course, $session)
                : null;

            if (null === $sessionLink) {
                $assignment = $this->findVisibleAssignment($assignmentId, $course, null);
                if ($assignment instanceof CStudentPublication) {
                    return $assignment;
                }
            }
        }

        throw new NotFoundHttpException('Assignment not found in the current course context.');
    }

    public function resolveOwnedSubmission(
        int $submissionId,
        int $assignmentId,
        User $user,
        Course $course,
        ?Session $session,
    ): CStudentPublication {
        $assignment = $this->resolveVisibleAssignment($assignmentId, $course, $session);
        $submission = $this->studentPublicationRepository->find($submissionId);

        if (!$submission instanceof CStudentPublication || 'file' !== $submission->getFiletype()) {
            throw new NotFoundHttpException('Assignment submission not found.');
        }

        if ($submission->getPublicationParent()?->getIid() !== $assignment->getIid()) {
            throw new NotFoundHttpException('Assignment submission not found in the current assignment.');
        }

        if ($submission->getUser()->getId() !== $user->getId()) {
            throw new AccessDeniedHttpException('You can only manage your own assignment submissions.');
        }

        if (null === $submission->getFirstResourceLinkFromCourseSession($course, $session)) {
            throw new AccessDeniedHttpException('The assignment submission does not belong to this course context.');
        }

        return $submission;
    }

    /**
     * @return array{
     *     canEdit: bool,
     *     canDelete: bool,
     *     editBlockedReason: ?string,
     *     deleteBlockedReason: ?string,
     *     reviewed: bool
     * }
     */
    public function capabilities(
        CStudentPublication $submission,
        User $user,
        Course $course,
        ?Session $session,
    ): array {
        $isOwner = $submission->getUser()->getId() === $user->getId();
        $inContext = null !== $submission->getFirstResourceLinkFromCourseSession($course, $session);
        $reviewed = $submission->getQualificatorId() > 0;
        $courseSettingEnabled = 1 === (int) api_get_course_setting(
            'student_delete_own_publication',
            $course,
        );
        $editionBlocked = 'true' === $this->settingsManager->getSetting(
            'work.block_student_publication_edition',
        );
        $sessionEditingAllowed = null === $session || api_is_allowed_to_session_edit(false, true);

        $baseReason = match (true) {
            !$isOwner => 'not_owner',
            !$inContext => 'context_mismatch',
            !$courseSettingEnabled => 'course_setting_disabled',
            $reviewed => 'reviewed',
            default => null,
        };

        $deleteReason = $baseReason;
        $editReason = $baseReason
            ?? (!$sessionEditingAllowed ? 'session_locked' : null)
            ?? ($editionBlocked ? 'edition_blocked' : null);

        return [
            'canEdit' => null === $editReason,
            'canDelete' => null === $deleteReason,
            'editBlockedReason' => $editReason,
            'deleteBlockedReason' => $deleteReason,
            'reviewed' => $reviewed,
        ];
    }

    public function assertCanEdit(
        CStudentPublication $submission,
        User $user,
        Course $course,
        ?Session $session,
    ): void {
        $capabilities = $this->capabilities($submission, $user, $course, $session);

        if (!$capabilities['canEdit']) {
            throw new AccessDeniedHttpException('This assignment submission cannot be edited.');
        }
    }

    public function assertCanDelete(
        CStudentPublication $submission,
        User $user,
        Course $course,
        ?Session $session,
    ): void {
        $capabilities = $this->capabilities($submission, $user, $course, $session);

        if (!$capabilities['canDelete']) {
            throw new AccessDeniedHttpException('This assignment submission cannot be deleted.');
        }
    }

    private function findVisibleAssignment(
        int $assignmentId,
        Course $course,
        ?Session $session,
    ): ?CStudentPublication {
        foreach ($this->studentPublicationRepository->findVisibleAssignmentsForStudent($course, $session) as $row) {
            $assignment = \is_array($row) ? ($row[0] ?? null) : $row;

            if ($assignment instanceof CStudentPublication && $assignment->getIid() === $assignmentId) {
                return $assignment;
            }
        }

        return null;
    }

    private function assertStudentSubscribed(User $user, Course $course, ?Session $session): void
    {
        if ($session instanceof Session) {
            $subscription = $this->entityManager
                ->getRepository(SessionRelCourseRelUser::class)
                ->findOneBy([
                    'user' => $user,
                    'course' => $course,
                    'session' => $session,
                    'status' => Session::STUDENT,
                ])
            ;
        } else {
            $subscription = $this->entityManager
                ->getRepository(CourseRelUser::class)
                ->findOneBy([
                    'user' => $user,
                    'course' => $course,
                    'status' => CourseRelUser::STUDENT,
                ])
            ;
        }

        if (null === $subscription) {
            throw new AccessDeniedHttpException('You are not enrolled in this course context.');
        }
    }
}
