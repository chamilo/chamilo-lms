<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Security\Authorization\Voter;

use Chamilo\CoreBundle\Repository\CourseRepository;
use Chamilo\CourseBundle\Entity\CGroupInfo;
use Chamilo\CourseBundle\Repository\CGroupInfoRepository;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Class GroupVoter.
 */
class GroupVoter extends Voter
{
    public const VIEW = 'VIEW';
    public const EDIT = 'EDIT';
    public const DELETE = 'DELETE';

    private $entityManager;
    private $courseManager;
    private $groupManager;
    private $authorizationChecker;

    public function __construct(
        EntityManager $entityManager,
        CourseRepository $courseManager,
        CGroupInfoRepository $groupManager,
        AuthorizationCheckerInterface $authorizationChecker
    ) {
        $this->entityManager = $entityManager;
        $this->courseManager = $courseManager;
        $this->groupManager = $groupManager;
        $this->authorizationChecker = $authorizationChecker;
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

    public function getCourseManager(): CourseRepository
    {
        return $this->courseManager;
    }

    /**
     * @return GroupVoter
     */
    public function setCourseManager(CourseRepository $courseManager): self
    {
        $this->courseManager = $courseManager;

        return $this;
    }

    public function getGroupManager(): CGroupInfoRepository
    {
        return $this->groupManager;
    }

    /**
     * @return GroupVoter
     */
    public function setGroupManager(CGroupInfoRepository $groupManager): self
    {
        $this->groupManager = $groupManager;

        return $this;
    }

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
        if (!$subject instanceof CGroupInfo) {
            return false;
        }

        return true;
    }

    protected function voteOnAttribute($attribute, $group, TokenInterface $token): bool
    {
        $user = $token->getUser();

        // make sure there is a user object (i.e. that the user is logged in)
        if (!$user instanceof UserInterface) {
            return false;
        }

        if (false == $group) {
            return false;
        }

        $authChecker = $this->getAuthorizationChecker();

        // Admins have access to everything
        if ($authChecker->isGranted('ROLE_ADMIN')) {
            return true;
        }

        $groupInfo = [
            'id' => $group->getId(),
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
        dump("You don't have access to this group!!");

        return false;
    }
}
