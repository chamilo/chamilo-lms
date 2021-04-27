<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity\Repository;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CourseBundle\Entity\CItemProperty;
use Chamilo\UserBundle\Entity\Group;
use Chamilo\UserBundle\Entity\User;
use Doctrine\ORM\EntityRepository;

/**
 * Class ItemPropertyRepository.
 */
class ItemPropertyRepository extends EntityRepository
{
    /**
     * Get users subscribed to a item LP, Document, etc (item_property).
     *
     * @param string  $tool    learnpath | document | etc
     * @param int     $itemId
     * @param Session $session
     * @param Group   $group
     *
     * @return array
     */
    public function getUsersSubscribedToItem(
        $tool,
        $itemId,
        Course $course,
        Session $session = null,
        Group $group = null
    ) {
        $criteria = [
            'tool' => $tool,
            'lasteditType' => 'LearnpathSubscription',
            'ref' => $itemId,
            'course' => $course,
            'session' => $session,
            'group' => $group,
        ];

        return $this->findBy($criteria);
    }

    public function findByUserSuscribedToItem(
        $tool,
        $itemId,
        int $userId,
        int $courseId,
        int $sessionId = null
    ): ?CItemProperty {
        $criteria = [
            'tool' => $tool,
            'lasteditType' => 'LearnpathSubscription',
            'ref' => $itemId,
            'toUser' => $userId,
            'course' => $courseId,
            'session' => $sessionId ?: null,
            'group' => null,
        ];

        return $this->findOneBy($criteria);
    }

    public function findByGroupSuscribedToLp(
        $tool,
        $lpId,
        int $groupId,
        int $courseId,
        int $sessionId = 0
    ): ?CItemProperty {
        $criteria = [
            'tool' => $tool,
            'lasteditType' => 'LearnpathSubscription',
            'ref' => $lpId,
            'toUser' => null,
            'course' => $courseId,
            'session' => $sessionId ?: null,
            'group' => $groupId,
        ];

        return $this->findOneBy($criteria);
    }

    /**
     * Get Groups subscribed to a item: LP, Doc, etc.
     *
     * @param string  $tool    learnpath | document | etc
     * @param int     $itemId
     * @param Session $session
     *
     * @return array
     */
    public function getGroupsSubscribedToItem(
        $tool,
        $itemId,
        Course $course,
        Session $session = null
    ) {
        $criteria = [
            'tool' => $tool,
            'lasteditType' => 'LearnpathSubscription',
            'ref' => $itemId,
            'course' => $course,
            'session' => $session,
            'toUser' => null,
        ];

        return $this->findBy($criteria);
    }

    /**
     * Subscribe groups to a LP, doc (itemproperty).
     *
     * @param User    $currentUser
     * @param string  $tool        learnpath | document | etc
     * @param Session $session
     * @param int     $itemId
     * @param array   $newList
     */
    public function subscribeGroupsToItem(
        $currentUser,
        $tool,
        Course $course,
        Session $session = null,
        $itemId,
        $newList = []
    ) {
        $em = $this->getEntityManager();
        $groupsSubscribedToItem = $this->getGroupsSubscribedToItem(
            $tool,
            $itemId,
            $course,
            $session
        );

        $alreadyAdded = [];
        if ($groupsSubscribedToItem) {
            /** @var CItemProperty $itemProperty */
            foreach ($groupsSubscribedToItem as $itemProperty) {
                $getGroup = $itemProperty->getGroup();
                if (!empty($getGroup)) {
                    $alreadyAdded[] = $getGroup->getId();
                }
            }
        }

        $toDelete = $alreadyAdded;

        if (!empty($newList)) {
            $toDelete = array_diff($alreadyAdded, $newList);
        }

        if ($toDelete) {
            $this->unsubscribeGroupsToItem(
                $tool,
                $course,
                $session,
                $itemId,
                $toDelete,
                true
            );
        }

        foreach ($newList as $groupId) {
            if (!in_array($groupId, $alreadyAdded)) {
                $item = new CItemProperty($course);
                $groupObj = $em->find('ChamiloCourseBundle:CGroupInfo', $groupId);
                $item->setGroup($groupObj);
                $item->setTool($tool);
                $item->setRef($itemId);
                $item->setInsertUser($currentUser);

                if (!empty($session)) {
                    $item->setSession($session);
                }
                $item->setLasteditType('LearnpathSubscription');
                $item->setVisibility('1');
                $em->persist($item); //$em is an instance of EntityManager
            }
        }

        $em->flush();
    }

    /**
     * Unsubscribe groups to item.
     *
     * @param string  $tool
     * @param Session $session
     * @param int     $itemId
     * @param array   $groups
     * @param bool    $unsubscribeUserToo
     */
    public function unsubscribeGroupsToItem(
        $tool,
        Course $course,
        Session $session = null,
        $itemId,
        $groups,
        $unsubscribeUserToo = false
    ) {
        if (!empty($groups)) {
            $em = $this->getEntityManager();

            foreach ($groups as $groupId) {
                $item = $this->findOneBy([
                    'tool' => $tool,
                    'session' => $session,
                    'ref' => $itemId,
                    'group' => $groupId,
                ]);
                if ($item) {
                    $em->remove($item);
                }

                if ($unsubscribeUserToo) {
                    //Adding users from this group to the item
                    $users = \GroupManager::getStudentsAndTutors($groupId);
                    $newUserList = [];
                    if (!empty($users)) {
                        foreach ($users as $user) {
                            $newUserList[] = $user['user_id'];
                        }
                        $this->unsubcribeUsersToItem(
                            'learnpath',
                            $course,
                            $session,
                            $itemId,
                            $newUserList
                        );
                    }
                }
            }
            $em->flush();
        }
    }

    /**
     * Subscribe users to a LP, doc (itemproperty).
     *
     * @param User    $currentUser
     * @param string  $tool
     * @param Session $session
     * @param int     $itemId
     * @param array   $newUserList
     * @param bool    $deleteUsers
     */
    public function subscribeUsersToItem(
        $currentUser,
        $tool,
        Course $course,
        Session $session = null,
        $itemId,
        $newUserList = [],
        $deleteUsers = true
    ) {
        $em = $this->getEntityManager();
        $user = $em->getRepository('ChamiloUserBundle:User');

        $usersSubscribedToItem = $this->getUsersSubscribedToItem(
            $tool,
            $itemId,
            $course,
            $session
        );

        $alreadyAddedUsers = [];
        if ($usersSubscribedToItem) {
            /** @var CItemProperty $itemProperty */
            foreach ($usersSubscribedToItem as $itemProperty) {
                $getToUser = $itemProperty->getToUser();
                if (!empty($getToUser)) {
                    $alreadyAddedUsers[] = $itemProperty->getToUser()->getId();
                }
            }
        }

        if ($deleteUsers) {
            $usersToDelete = $alreadyAddedUsers;
            if (!empty($newUserList)) {
                $usersToDelete = array_diff($alreadyAddedUsers, $newUserList);
            }

            if ($usersToDelete) {
                $this->unsubcribeUsersToItem(
                    $tool,
                    $course,
                    $session,
                    $itemId,
                    $usersToDelete
                );
            }
        }

        foreach ($newUserList as $userId) {
            if (!in_array($userId, $alreadyAddedUsers)) {
                $userObj = $user->find($userId);

                $item = new CItemProperty($course);
                $item
                    ->setToUser($userObj)
                    ->setTool($tool)
                    ->setInsertUser($currentUser)
                    ->setRef($itemId);

                if (!empty($session)) {
                    $item->setSession($session);
                }
                $item->setLasteditType('LearnpathSubscription');
                $item->setVisibility('1');
                $em->persist($item); //$em is an instance of EntityManager
            }
        }

        $em->flush();
    }

    /**
     * Unsubscribe users to item.
     *
     * @param string  $tool
     * @param Session $session
     * @param int     $itemId
     * @param array   $usersToDelete
     */
    public function unsubcribeUsersToItem(
        $tool,
        Course $course,
        Session $session = null,
        $itemId,
        $usersToDelete
    ) {
        $em = $this->getEntityManager();

        if (!empty($usersToDelete)) {
            foreach ($usersToDelete as $userId) {
                $item = $this->findOneBy(
                    [
                        'tool' => $tool,
                        'session' => $session,
                        'ref' => $itemId,
                        'toUser' => $userId,
                    ]
                );
                if ($item) {
                    $em->remove($item);
                }
            }
            $em->flush();
        }
    }
}
