<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Security\Authorization\Voter;

use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\TrackECourseAccess;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CoreBundle\Settings\SettingsManager;
use CourseManager;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use SessionManager;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @todo remove legacy code.
 */
class SessionVoter extends Voter
{
    public const VIEW = 'VIEW';
    public const EDIT = 'EDIT';
    public const DELETE = 'DELETE';

    private EntityManagerInterface $entityManager;
    private Security $security;
    private SettingsManager $settingsManager;

    public function __construct(
        EntityManagerInterface $entityManager,
        //CourseRepository $courseManager,
        Security $security,
        SettingsManager $settingsManager
    ) {
        $this->entityManager = $entityManager;
        //$this->courseManager = $courseManager;
        $this->security = $security;
        $this->settingsManager = $settingsManager;
    }

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
        // $session->getCurrentCourse() is set in the class CourseListener.
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
                    $userIsCourseCoach = false;
                } else {
                    $userIsCourseCoach = $session->hasCourseCoachInCourse($user, $currentCourse);
                    $userIsStudent = $session->hasUserInCourse($user, $currentCourse, Session::STUDENT);
                }
                $duration = (int) $session->getDuration();
                if (0 === $duration) {
                    // General coach.
                    if ($userIsGeneralCoach && $session->isActiveForCoach()) {
                        $user->addRole(ResourceNodeVoter::ROLE_CURRENT_COURSE_SESSION_TEACHER);

                        return true;
                    }

                    // Course-Coach access.
                    if ($userIsCourseCoach && $session->isActiveForCoach()) {
                        $user->addRole(ResourceNodeVoter::ROLE_CURRENT_COURSE_SESSION_TEACHER);

                        return true;
                    }

                    // Student access.
                    if ($userIsStudent && $session->isActiveForStudent()) {
                        $user->addRole(ResourceNodeVoter::ROLE_CURRENT_COURSE_SESSION_STUDENT);

                        return true;
                    }
                }

                if ($this->sessionIsAvailableByDuration($session, $user)) {
                    if ($userIsGeneralCoach) {
                        $user->addRole(ResourceNodeVoter::ROLE_CURRENT_COURSE_SESSION_TEACHER);

                        return true;
                    }

                    if ($userIsCourseCoach) {
                        $user->addRole(ResourceNodeVoter::ROLE_CURRENT_COURSE_SESSION_TEACHER);

                        return true;
                    }

                    if ($userIsStudent) {
                        $user->addRole(ResourceNodeVoter::ROLE_CURRENT_COURSE_SESSION_STUDENT);

                        return true;
                    }
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

    private function sessionIsAvailableByDuration(Session $session, User $user): bool
    {
        $duration = $session->getDuration() * 24 * 60 * 60;

        if (0 === $user->getTrackECourseAccess()->count()) {
            return true;
        }

        $criteria = Criteria::create()->where(
            Criteria::expr()->eq('session', $session)
        );

        /** @var TrackECourseAccess|null $trackECourseAccess */
        $trackECourseAccess = $user->getTrackECourseAccess()->matching($criteria)->first();

        $currentTime = time();
        $firstAccess = 0;

        if (null !== $trackECourseAccess) {
            $firstAccess = $trackECourseAccess->getLoginCourseDate()->getTimestamp();
        }

        $userDurationData = SessionManager::getUserSession(
            $user->getId(),
            $session->getId()
        );
        $userDuration = 0;

        if (isset($userDurationData['duration'])) {
            $userDuration = (int) $userDurationData['duration'] * 24 * 60 * 60;
        }

        $totalDuration = $firstAccess + $duration + $userDuration;

        return $totalDuration > $currentTime;
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

        if ($this->security->isGranted('ROLE_SESSION_MANAGER') &&
            'true' !== $this->settingsManager->getSetting('session.allow_session_admins_to_manage_all_sessions') &&
            !$session->hasUserAsSessionAdmin($user)
        ) {
            return false;
        }

        if ($this->security->isGranted('ROLE_ADMIN') &&
            'true' === $this->settingsManager->getSetting('session.allow_teachers_to_create_sessions') &&
            !$session->hasUserAsGeneralCoach($user)
        ) {
            return false;
        }

        return true;
    }
}
