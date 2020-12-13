<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Security\Authorization\Voter;

use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Manager\SettingsManager;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Class SessionVoter.
 */
class SessionVoter extends Voter
{
    public const VIEW = 'VIEW';
    public const EDIT = 'EDIT';
    public const DELETE = 'DELETE';

    //private $entityManager;
    //private $courseManager;
    private $security;
    private $settingsManager;

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

    public function supports(string $attribute, $subject): bool
    {
        $options = [
            self::VIEW,
            self::EDIT,
            self::DELETE,
        ];

        return $subject instanceof Session && in_array($attribute, $options);
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

        // Admins have access to everything
        if ($this->security->isGranted('ROLE_ADMIN')) {
            return true;
        }

        // Checks if the current course was set up
        // $session->getCurrentCourse() is set in the class CourseListener
        /** @var Session $session */
        $session = $subject;

        $currentCourse = $session->getCurrentCourse();

        switch ($attribute) {
            case self::VIEW:
                $userIsGeneralCoach = $session->isUserGeneralCoach($user);
                $userIsCourseCoach = $session->hasCoachInCourseWithStatus($user, $currentCourse);
                $userIsStudent = $session->hasUserInCourse($user, $currentCourse, Session::STUDENT);

                if (empty($session->getDuration())) {
                    // General coach.
                    if ($userIsGeneralCoach && $session->isActiveForCoach()) {
                        $user->addRole(ResourceNodeVoter::ROLE_CURRENT_SESSION_COURSE_TEACHER);

                        return true;
                    }

                    // Course-Coach access.
                    if ($userIsCourseCoach && $session->isActiveForCoach()) {
                        $user->addRole(ResourceNodeVoter::ROLE_CURRENT_SESSION_COURSE_TEACHER);

                        return true;
                    }

                    // Student access
                    if ($userIsStudent && $session->isActiveForStudent()) {
                        $user->addRole(ResourceNodeVoter::ROLE_CURRENT_SESSION_COURSE_STUDENT);

                        //$token->setUser($user);

                        return true;
                    }
                }

                if ($this->sessionIsAvailableByDuration($session, $user)) {
                    if ($userIsGeneralCoach) {
                        $user->addRole(ResourceNodeVoter::ROLE_CURRENT_SESSION_COURSE_TEACHER);

                        return true;
                    }

                    if ($userIsCourseCoach) {
                        $user->addRole(ResourceNodeVoter::ROLE_CURRENT_SESSION_COURSE_TEACHER);

                        return true;
                    }

                    if ($userIsStudent) {
                        $user->addRole(ResourceNodeVoter::ROLE_CURRENT_SESSION_COURSE_STUDENT);

                        return true;
                    }
                }

                return false;
            case self::EDIT:
            case self::DELETE:
                $canEdit = $this->canEditSession($user, $session, false);

                if ($canEdit) {
                    $user->addRole(ResourceNodeVoter::ROLE_CURRENT_SESSION_COURSE_TEACHER);

                    return true;
                }

                return false;
        }

        // User don't have access to the session
        return false;
    }

    /**
     * @return bool
     */
    private function sessionIsAvailableByDuration(Session $session, User $user)
    {
        $duration = $session->getDuration() * 24 * 60 * 60;
        $courseAccess = \CourseManager::getFirstCourseAccessPerSessionAndUser(
            $session->getId(),
            $user->getId()
        );

        // If there is a session duration but there is no previous
        // access by the user, then the session is still available
        if (0 == count($courseAccess)) {
            return true;
        }

        $currentTime = time();
        $firstAccess = 0;

        if (isset($courseAccess['login_course_date'])) {
            $firstAccess = api_strtotime(
                $courseAccess['login_course_date'],
                'UTC'
            );
        }

        $userDurationData = \SessionManager::getUserSession(
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

    /**
     * @param bool $checkSession
     */
    private function canEditSession(User $user, Session $session, $checkSession = true): bool
    {
        if (!$this->allowToManageSessions()) {
            return false;
        }

        if ($this->security->isGranted('ROLE_ADMIN') && $this->allowed($user, $session)) {
            return true;
        }

        if ($checkSession) {
            if ($this->allowed($user, $session)) {
                return true;
            }

            return false;
        }

        return true;
    }

    private function allowToManageSessions(): bool
    {
        if ($this->allowManageAllSessions()) {
            return true;
        }

        $setting = $this->settingsManager->getSetting('session.allow_teachers_to_create_sessions');

        if ('true' === $setting && $this->security->isGranted('ROLE_TEACHER')) {
            return true;
        }

        return false;
    }

    private function allowManageAllSessions(): bool
    {
        if ($this->security->isGranted('ROLE_ADMIN') || $this->security->isGranted('ROLE_SESSION_MANAGER')) {
            return true;
        }

        return false;
    }

    /**
     * @return bool
     */
    private function allowed(User $user, Session $session)
    {
        if ($this->security->isGranted('ROLE_ADMIN')) {
            return true;
        }

        if ($this->security->isGranted('ROLE_SESSION_MANAGER') &&
            'true' !== $this->settingsManager->getSetting('session.allow_session_admins_to_manage_all_sessions')
        ) {
            if ($session->getSessionAdmin()->getId() !== $user->getId()) {
                return false;
            }
        }

        if ($this->security->isGranted('ROLE_ADMIN') &&
            'true' === $this->settingsManager->getSetting('session.allow_teachers_to_create_sessions')
        ) {
            if ($session->getGeneralCoach()->getId() !== $user->getId()) {
                return false;
            }
        }

        return true;
    }
}
