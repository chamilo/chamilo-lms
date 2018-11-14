<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Repository;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CourseBundle\Entity\CForumPost;
use Chamilo\CourseBundle\Entity\CForumThread;
use Chamilo\CourseBundle\Entity\CGroupInfo;
use Chamilo\UserBundle\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * Class CForumPostRepository.
 *
 * @package Chamilo\CourseBundle\Repository
 */
class CForumPostRepository extends ServiceEntityRepository
{
    /**
     * CForumPostRepository constructor.
     *
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CForumPost::class);
    }

    /**
     * @param bool            $onlyVisibles
     * @param bool            $isAllowedToEdit
     * @param CForumThread    $thread
     * @param Course          $course
     * @param User|null       $currentUser
     * @param CGroupInfo|null $group
     * @param string          $orderDirection
     *
     * @return array
     */
    public function findAllInCourseByThread(
        $onlyVisibles,
        $isAllowedToEdit,
        CForumThread $thread,
        Course $course,
        User $currentUser = null,
        CGroupInfo $group = null,
        $orderDirection = 'ASC'
    ): array {
        $conditionVisibility = $onlyVisibles ? 'p.visible = 1' : 'p.visible != 2';
        $conditionModetared = '';
        $filterModerated = true;

        if (
            (empty($group) && $isAllowedToEdit) ||
            (
                ($group ? $group->userIsTutor($currentUser) : false) ||
                !$onlyVisibles
            )
        ) {
            $filterModerated = false;
        }

        if ($filterModerated && $thread->getForum()->isModerated() && $onlyVisibles) {
            $userId = $currentUser ? $currentUser->getId() : 0;

            $conditionModetared = "AND p.status = 1 OR
                (p.status = ".CForumPost::STATUS_WAITING_MODERATION." AND p.posterId = $userId) OR
                (p.status = ".CForumPost::STATUS_REJECTED." AND p.poster = $userId) OR
                (p.status IS NULL AND p.posterId = $userId)";
        }

        $dql = "SELECT p
            FROM ChamiloCourseBundle:CForumPost p
            WHERE
                p.thread = :thread AND
                p.cId = :course AND
                $conditionVisibility
                $conditionModetared
            ORDER BY p.iid $orderDirection";

        $result = $this
            ->_em
            ->createQuery($dql)
            ->setParameters(['thread' => $thread, 'course' => $course])
            ->getResult();

        return $result;
    }
}
