<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Security\Authorization\Voter;

use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CourseBundle\Entity\CGroup;
use Chamilo\CourseBundle\Repository\CGroupRepository;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;

class GroupVoter extends Voter
{
    public const VIEW = 'VIEW';
    public const EDIT = 'EDIT';
    public const DELETE = 'DELETE';

    private Security $security;

    public function __construct(
        //EntityManager $entityManager,
        //CourseRepository $courseManager,
        //CGroupRepository $groupManager,
        Security $security
    ) {
        //$this->entityManager = $entityManager;
        //$this->courseManager = $courseManager;
        //$this->groupManager = $groupManager;
        $this->security = $security;
    }

    protected function supports(string $attribute, $subject): bool
    {
        $options = [
            self::VIEW,
            self::EDIT,
            self::DELETE,
        ];

        return $subject instanceof CGroup && \in_array($attribute, $options, true);
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        /** @var User $user */
        $user = $token->getUser();

        // make sure there is a user object (i.e. that the user is logged in)
        if (!$user instanceof UserInterface) {
            return false;
        }

        if (false === $subject) {
            return false;
        }

        // Admins have access to everything
        if ($this->security->isGranted('ROLE_ADMIN')) {
            return true;
        }

        /** @var CGroup $group */
        $group = $subject;

        // Legacy
        //\GroupManager::userHasAccessToBrowse($user->getId(), $group);
        $isTutor = $group->hasTutor($user);

        switch ($attribute) {
            case self::VIEW:
                if ($isTutor) {
                    $user->addRole(ResourceNodeVoter::ROLE_CURRENT_COURSE_GROUP_TEACHER);

                    return true;
                }

                if (!$group->getStatus()) {
                    return false;
                }

                if ($group->hasMember($user)) {
                    $user->addRole(ResourceNodeVoter::ROLE_CURRENT_COURSE_GROUP_STUDENT);

                    return true;
                }

                break;
            case self::EDIT:
            case self::DELETE:
                if ($isTutor) {
                    $user->addRole(ResourceNodeVoter::ROLE_CURRENT_COURSE_GROUP_TEACHER);

                    return true;
                }

                break;
        }
        //dump("You don't have access to this group!!");

        return false;
    }
}
