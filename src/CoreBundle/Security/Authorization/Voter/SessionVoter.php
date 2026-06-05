<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Security\Authorization\Voter;

use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\SessionVisibilityHelper;
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
        private readonly SessionVisibilityHelper $sessionVisibilityHelper,
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

                $visibilityForUser = $this->sessionVisibilityHelper->getSessionVisibility($session, $user);

                if ($userIsStudent && Session::LIST_ONLY == $visibilityForUser) {
                    return false;
                }

                return ($userIsGeneralCoach || $userIsCourseCoach || $userIsStudent)
                    && Session::INVISIBLE != $visibilityForUser;

            case self::EDIT:
            case self::DELETE:
                // canEditSession() runs the per-session ownership check (allowed())
                // so non-admin session managers/teachers are confined to the
                // sessions they actually own/coach.
                return $this->canEditSession($user, $session);
        }

        return false;
    }

    // Admins are already granted in voteOnAttribute(), so the methods below only
    // ever run for non-admin users. ROLE_ADMIN is therefore intentionally not
    // re-checked here.
    private function canEditSession(User $user, Session $session): bool
    {
        if (!$this->allowToManageSessions()) {
            return false;
        }

        return $this->allowed($user, $session);
    }

    private function allowToManageSessions(): bool
    {
        if ($this->security->isGranted('ROLE_SESSION_MANAGER')) {
            return true;
        }

        return $this->teachersCanCreateSessions() && $this->security->isGranted('ROLE_TEACHER');
    }

    private function allowed(User $user, Session $session): bool
    {
        if ($this->security->isGranted('ROLE_SESSION_MANAGER')
            && 'true' !== $this->settingsManager->getSetting('session.allow_session_admins_to_manage_all_sessions', true)
            && !$session->hasUserAsSessionAdmin($user)
        ) {
            return false;
        }

        if ($this->security->isGranted('ROLE_TEACHER')
            && $this->teachersCanCreateSessions()
            && !$session->hasUserAsGeneralCoach($user)
        ) {
            return false;
        }

        return true;
    }

    private function teachersCanCreateSessions(): bool
    {
        return 'true' === $this->settingsManager->getSetting('session.allow_teachers_to_create_sessions', true);
    }
}
