<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity\Repository;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Group;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CourseBundle\Entity\CItemProperty;
use Doctrine\ORM\EntityRepository;

/**
 * Class ItemPropertyRepository
 *
 */
class ItemPropertyRepository extends EntityRepository
{
    /**
     *
     * Get users subscribed to a item LP, Document, etc (item_property)
     *
     * @param $tool learnpath | document | etc
     * @param $itemId
     * @param Course $course
     * @param Session $session
     * @param Group $group
     *
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function getUsersSubscribedToItem(
        $tool,
        $itemId,
        Course $course,
        Session $session = null,
        Group $group = null
    ) {
        $criteria = array(
            'tool' => $tool,
            'lasteditType' => 'LearnpathSubscription',
            'ref' => $itemId,
            'course' => $course,
            'session' => $session,
            'group' => $group,
        );

        return $this->findBy($criteria);
    }

    /**
     * Get Groups subscribed to a item: LP, Doc, etc
     * @param $tool learnpath | document | etc
     * @param $itemId
     * @param Course $course
     * @param Session $session
     * @return array
     */
    public function getGroupsSubscribedToItem(
        $tool,
        $itemId,
        Course $course,
        Session $session = null
    ) {
        $criteria = array(
            'tool' => $tool,
            'lasteditType' => 'LearnpathSubscription',
            'ref' => $itemId,
            'course' => $course,
            'session' => $session,
            'toUser' => null,
        );

        return $this->findBy($criteria);
    }

    /**
     * Subscribe groups to a LP, doc (itemproperty)
     * @param User $currentUser
     * @param $tool learnpath | document | etc
     * @param Course $course
     * @param Session $session
     * @param $itemId
     * @param array $newList
     */
    public function subscribeGroupsToItem(
        $currentUser,
        $tool,
        Course $course,
        Session $session = null,
        $itemId,
        $newList = array()
    ) {
        $em = $this->getEntityManager();
        $groupsSubscribedToItem = $this->getGroupsSubscribedToItem(
            $tool,
            $itemId,
            $course,
            $session
        );

        $alreadyAdded = array();
        if ($groupsSubscribedToItem) {
            /** @var CItemProperty $itemProperty */
            foreach ($groupsSubscribedToItem as $itemProperty) {
                $alreadyAdded[] = $itemProperty->getGroup()->getId();
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

            //Adding users from this group to the item
            /*$users = \GroupManager::getStudentsAndTutors($groupId);
            $newUserList = array();
            if (!empty($users)) {
                foreach ($users as $user) {
                    $newUserList[] = $user['user_id'];
                }
                $this->subscribeUsersToItem(
                    $currentUser,
                    'learnpath',
                    $course,
                    $session,
                    $itemId,
                    $newUserList
                );
            }*/
        }

        $em->flush();
    }

    /**
     * Unsubscribe groups to item
     * @param $tool
     * @param Course $course
     * @param Session $session
     * @param $itemId
     * @param $groups
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
                $item = $this->findOneBy(array(
                    'tool' => $tool,
                    'session' => $session,
                    'ref' => $itemId,
                    'group' => $groupId,
                ));
                if ($item) {
                    $em->remove($item);
                }

                if ($unsubscribeUserToo) {
                    //Adding users from this group to the item
                    $users = \GroupManager::getStudentsAndTutors($groupId);
                    $newUserList = array();
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
     * Subscribe users to a LP, doc (itemproperty)
     *
     * @param User $currentUser
     * @param $tool
     * @param Course $course
     * @param Session $session
     * @param $itemId
     * @param array $newUserList
     */
    public function subscribeUsersToItem(
        $currentUser,
        $tool,
        Course $course,
        Session $session = null,
        $itemId,
        $newUserList = array()
    ) {
        $em = $this->getEntityManager();
        $user = $em->getRepository('ChamiloUserBundle:User');

        $usersSubscribedToItem = $this->getUsersSubscribedToItem(
            $tool,
            $itemId,
            $course,
            $session
        );

        $alreadyAddedUsers = array();
        if ($usersSubscribedToItem) {
            /** @var CItemProperty $itemProperty */
            foreach ($usersSubscribedToItem as $itemProperty) {
                $alreadyAddedUsers[] = $itemProperty->getToUser()->getId();
            }
        }

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
     * Unsubscribe users to item
     *
     * @param $tool
     * @param Course $course
     * @param Session $session
     * @param $itemId
     * @param $usersToDelete
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
                    array(
                        'tool' => $tool,
                        'session' => $session,
                        'ref' => $itemId,
                        'toUser' => $userId,
                    )
                );
                if ($item) {
                    $em->remove($item);
                }
            }
            $em->flush();
        }
    }
}
