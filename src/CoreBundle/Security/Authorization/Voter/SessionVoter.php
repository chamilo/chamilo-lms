<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Security\Authorization\Voter;

use Chamilo\CoreBundle\Entity\Manager\CourseManager;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\UserBundle\Entity\User;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Class SessionVoter.
 *
 * @package Chamilo\CoreBundle\Security\Authorization\Voter
 */
class SessionVoter extends Voter
{
    public const VIEW = 'VIEW';
    public const EDIT = 'EDIT';
    public const DELETE = 'DELETE';

    private $entityManager;
    private $courseManager;
    private $authorizationChecker;
    private $container;

    /**
     * @param EntityManagerInterface        $entityManager
     * @param CourseManager                 $courseManager
     * @param AuthorizationCheckerInterface $authorizationChecker
     * @param ContainerInterface            $container
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        CourseManager $courseManager,
        AuthorizationCheckerInterface $authorizationChecker,
        ContainerInterface $container
    ) {
        $this->entityManager = $entityManager;
        $this->courseManager = $courseManager;
        $this->authorizationChecker = $authorizationChecker;
        $this->container = $container;
    }

    /**
     * @return AuthorizationCheckerInterface
     */
    public function getAuthorizationChecker()
    {
        return $this->authorizationChecker;
    }

    /**
     * @return EntityManager
     */
    public function getEntityManager()
    {
        return $this->entityManager;
    }

    /**
     * @return CourseManager
     */
    public function getCourseManager()
    {
        return $this->courseManager;
    }

    /**
     * {@inheritdoc}
     */
    public function supports($attribute, $subject): bool
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
    protected function voteOnAttribute($attribute, $session, TokenInterface $token): bool
    {
        /** @var User $user */
        $user = $token->getUser();

        // Make sure there is a user object (i.e. that the user is logged in)
        if (!$user instanceof UserInterface) {
            return false;
        }

        $authChecker = $this->getAuthorizationChecker();

        // Admins have access to everything
        if ($authChecker->isGranted('ROLE_ADMIN')) {
            return true;
        }

        // Checks if the current course was set up
        // $session->getCurrentCourse() is set in the class CourseListener
        /** @var Session $session */
        $currentCourse = $session->getCurrentCourse();

        switch ($attribute) {
            case self::VIEW:
                $userIsGeneralCoach = $session->isUserGeneralCoach($user);
                $userIsCourseCoach = $session->hasCoachInCourseWithStatus($user, $currentCourse);
                $userIsStudent = $session->hasUserInCourse($user, $currentCourse, Session::STUDENT);

                if (empty($session->getDuration())) {
                    // General coach
                    if ($userIsGeneralCoach && $session->isActiveForCoach()) {
                        $user->addRole(ResourceNodeVoter::ROLE_CURRENT_SESSION_COURSE_TEACHER);

                        return true;
                    }
                    // Course-Coach access
                    if ($userIsCourseCoach && $session->isActiveForCoach()) {
                        $user->addRole(ResourceNodeVoter::ROLE_CURRENT_SESSION_COURSE_TEACHER);

                        return true;
                    }

                    // Student access
                    if ($userIsStudent && $session->isActiveForStudent()) {
                        $user->addRole(ResourceNodeVoter::ROLE_CURRENT_SESSION_COURSE_STUDENT);

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
     * @param Session $session
     * @param User    $user
     *
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
        if (count($courseAccess) == 0) {
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
     * @param User    $user
     * @param Session $session
     * @param bool    $checkSession
     *
     * @return bool
     */
    private function canEditSession(User $user, Session $session, $checkSession = true): bool
    {
        if (!$this->allowToManageSessions()) {
            return false;
        }

        $authChecker = $this->getAuthorizationChecker();

        if ($authChecker->isGranted('ROLE_ADMIN') && $this->allowed($user, $session)) {
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

    /**
     * @return bool
     */
    private function allowToManageSessions(): bool
    {
        if ($this->allowManageAllSessions()) {
            return true;
        }

        $authChecker = $this->getAuthorizationChecker();
        $settingsManager = $this->container->get('chamilo.settings.manager');
        $setting = $settingsManager->getSetting('session.allow_teachers_to_create_sessions');

        if ($authChecker->isGranted('ROLE_TEACHER') && $setting === 'true') {
            return true;
        }

        return false;
    }

    /**
     * @return bool
     */
    private function allowManageAllSessions(): bool
    {
        $authChecker = $this->getAuthorizationChecker();

        if ($authChecker->isGranted('ROLE_ADMIN') || $authChecker->isGranted('ROLE_SESSION_MANAGER')) {
            return true;
        }

        return false;
    }

    /**
     * @param User    $user
     * @param Session $session
     *
     * @return bool
     */
    private function allowed(User $user, Session $session)
    {
        $authChecker = $this->getAuthorizationChecker();

        if ($authChecker->isGranted('ROLE_ADMIN')) {
            return true;
        }

        $settingsManager = $this->container->get('chamilo.settings.manager');

        if ($authChecker->isGranted('ROLE_SESSION_MANAGER') &&
            $settingsManager->getSetting('session.allow_session_admins_to_manage_all_sessions') !== 'true'
        ) {
            if ($session->getSessionAdminId() !== $user->getId()) {
                return false;
            }
        }

        if ($authChecker->isGranted('ROLE_ADMIN') &&
            $settingsManager->getSetting('session.allow_teachers_to_create_sessions') === 'true'
        ) {
            if ($session->getGeneralCoach()->getId() !== $user->getId()) {
                return false;
            }
        }

        return true;
    }
}
