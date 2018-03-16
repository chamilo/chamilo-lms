<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Security\Authorization\Voter;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Manager\CourseManager;
use Chamilo\CoreBundle\Entity\Session;
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
    const VIEW = 'VIEW';
    const EDIT = 'EDIT';
    const DELETE = 'DELETE';

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
        $course = $session->getCurrentCourse();

        if ($course == false) {
            return false;
        }

        $authChecker = $this->container->get('security.authorization_checker');

        // Admins have access to everything
        if ($authChecker->isGranted('ROLE_ADMIN')) {
            return true;
        }

        $sessionId = $session->getId();
        $userId = $user->getId();

        switch ($attribute) {
            case self::VIEW:
                // General coach
                $generalCoach = $session->getGeneralCoach();
                if ($generalCoach) {
                    $coachId = $generalCoach->getId();
                    $userId = $user->getId();
                    if ($coachId == $userId) {
                        $user->addRole('ROLE_CURRENT_SESSION_COURSE_TEACHER');

                        return true;
                    }
                }

                // Course-Coach access
                if ($session->hasCoachInCourseWithStatus($user, $course)) {
                    if (!$session->isActiveForCoach()) {
                        return false;
                    }
                    $user->addRole('ROLE_CURRENT_SESSION_COURSE_TEACHER');

                    return true;
                }

                // Student access
                if ($session->hasUserInCourse($user, $course)) {
                    $user->addRole('ROLE_CURRENT_SESSION_COURSE_STUDENT');

                    // Session duration per student.
                    if (!empty($session->getDuration())) {
                        $duration = $session->getDuration() * 24 * 60 * 60;

                        $courseAccess = \CourseManager::getFirstCourseAccessPerSessionAndUser(
                            $sessionId,
                            $userId
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
                            $userId,
                            $sessionId
                        );
                        $userDuration = 0;
                        if (isset($userDurationData['duration'])) {
                            $userDuration = intval($userDurationData['duration']) * 24 * 60 * 60;
                        }

                        $totalDuration = $firstAccess + $duration + $userDuration;
                        if ($totalDuration > $currentTime) {
                            return true;
                        } else {
                            return false;
                        }
                    } else {
                        if (!$session->isActiveForStudent()) {
                            return false;
                        }
                    }

                    return true;
                }

                return false;
                break;
            case self::EDIT:
            case self::DELETE:
                // General coach check
                $generalCoach = $session->getGeneralCoach();
                if ($generalCoach) {
                    $coachId = $generalCoach->getId();
                    $userId = $user->getId();
                    if ($coachId == $userId) {
                        $user->addRole('ROLE_CURRENT_SESSION_COURSE_TEACHER');

                        return true;
                    }
                }

                // Course session check
                if ($session->hasCoachInCourseWithStatus($user, $course)) {
                    if (!$session->isActiveForCoach()) {
                        return false;
                    }
                    $user->addRole('ROLE_CURRENT_SESSION_COURSE_TEACHER');

                    return true;
                }

                return false;
                break;
        }

        // User don't have access to the session
        return false;
    }
}
