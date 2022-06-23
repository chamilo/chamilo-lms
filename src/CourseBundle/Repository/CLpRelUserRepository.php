<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Repository;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Repository\ResourceRepository;
use Chamilo\CourseBundle\Entity\CLp;
use Chamilo\CourseBundle\Entity\CLpRelUser;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Class CLpRelUserRepository.
 */
final class CLpRelUserRepository extends ResourceRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CLpRelUser::class);
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
    public function getUsersSubscribedToItem(
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
     * Subscribe users to a LP.
     *
     * @param User    $currentUser
     * @param Course  $course
     * @param Session $session
     * @param CLp     $lp
     * @param array   $newUserList
     * @param bool    $deleteUsers
     */
    public function subscribeUsersToItem(
        $currentUser,
        Course $course,
        Session $session = null,
        CLp $lp,
        $newUserList = [],
        $deleteUsers = true
    ) {
        $em = $this->getEntityManager();
        $user = $em->getRepository('ChamiloCoreBundle:User');

        $usersSubscribedToItem = $this->getUsersSubscribedToItem(
            $lp,
            $course,
            $session
        );

        $alreadyAddedUsers = [];
        if ($usersSubscribedToItem) {
            /** @var CLpRelUser $lpUser */
            foreach ($usersSubscribedToItem as $lpUser) {
                $getToUser = $lpUser->getUser();
                if (!empty($getToUser)) {
                    $alreadyAddedUsers[] = $lpUser->getUser()->getId();
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
                    $course,
                    $session,
                    $lp,
                    $usersToDelete
                );
            }
        }

        foreach ($newUserList as $userId) {
            if (!in_array($userId, $alreadyAddedUsers)) {
                $userObj = $user->find($userId);

                $item = new CLpRelUser();
                $item
                    ->setUser($userObj)
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
     * Unsubscribe users to Lp.
     *
     * @param Course  $course
     * @param Session $session
     * @param CLp     $lp
     * @param array   $usersToDelete
     */
    public function unsubcribeUsersToItem(
        Course $course,
        Session $session = null,
        CLp $lp,
        $usersToDelete
    ) {
        $em = $this->getEntityManager();

        if (!empty($usersToDelete)) {
            foreach ($usersToDelete as $userId) {
                $item = $this->findOneBy(
                    [
                        'course' => $course,
                        'session' => $session,
                        'lp' => $lp,
                        'user' => api_get_user_entity($userId),
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
