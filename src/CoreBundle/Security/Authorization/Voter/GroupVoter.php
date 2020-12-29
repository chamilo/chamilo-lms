<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Security\Authorization\Voter;

use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CourseBundle\Entity\CGroup;
use Chamilo\CourseBundle\Repository\CGroupRepository;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Class GroupVoter.
 */
class GroupVoter extends Voter
{
    public const VIEW = 'VIEW';
    public const EDIT = 'EDIT';
    public const DELETE = 'DELETE';

    //private $entityManager;
    //private $courseManager;
    //private $groupManager;
    private $security;

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

        // if the attribute isn't one we support, return false
        if (!in_array($attribute, $options)) {
            return false;
        }

        // only vote on Post objects inside this voter
        if (!$subject instanceof CGroup) {
            return false;
        }

        return true;
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        // make sure there is a user object (i.e. that the user is logged in)
        if (!$user instanceof UserInterface) {
            return false;
        }

        if (false == $subject) {
            return false;
        }

        // Admins have access to everything
        if ($this->security->isGranted('ROLE_ADMIN')) {
            return true;
        }
        /** @var CGroup $group */
        $group = $subject;
        $groupInfo = [
            'iid' => $group->getIid(),
            'session_id' => 0,
            'status' => $group->getStatus(),
        ];

        // Legacy
        return \GroupManager::userHasAccessToBrowse($user->getId(), $groupInfo);

        switch ($attribute) {
            case self::VIEW:
                if (!$group->hasUserInCourse($user, $course)) {
                    $user->addRole(ResourceNodeVoter::ROLE_CURRENT_SESSION_COURSE_STUDENT);

                    return true;
                }

                break;
            case self::EDIT:
            case self::DELETE:
                if (!$session->hasCoachInCourseWithStatus($user, $course)) {
                    $user->addRole(ResourceNodeVoter::ROLE_CURRENT_SESSION_COURSE_TEACHER);

                    return true;
                }

                break;
        }
        //dump("You don't have access to this group!!");

        return false;
    }
}
