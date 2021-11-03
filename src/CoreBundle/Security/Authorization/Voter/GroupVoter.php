<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Security\Authorization\Voter;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CourseBundle\Entity\CGroup;
use Chamilo\CourseBundle\Repository\CGroupRepository;
use Doctrine\ORM\EntityManager;
use GroupManager;
use Symfony\Component\HttpFoundation\RequestStack;
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
    private RequestStack $requestStack;

    public function __construct(
        //EntityManager $entityManager,
        //CourseRepository $courseManager,
        //CGroupRepository $groupManager,
        RequestStack $requestStack,
        Security $security
    ) {
        //$this->entityManager = $entityManager;
        //$this->courseManager = $courseManager;
        //$this->groupManager = $groupManager;
        $this->security = $security;
        $this->requestStack = $requestStack;
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

        // Admins have access to everything.
        if ($this->security->isGranted('ROLE_ADMIN')) {
            return true;
        }

        /** @var CGroup $group */
        $group = $subject;

        // The group's parent is the course.
        /** @var Course $course */
        $course = $group->getParent();

        if ($course->isHidden()) {
            return false;
        }

        if (Course::REGISTERED === $course->getVisibility()) {
            if (!$course->hasUser($user)) {
                return false;
            }
        }

        if ($course->hasTeacher($user)) {
            $user->addRole(ResourceNodeVoter::ROLE_CURRENT_COURSE_GROUP_TEACHER);

            return true;
        }

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

                $userIsInGroup = $group->hasMember($user);

                if ($userIsInGroup) {
                    $user->addRole(ResourceNodeVoter::ROLE_CURRENT_COURSE_GROUP_STUDENT);
                }

                $requestUri = '';
                // Check if user has access in legacy tool.
                $request = $this->requestStack->getCurrentRequest();
                if ($request) {
                    $requestUri = $request->getRequestUri();
                }

                $tools = [
                    '/main/forum/' => $group->getForumState(),
                    '/documents/' => $group->getDocState(),
                    '/main/calendar/' => $group->getCalendarState(),
                    '/main/announcements/' => $group->getAnnouncementsState(),
                    '/main/work/' => $group->getWorkState(),
                    '/main/wiki/' => $group->getWikiState(),
                    /*'/main/group/group_space' => GroupManager::TOOL_PUBLIC,
                    '/main/inc/ajax/model.ajax.php' => GroupManager::TOOL_PUBLIC,
                    '/main/inc/ajax/announcement.ajax.php' => GroupManager::TOOL_PUBLIC,*/
                    //'/main/chat/' => $group->getAnnouncementsState(),  ??
                ];

                $toolStatus = GroupManager::TOOL_PUBLIC;
                foreach ($tools as $path => $status) {
                    if (str_contains($requestUri, $path)) {
                        $toolStatus = $status;

                        break;
                    }
                }

                switch ($toolStatus) {
                    case GroupManager::TOOL_NOT_AVAILABLE:
                        return false;
                    case GroupManager::TOOL_PUBLIC:
                        return true;
                    case GroupManager::TOOL_PRIVATE:
                        if ($userIsInGroup) {
                            return true;
                        }

                        break;
                    case GroupManager::TOOL_PRIVATE_BETWEEN_USERS:
                        // Only works for announcements for now
                        if ($userIsInGroup && '/main/announcements/' === $path) {
                            return true;
                        }

                        break;
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
