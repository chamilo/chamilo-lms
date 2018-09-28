<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Repository;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CourseBundle\Entity\CForumForum;
use Chamilo\CourseBundle\Entity\CItemProperty;
use Doctrine\ORM\EntityRepository;

/**
 * Class CForumForumRepository.
 *
 * @package Chamilo\CourseBundle\Repository
 */
class CForumForumRepository extends EntityRepository
{
    /**
     * @param bool         $isAllowedToEdit
     * @param Course       $course
     * @param Session|null $session
     * @param bool         $includeGroupsForums
     *
     * @return array
     *
     * @todo Remove api_get_session_condition
     */
    public function findAllInCourse(
        $isAllowedToEdit,
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

        $dql = "SELECT ip, f
            FROM ChamiloCourseBundle:CForumForum AS f
            INNER JOIN ChamiloCourseBundle:CItemProperty AS ip
                WITH (f.iid = ip.ref AND f.cId = ip.course)
            WHERE
                ip.tool = :tool AND
                f.cId = :course
                $conditionSession AND
                $conditionVisibility
                $conditionGroups
            ORDER BY f.forumOrder ASC";

        $result = $this
            ->_em
            ->createQuery($dql)
            ->setParameters(['course' => $course, 'tool' => TOOL_FORUM])
            ->getResult();

        $forums = [];

        for ($i = 0; $i < count($result); $i += 2) {
            /** @var CItemProperty $ip */
            $ip = $result[$i];
            /** @var CForumForum $f */
            $f = $result[$i + 1];
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
        $dql = "SELECT ip, f
            FROM ChamiloCourseBundle:CForumForum AS f
            INNER JOIN ChamiloCourseBundle:CItemProperty AS ip
                WITH (f.iid = ip.ref AND f.cId = ip.course)
            WHERE
                f.iid = :id
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

        /** @var CItemProperty $ip */
        $ip = $result[0];
        /** @var CForumForum $f */
        $f = $result[1];
        $f->setItemProperty($ip);

        return $f;
    }
}
