<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\CourseUser;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use CourseManager;
use Doctrine\ORM\EntityManagerInterface;
use SessionManager;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use UserManager;

final readonly class CourseUserWriteManager
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private Security $security,
        private CourseUserManager $courseUserManager,
    ) {}

    /**
     * @param int[] $userIds
     *
     * @return array{affectedIds: int[], failed: array<int, array{id: int, message: string}>}
     */
    public function subscribe(Course $course, ?Session $session, array $userIds, int $type): array
    {
        if (!$this->courseUserManager->canSubscribe($course, $session)) {
            throw new AccessDeniedHttpException('Course user subscription is disabled for the current manager.');
        }

        $userIds = $this->filterEligibleUserIds($course, $session, $userIds, $type, true);
        if ([] === $userIds) {
            throw new BadRequestHttpException('No valid users were selected.');
        }

        $affectedIds = [];
        $failed = [];

        foreach ($userIds as $userId) {
            if ($session instanceof Session && CourseUserManager::TYPE_TEACHER === $type) {
                $success = SessionManager::set_coach_to_course_session(
                    $userId,
                    (int) $session->getId(),
                    (int) $course->getId(),
                );

                if ($success) {
                    $affectedIds[] = $userId;

                    continue;
                }

                $failed[] = ['id' => $userId, 'message' => get_lang('Unexpected error while subscribing the user')];

                continue;
            }

            $result = CourseManager::subscribeUser(
                $userId,
                (int) $course->getId(),
                $type,
                $session?->getId() ?? 0,
                0,
                true,
                [
                    'result' => true,
                    'flash' => false,
                    'emails' => true,
                ],
            );

            if (\is_array($result) && !empty($result['ok'])) {
                $affectedIds[] = $userId;

                continue;
            }

            $failed[] = [
                'id' => $userId,
                'message' => \is_array($result)
                    ? (string) ($result['message'] ?? get_lang('Unexpected error while subscribing the user'))
                    : get_lang('Unexpected error while subscribing the user'),
            ];
        }

        return ['affectedIds' => $affectedIds, 'failed' => $failed];
    }

    /**
     * @param int[] $userIds
     *
     * @return int[]
     */
    public function unsubscribe(Course $course, ?Session $session, array $userIds, int $type): array
    {
        $currentUser = $this->security->getUser();
        if (!$currentUser instanceof User) {
            throw new AccessDeniedHttpException('Authentication is required.');
        }

        $canManage = $this->courseUserManager->canUnsubscribe($course, $session);
        $isSelfRequest = 1 === \count($userIds)
            && (int) $currentUser->getId() === (int) reset($userIds)
            && $this->courseUserManager->canSelfUnsubscribe($course, $session);

        if (!$canManage && !$isSelfRequest) {
            throw new AccessDeniedHttpException('You are not allowed to unsubscribe users in this context.');
        }

        $userIds = $this->courseUserManager->filterContextUserIds($course, $session, $userIds, $type);
        if (!$this->security->isGranted('ROLE_ADMIN') && $canManage) {
            $userIds = array_values(array_diff($userIds, [(int) $currentUser->getId()]));
        }

        if ([] === $userIds) {
            throw new BadRequestHttpException('No valid users were selected.');
        }

        $success = CourseManager::unsubscribe_user(
            $userIds,
            $course->getCode(),
            $session?->getId() ?? 0,
        );

        if (!$success) {
            throw new BadRequestHttpException('The selected users could not be unsubscribed.');
        }

        return $userIds;
    }

    public function setTutor(Course $course, ?Session $session, int $userId, bool $isTutor): bool
    {
        if (!$this->courseUserManager->canSetTutor($course, $session)) {
            throw new AccessDeniedHttpException('You are not allowed to change the tutor role in this context.');
        }

        if ($session instanceof Session) {
            throw new BadRequestHttpException('The course tutor role cannot be changed inside a session.');
        }

        $validIds = $this->courseUserManager->filterContextUserIds(
            $course,
            null,
            [$userId],
            CourseUserManager::TYPE_STUDENT,
        );
        if ([] === $validIds) {
            throw new BadRequestHttpException('The requested student is not subscribed to this course.');
        }

        $user = $this->entityManager->getRepository(User::class)->find($userId);
        if (!$user instanceof User) {
            throw new BadRequestHttpException('The requested user was not found.');
        }

        if ($user->isInvitee()) {
            throw new BadRequestHttpException('Invitees cannot be tutors.');
        }

        return CourseManager::updateUserCourseTutor($userId, (int) $course->getId(), $isTutor);
    }

    /**
     * @param int[] $userIds
     *
     * @return int[]
     */
    public function filterEligibleUserIds(
        Course $course,
        ?Session $session,
        array $userIds,
        int $type,
        bool $excludeCurrentMembers = false,
    ): array {
        $memberIds = $excludeCurrentMembers
            ? $this->courseUserManager->getContextMemberIds($course, $session)
            : [];
        $result = [];

        foreach (array_values(array_unique(array_map('intval', $userIds))) as $userId) {
            if ($userId <= 0 || isset($memberIds[$userId])) {
                continue;
            }

            $portalRows = UserManager::get_user_list(['user.id' => $userId]);
            if ([] === $portalRows) {
                continue;
            }

            $user = $this->entityManager->getRepository(User::class)->find($userId);
            if (!$user instanceof User
                || User::SOFT_DELETED === $user->getActive()
                || $user->hasRole('ROLE_ANONYMOUS')
                || !$this->hasPersistedPlatformRole($user)
            ) {
                continue;
            }

            if ((CourseUserManager::TYPE_STUDENT === $type || $session instanceof Session)
                && 'ADMIN' === strtoupper((string) $user->getOfficialCode())
            ) {
                continue;
            }

            // Teacher eligibility is roles-based only (never user.status).
            if (CourseUserManager::TYPE_TEACHER === $type
                && !$user->isTeacher()
                && !$user->isAdmin()
                && !$user->isSessionAdmin()
            ) {
                continue;
            }

            $result[] = $userId;
        }

        return $result;
    }

    /**
     * System accounts (anonymous / fallback) often have an empty roles array.
     * Require at least one real platform role from the roles column.
     */
    private function hasPersistedPlatformRole(User $user): bool
    {
        return $user->isStudent()
            || $user->isTeacher()
            || $user->isAdmin()
            || $user->isSessionAdmin()
            || $user->isHRM()
            || $user->isStudentBoss()
            || $user->isInvitee()
            || $user->hasRole('ROLE_GLOBAL_ADMIN')
            || $user->hasRole('ROLE_QUESTION_MANAGER');
    }
}
