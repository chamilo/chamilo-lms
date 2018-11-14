<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Repository;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CourseBundle\Entity\CForumCategory;
use Chamilo\CourseBundle\Entity\CForumForum;
use Chamilo\CourseBundle\Entity\CItemProperty;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * Class CForumForumRepository.
 *
 * @package Chamilo\CourseBundle\Repository
 */
class CForumForumRepository extends ServiceEntityRepository
{
    /**
     * CForumForumRepository constructor.
     *
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CForumForum::class);
    }

    /**
     * @param bool           $isAllowedToEdit
     * @param CForumCategory $category
     * @param Course         $course
     * @param Session|null   $session
     * @param bool           $includeGroupsForums
     *
     * @return array
     *
     * @todo Remove api_get_session_condition
     */
    public function findAllInCourseByCategory(
        $isAllowedToEdit,
        CForumCategory $category,
        Course $course,
        Session $session = null,
        $includeGroupsForums = true
    ): array {
        $conditionSession = api_get_session_condition(
            $session ? $session->getId() : 0,
            true,
            true,
            'f.sessionId'
        );
        $conditionVisibility = $isAllowedToEdit ? 'ip.visibility != 2' : 'ip.visibility = 1';
        $conditionGroups = $includeGroupsForums
            ? 'AND (f.forumOfGroup = 0 OR f.forumOfGroup IS NULL)'
            : '';

        $dql = "SELECT f, ip
            FROM ChamiloCourseBundle:CForumForum AS f
            INNER JOIN ChamiloCourseBundle:CItemProperty AS ip
                WITH (f.iid = ip.ref AND f.cId = ip.course)
            WHERE
                f.forumCategory = :category AND
                ip.tool = :tool AND
                f.cId = :course
                $conditionSession AND
                $conditionVisibility
                $conditionGroups
            ORDER BY f.forumOrder ASC";

        $result = $this
            ->_em
            ->createQuery($dql)
            ->setParameters(['category' => $category, 'course' => $course, 'tool' => TOOL_FORUM])
            ->getResult();

        $forums = [];

        for ($i = 0; $i < count($result); $i += 2) {
            /** @var CForumForum $f */
            $f = $result[$i];
            /** @var CItemProperty $ip */
            $ip = $result[$i + 1];
            $f->setItemProperty($ip);

            $forums[] = $f;
        }

        return $forums;
    }

    /**
     * @param int    $id
     * @param Course $course
     *
     * @return CForumForum
     */
    public function findOneInCourse($id, Course $course)
    {
        $dql = "SELECT f, ip
            FROM ChamiloCourseBundle:CForumForum AS f
            INNER JOIN ChamiloCourseBundle:CItemProperty AS ip
                WITH (f.iid = ip.ref AND f.cId = ip.course)
            WHERE
                f.iid = :id AND
                ip.tool = :tool AND
                f.cId = :course AND
                ip.visibility != 2";

        $result = $this
            ->_em
            ->createQuery($dql)
            ->setParameters(['id' => (int) $id, 'course' => $course, 'tool' => TOOL_FORUM])
            ->getResult();

        if (empty($result)) {
            return null;
        }

        /** @var CForumForum $f */
        $f = $result[0];
        /** @var CItemProperty $ip */
        $ip = $result[1];
        $f->setItemProperty($ip);

        return $f;
    }
}
