<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Security\Authorization\Voter;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;

class CourseVoter extends Voter
{
    public const VIEW = 'VIEW';
    public const EDIT = 'EDIT';
    public const DELETE = 'DELETE';

    private EntityManagerInterface $entityManager;
    private Security $security;

    public function __construct(
        EntityManagerInterface $entityManager,
      //  CourseRepository $courseManager,
        Security $security
    ) {
        $this->entityManager = $entityManager;
        //$this->courseManager = $courseManager;
        $this->security = $security;
    }

    protected function supports(string $attribute, $subject): bool
    {
        $options = [
            self::VIEW,
            self::EDIT,
            self::DELETE,
        ];

        // if the attribute isn't one we support, return false
        if (!\in_array($attribute, $options, true)) {
            return false;
        }
        // only vote on Post objects inside this voter
        return $subject instanceof Course;
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        /** @var User $user */
        $user = $token->getUser();
        // Anons can enter a course depending of the course visibility
        /*if (!$user instanceof UserInterface) {
            return false;
        }*/

        // Admins have access to everything
        if ($this->security->isGranted('ROLE_ADMIN')) {
            return true;
        }

        // Course is active?
        /** @var Course $course */
        $course = $subject;

        switch ($attribute) {
            case self::VIEW:
                // Course is hidden then is not visible for nobody expect admins.
                if ($course->isHidden()) {
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
                if (!($user instanceof UserInterface)) {
                    return false;
                }

                // If user is logged in and is open platform, allow access.
                if (Course::OPEN_PLATFORM === $course->getVisibility()) {
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
                // Only teacher can edit/delete stuff.
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
