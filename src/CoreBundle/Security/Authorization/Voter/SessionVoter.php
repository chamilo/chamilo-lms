<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Security\Authorization\Voter;

use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
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
        private readonly SettingsManager $settingsManager
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

                $visibilityForUser = $session->setAccessVisibilityByUser($user);

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

        $setting = $this->settingsManager->getSetting('session.allow_teachers_to_create_sessions');

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
            && 'true' !== $this->settingsManager->getSetting('session.allow_session_admins_to_manage_all_sessions')
            && !$session->hasUserAsSessionAdmin($user)
        ) {
            return false;
        }

        if ($this->security->isGranted('ROLE_TEACHER')
            && 'true' === $this->settingsManager->getSetting('session.allow_teachers_to_create_sessions')
            && !$session->hasUserAsGeneralCoach($user)
        ) {
            return false;
        }

        return true;
    }
}
