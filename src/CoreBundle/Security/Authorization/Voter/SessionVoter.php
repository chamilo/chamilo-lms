<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Security\Authorization\Voter;

use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Repository\TrackECourseAccessRepository;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @todo remove legacy code.
 *
 * @extends Voter<'VIEW'|'EDIT'|'DELETE', Session>
 */
class SessionVoter extends Voter
{
    public const VIEW = 'VIEW';
    public const EDIT = 'EDIT';
    public const DELETE = 'DELETE';

    public function __construct(
        private readonly Security $security,
        private readonly SettingsManager $settingsManager,
        private readonly TrackECourseAccessRepository $trackECourseAccessRepository,
    ) {}

    protected function supports(string $attribute, $subject): bool
    {
        $options = [
            self::VIEW,
            self::EDIT,
            self::DELETE,
        ];

        return $subject instanceof Session && \in_array($attribute, $options, true);
    }

    /**
     * Check if user has access to a session.
     *
     * {@inheritdoc}
     */
    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        /** @var User $user */
        $user = $token->getUser();

        // Make sure there is a user object (i.e. that the user is logged in)
        if (!$user instanceof UserInterface) {
            return false;
        }

        // Admins have access to everything.
        if ($this->security->isGranted('ROLE_ADMIN')) {
            return true;
        }

        // Checks if the current course was set up
        // $session->getCurrentCourse() is set in the class CidReqListener.
        /** @var Session $session */
        $session = $subject;
        $currentCourse = $session->getCurrentCourse();

        // Course checks.
        if ($currentCourse && $currentCourse->isHidden()) {
            return false;
        }

        switch ($attribute) {
            case self::VIEW:
                // @todo improve performance.
                $userIsGeneralCoach = $session->hasUserAsGeneralCoach($user);
                if (null === $currentCourse) {
                    $userIsStudent = $session->getSessionRelCourseByUser($user, Session::STUDENT)->count() > 0;
                    $userIsCourseCoach = $session->hasCoachInCourseList($user); // The current course will be checked in CourseVoter.
                } else {
                    $userIsCourseCoach = $session->hasCourseCoachInCourse($user, $currentCourse);
                    $userIsStudent = $session->hasUserInCourse($user, $currentCourse, Session::STUDENT);
                }

                // Read the setting to determine if coaches should always have access after duration ends.
                $coachAccessAfterDurationEnd = 'true' === $this->settingsManager->getSetting('session.session_coach_access_after_duration_end', true);
                $visibilityForUser = $this->getAccessVisibilityByDuration($session, $user, $coachAccessAfterDurationEnd);

                if (null === $visibilityForUser) {
                    $visibilityForUser = $session->setAccessVisibilityByUser($user, true, $coachAccessAfterDurationEnd);
                }

                if ($userIsStudent && Session::LIST_ONLY == $visibilityForUser) {
                    return false;
                }

                if ($userIsGeneralCoach || $userIsCourseCoach) {
                    $user->addRole(ResourceNodeVoter::ROLE_CURRENT_COURSE_SESSION_TEACHER);
                } elseif ($userIsStudent) { // Student access.
                    $user->addRole(ResourceNodeVoter::ROLE_CURRENT_COURSE_SESSION_STUDENT);
                }

                if (
                    ($userIsGeneralCoach || $userIsCourseCoach || $userIsStudent)
                    && Session::INVISIBLE != $visibilityForUser
                ) {
                    return true;
                }

                return false;

            case self::EDIT:
            case self::DELETE:
                $canEdit = $this->canEditSession($user, $session, false);

                if ($canEdit) {
                    $user->addRole(ResourceNodeVoter::ROLE_CURRENT_COURSE_SESSION_TEACHER);

                    return true;
                }

                return false;
        }

        // User don't have access to the session
        return false;
    }

    /**
     * Calculate session visibility for duration-based sessions using a fresh DB query
     * instead of the potentially stale lazy collection on the User entity.
     * Returns null if the session uses fixed dates (not duration).
     */
    private function getAccessVisibilityByDuration(Session $session, User $user, bool $coachAccessAfterDurationEnd): ?int
    {
        // Only applies to duration-based sessions.
        if (!$session->getDuration() || $session->getDuration() <= 0) {
            return null;
        }

        // If session has fixed access dates, it uses date-based visibility instead.
        if ($session->getAccessStartDate() || $session->getAccessEndDate()) {
            return null;
        }

        // If setting is enabled, coaches always have access regardless of duration end.
        if ($coachAccessAfterDurationEnd && $session->hasCoach($user)) {
            return Session::AVAILABLE;
        }

        $duration = $session->getDuration() * 24 * 60 * 60;

        // Use repository directly to avoid stale Doctrine lazy collection.
        $firstAccess = $this->trackECourseAccessRepository->findFirstAccessByUserAndSession(
            $user,
            $session->getId()
        );

        // If no previous access exists, session is still available.
        if (!$firstAccess) {
            return Session::AVAILABLE;
        }

        $userSessionSubscription = $user->getSubscriptionToSession($session);
        $userDuration = $userSessionSubscription
            ? $userSessionSubscription->getDuration() * 24 * 60 * 60
            : 0;

        $firstAccessTimestamp = $firstAccess->getLoginCourseDate()->getTimestamp();
        $totalDuration = $firstAccessTimestamp + $duration + $userDuration;
        $currentTime = time();

        return $totalDuration > $currentTime ? Session::AVAILABLE : $session->getVisibility();
    }

    private function canEditSession(User $user, Session $session, bool $checkSession = true): bool
    {
        if (!$this->allowToManageSessions()) {
            return false;
        }

        if ($this->security->isGranted('ROLE_ADMIN') && $this->allowed($user, $session)) {
            return true;
        }

        if ($checkSession) {
            return $this->allowed($user, $session);
        }

        return true;
    }

    private function allowToManageSessions(): bool
    {
        if ($this->allowManageAllSessions()) {
            return true;
        }

        $setting = $this->settingsManager->getSetting('session.allow_teachers_to_create_sessions', true);

        return 'true' === $setting && $this->security->isGranted('ROLE_TEACHER');
    }

    private function allowManageAllSessions(): bool
    {
        return $this->security->isGranted('ROLE_ADMIN') || $this->security->isGranted('ROLE_SESSION_MANAGER');
    }

    private function allowed(User $user, Session $session): bool
    {
        if ($this->security->isGranted('ROLE_ADMIN')) {
            return true;
        }

        if ($this->security->isGranted('ROLE_SESSION_MANAGER')
            && 'true' !== $this->settingsManager->getSetting('session.allow_session_admins_to_manage_all_sessions', true)
            && !$session->hasUserAsSessionAdmin($user)
        ) {
            return false;
        }

        if ($this->security->isGranted('ROLE_TEACHER')
            && 'true' === $this->settingsManager->getSetting('session.allow_teachers_to_create_sessions', true)
            && !$session->hasUserAsGeneralCoach($user)
        ) {
            return false;
        }

        return true;
    }
}
