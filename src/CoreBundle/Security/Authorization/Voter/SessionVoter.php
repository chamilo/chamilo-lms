<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Security\Authorization\Voter;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Manager\CourseManager;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\SessionRelCourse;
use Chamilo\UserBundle\Entity\User;
use Doctrine\ORM\EntityManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
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
    private $container;

    /**
     * @param EntityManager      $entityManager
     * @param CourseManager      $courseManager
     * @param ContainerInterface $container
     */
    public function __construct(
        EntityManager $entityManager,
        CourseManager $courseManager,
        ContainerInterface $container
    ) {
        $this->entityManager = $entityManager;
        $this->courseManager = $courseManager;
        $this->container = $container;
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
    public function supports($attribute, $subject)
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
    protected function voteOnAttribute($attribute, $session, TokenInterface $token)
    {
        /** @var User $user */
        $user = $token->getUser();

        // Make sure there is a user object (i.e. that the user is logged in)
        if (!$user instanceof UserInterface) {
            return false;
        }

        // Checks if the current course was set up
        // $session->getCurrentCourse() is set in the class CourseListener
        /** @var Session $session */
        $currentCourse = $session->getCurrentCourse();

        $authChecker = $this->container->get('security.authorization_checker');

        // Admins have access to everything
        if ($authChecker->isGranted('ROLE_ADMIN')) {
            return true;
        }

        switch ($attribute) {
            case self::VIEW:
                $userIsGeneralCoach = $this->isGeneralCoach($user, $session);
                $userIsCourseCoach = $this->isCourseCoach($user, $session, $currentCourse);
                $userIsStudent = $this->isStudent($user, $session, $currentCourse);

                if ($session->getDuration() === 0) {
                    // General coach
                    if ($userIsGeneralCoach && $session->isActiveForCoach()) {
                        $user->addRole('ROLE_CURRENT_SESSION_COURSE_TEACHER');

                        return true;
                    }

                    // Course-Coach access
                    if ($userIsCourseCoach && $session->isActiveForCoach()) {
                        $user->addRole('ROLE_CURRENT_SESSION_COURSE_TEACHER');

                        return true;
                    }

                    // Student access
                    if ($userIsStudent && $session->isActiveForStudent()) {
                        $user->addRole('ROLE_CURRENT_SESSION_COURSE_STUDENT');

                        return true;
                    }

                    return false;
                }

                if ($this->sessionIsAvailableByDuration($session, $user)) {
                    if ($userIsGeneralCoach) {
                        $user->addRole('ROLE_CURRENT_SESSION_COURSE_TEACHER');
                    }

                    if ($userIsCourseCoach) {
                        $user->addRole('ROLE_CURRENT_SESSION_COURSE_TEACHER');
                    }

                    if ($userIsStudent) {
                        $user->addRole('ROLE_CURRENT_SESSION_COURSE_STUDENT');
                    }

                    return true;
                }

                return false;
            case self::EDIT:
            case self::DELETE:
                $canEdit = $this->canEditSession($user, $session, false);

                if ($canEdit) {
                    $user->addRole('ROLE_CURRENT_SESSION_COURSE_TEACHER');

                    return true;
                }

                return false;
        }

        // User don't have access to the session
        return false;
    }

    /**
     * @param User    $user
     * @param Session $session
     *
     * @return bool
     */
    private function isGeneralCoach(User $user, Session $session): bool
    {
        $generalCoach = $session->getGeneralCoach();

        if (!$generalCoach) {
            return false;
        }

        if ($user->getId() === $generalCoach->getId()) {
            return true;
        }
    }

    /**
     * @param User        $user
     * @param Session     $session
     * @param Course|null $course
     *
     * @return bool
     */
    private function isCourseCoach(User $user, Session $session, Course $course = null): bool
    {
        if ($course) {
            return $session->hasCoachInCourseWithStatus($user, $course);
        }

        /** @var SessionRelCourse $sessionCourse */
        foreach ($session->getCourses() as $sessionCourse) {
            if ($session->hasCoachInCourseWithStatus($user, $sessionCourse->getCourse())) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param User        $user
     * @param Session     $session
     * @param Course|null $course
     *
     * @return bool
     */
    private function isStudent(User $user, Session $session, Course $course = null): bool
    {
        if ($course) {
            return $session->hasUserInCourse($user, $course, Session::STUDENT);
        }

        /** @var SessionRelCourse $sessionCourse */
        foreach ($session->getCourses() as $sessionCourse) {
            if ($session->hasUserInCourse($user, $sessionCourse->getCourse(), Session::STUDENT)) {
                return true;
            }
        }

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
            $userDuration = intval($userDurationData['duration']) * 24 * 60 * 60;
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

        $authChecker = $this->container->get('security.authorization_checker');

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

        $authChecker = $this->container->get('security.authorization_checker');
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
        $authChecker = $this->container->get('security.authorization_checker');

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
        $authChecker = $this->container->get('security.authorization_checker');

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
