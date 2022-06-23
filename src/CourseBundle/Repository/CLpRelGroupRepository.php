<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Repository;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CoreBundle\Repository\ResourceRepository;
use Chamilo\CourseBundle\Entity\CLp;
use Chamilo\CourseBundle\Entity\CGroup;
use Chamilo\CourseBundle\Entity\CLpRelGroup;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Class CLpRelGroupRepository.
 */
final class CLpRelGroupRepository extends ResourceRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CLpRelGroup::class);
    }

    /**
     * Get users subscribed to a item LP.
     *
     * @param CLp $lp
     * @param Course $course
     * @param Session $session
     *
     * @return array
     */
    public function getGroupsSubscribedToItem(
        CLp $lp,
        Course $course,
        Session $session = null
    ) {
        $criteria = [
            'lp' => $lp,
            'course' => $course,
            'session' => $session,
        ];

        return $this->findBy($criteria);
    }

    /**
     * Subscribe groups to a LP.
     *
     * @param User    $currentUser
     * @param string  $tool        learnpath | document | etc
     * @param Session $session
     * @param int     $itemId
     * @param array   $newList
     */
    public function subscribeGroupsToItem(
        $currentUser,
        Course $course,
        Session $session = null,
        CLp $lp,
        $newList = []
    ) {
        $em = $this->getEntityManager();
        $groupsSubscribedToItem = $this->getGroupsSubscribedToItem(
            $lp,
            $course,
            $session
        );

        $alreadyAdded = [];
        if ($groupsSubscribedToItem) {
            /** @var CLpRelGroup $cLpRelGroup */
            foreach ($groupsSubscribedToItem as $cLpRelGroup) {
                $getGroup = $cLpRelGroup->getGroup();
                if (!empty($getGroup)) {
                    $alreadyAdded[] = $getGroup->getIid();
                }
            }
        }

        $toDelete = $alreadyAdded;

        if (!empty($newList)) {
            $toDelete = array_diff($alreadyAdded, $newList);
        }

        if ($toDelete) {
            $this->unsubscribeGroupsToItem(
                $course,
                $session,
                $lp,
                $toDelete,
                true
            );
        }

        foreach ($newList as $groupId) {
            if (!in_array($groupId, $alreadyAdded)) {
                $groupObj = $em->find('ChamiloCourseBundle:CGroup', $groupId);
                $item = new CLpRelGroup();
                $item
                    ->setGroup($groupObj)
                    ->setCourse($course)
                    ->setLp($lp)
                    ->setCreatedAt(api_get_utc_datetime(null, false, true))
                    ->setCreatorUser(api_get_user_entity());

                if (!empty($session)) {
                    $item->setSession($session);
                }
                $em->persist($item); //$em is an instance of EntityManager

            }
        }

        $em->flush();
    }

    /**
     * Unsubscribe groups to Lp.
     *
     * @param Course $course
     * @param Session $session
     * @param CLp     $lp
     * @param array   $groups
     * @param bool    $unsubscribeUserToo
     */
    public function unsubscribeGroupsToItem(
        Course $course,
        Session $session = null,
        CLp $lp,
        $groups,
        $unsubscribeUserToo = false
    ) {
        if (!empty($groups)) {
            $em = $this->getEntityManager();
            $groupRepo = Container::getGroupRepository();
            /** @var CLpRelUserRepository $cLpRelUserRepo */
            $cLpRelUserRepo = $em->getRepository('ChamiloCourseBundle:CLpRelUser');

            foreach ($groups as $groupId) {
                /** @var CGroup $groupEntity */
                $groupEntity = $groupRepo->find($groupId);

                $item = $this->findOneBy([
                    'course' => $course,
                    'session' => $session,
                    'lp' => $lp,
                    'group' => $groupEntity,
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
                        $cLpRelUserRepo->unsubcribeUsersToItem(
                            $course,
                            $session,
                            $lp,
                            $newUserList
                        );
                    }
                }
            }
            $em->flush();
        }
    }

}
