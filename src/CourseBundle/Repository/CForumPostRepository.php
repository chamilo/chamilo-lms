<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Repository;

use Chamilo\CoreBundle\Entity\AbstractResource;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Repository\ResourceRepository;
use Chamilo\CourseBundle\Entity\CForumPost;
use Chamilo\CourseBundle\Entity\CForumThread;
use Chamilo\CourseBundle\Entity\CGroup;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Class CForumPostRepository.
 */
class CForumPostRepository extends ResourceRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CForumPost::class);
    }

    /**
     * @param bool   $onlyVisibles
     * @param bool   $isAllowedToEdit
     * @param string $orderDirection
     */
    public function findAllInCourseByThread(
        $onlyVisibles,
        $isAllowedToEdit,
        CForumThread $thread,
        Course $course,
        User $currentUser = null,
        CGroup $group = null,
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

        if ($filterModerated && $onlyVisibles && $thread->getForum()->isModerated()) {
            $userId = $currentUser ? $currentUser->getId() : 0;

            $conditionModetared = 'AND p.status = 1 OR
                (p.status = '.CForumPost::STATUS_WAITING_MODERATION." AND p.posterId = $userId) OR
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

        return $this
            ->getEntityManager()
            ->createQuery($dql)
            ->setParameters(['thread' => $thread, 'course' => $course])
            ->getResult();
    }

    public function delete(AbstractResource $resource)
    {
        /** @var CForumPost $resource */
        $attachments = $resource->getAttachments();
        if (!empty($attachments)) {
            foreach ($attachments as $attachment) {
                $this->getEntityManager()->remove($attachment);
            }
        }
        parent::delete($resource);
    }
}
