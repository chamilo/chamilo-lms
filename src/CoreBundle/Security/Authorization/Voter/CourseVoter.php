<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Security\Authorization\Voter;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Manager\CourseManager;
use Chamilo\UserBundle\Entity\User;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Class CourseVoter.
 *
 * @package Chamilo\CoreBundle\Security\Authorization\Voter
 */
class CourseVoter extends Voter
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
    protected function supports($attribute, $subject): bool
    {
        $options = [
            self::VIEW,
            self::EDIT,
            self::DELETE,
        ];

        // if the attribute isn't one we support, return false
        if (!in_array($attribute, $options)) {
            return false;
        }

        // only vote on Post objects inside this voter
        if (!$subject instanceof Course) {
            return false;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function voteOnAttribute($attribute, $course, TokenInterface $token): bool
    {
        /** @var User $user */
        $user = $token->getUser();
        // Anons can enter a course depending of the course visibility
        /*if (!$user instanceof UserInterface) {
            return false;
        }*/

        $authChecker = $this->getAuthorizationChecker();

        // Admins have access to everything
        if ($authChecker->isGranted('ROLE_ADMIN')) {
            return true;
        }

        // Course is active?
        /** @var Course $course */
        switch ($attribute) {
            case self::VIEW:
                // Course is hidden then is not visible for nobody expect admins.
                if ($course->getVisibility() === Course::HIDDEN) {
                    return false;
                }

                // "Open to the world" no need to check if user is registered or if user exists.
                // Course::OPEN_WORLD
                if ($course->isPublic()) {
                    /*$user->addRole(ResourceNodeVoter::ROLE_CURRENT_COURSE_STUDENT);
                    $token->setUser($user);*/

                    return true;
                }

                // User should be instance of UserInterface.
                if (!$user instanceof UserInterface) {
                    return false;
                }

                // If user is logged in and is open platform, allow access.
                if ($course->getVisibility() === Course::OPEN_PLATFORM) {
                    $user->addRole(ResourceNodeVoter::ROLE_CURRENT_COURSE_STUDENT);

                    if ($course->hasTeacher($user)) {
                        $user->addRole(ResourceNodeVoter::ROLE_CURRENT_COURSE_TEACHER);
                    }

                    $token->setUser($user);

                    return true;
                }

                // Course::REGISTERED
                // User must be subscribed in the course no matter if is teacher/student
                if ($course->hasUser($user)) {
                    $user->addRole(ResourceNodeVoter::ROLE_CURRENT_COURSE_STUDENT);

                    if ($course->hasTeacher($user)) {
                        $user->addRole(ResourceNodeVoter::ROLE_CURRENT_COURSE_TEACHER);
                    }

                    $token->setUser($user);

                    return true;
                }
                break;
            case self::EDIT:
            case self::DELETE:
                // Only teacher can edit/delete stuff
                if ($course->hasTeacher($user)) {
                    $user->addRole(ResourceNodeVoter::ROLE_CURRENT_COURSE_TEACHER);
                    $token->setUser($user);

                    return true;
                }
                break;
        }

        return false;
    }
}
