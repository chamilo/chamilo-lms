<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Repository;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CourseBundle\Entity\CForumForum;
use Chamilo\CourseBundle\Entity\CForumThread;
use Chamilo\CourseBundle\Entity\CGroupInfo;
use Chamilo\CourseBundle\Entity\CItemProperty;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * Class CForumThreadRepository.
 *
 * @package Chamilo\CourseBundle\Repository
 */
class CForumThreadRepository extends ServiceEntityRepository
{
    /**
     * CForumPostRepository constructor.
     *
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CForumThread::class);
    }

    /**
     * @param bool            $isAllowedToEdit
     * @param CForumForum     $forum
     * @param Course          $course
     * @param CGroupInfo|null $group
     * @param Session|null    $session
     *
     * @return array
     *
     * @todo Remove api_get_session_condition
     */
    public function findAllInCourseByForum(
        $isAllowedToEdit,
        CForumForum $forum,
        Course $course,
        CGroupInfo $group = null,
        Session $session = null
    ): array {
        $conditionSession = api_get_session_condition(
            $session ? $session->getId() : 0,
            true,
            false,
            't.sessionId'
        );
        $conditionVisibility = $isAllowedToEdit ? 'ip.visibility != 2' : 'ip.visibility = 1';
        $conditionGroup = $group
            ? 'AND ip.group = '.$group->getIid()
            : '';

        $dql = "SELECT DISTINCT t, ip
            FROM ChamiloCourseBundle:CForumThread t
            INNER JOIN ChamiloCourseBundle:CItemProperty ip
                WITH (t.iid = ip.ref AND t.cId = ip.course AND ip.tool = :tool)
            WHERE
                ip.course = :course AND
                t.forum = :forum AND
                $conditionVisibility
                $conditionGroup
                $conditionSession
            ORDER BY t.threadSticky DESC, t.threadDate DESC";

        $result = $this
            ->_em
            ->createQuery($dql)
            ->setParameters(['forum' => $forum, 'course' => $course, 'tool' => TOOL_FORUM_THREAD])
            ->getResult();

        $forums = [];

        for ($i = 0; $i < count($result); $i += 2) {
            /** @var CForumThread $t */
            $t = $result[$i];
            /** @var CItemProperty $ip */
            $ip = $result[$i + 1];
            $t->setItemProperty($ip);

            $forums[] = $t;
        }

        return $forums;
    }

    /**
     * @param int          $id
     * @param Course       $course
     * @param Session|null $session
     *
     * @return CForumThread|null
     *
     * @todo Remove api_get_session_condition
     */
    public function findOneInCourse($id, Course $course, Session $session = null)
    {
        $conditionSession = api_get_session_condition(
            $session ? $session->getId() : 0,
            true,
            false,
            't.sessionId'
        );

        $dql = "SELECT t, ip
            FROM ChamiloCourseBundle:CForumThread AS t
            INNER JOIN ChamiloCourseBundle:CItemProperty AS ip
                WITH (t.iid = ip.ref AND t.cId = ip.course)
            WHERE
                t.iid = :id AND
                ip.tool = :tool AND
                t.cId = :course
                $conditionSession";

        $result = $this
            ->_em
            ->createQuery($dql)
            ->setParameters(['id' => (int) $id, 'course' => $course, 'tool' => TOOL_FORUM_THREAD])
            ->getResult();

        if (empty($result)) {
            return null;
        }

        /** @var CForumThread $t */
        $t = $result[0];
        /** @var CItemProperty $ip */
        $ip = $result[1];
        $t->setItemProperty($ip);

        return $t;
    }
}
