<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Repository;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CourseBundle\Entity\CForumCategory;
use Chamilo\CourseBundle\Entity\CItemProperty;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * Class CForumCategoryRepository.
 *
 * @package Chamilo\CourseBundle\Repository
 */
class CForumCategoryRepository extends ServiceEntityRepository
{
    /**
     * CCourseSettingRepository constructor.
     *
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CForumCategory::class);
    }

    /**
     * @param bool         $isAllowedToEdit
     * @param Course       $course
     * @param Session|null $session
     *
     * @return array
     *
     * @todo Remove api_get_session_condition
     */
    public function findAllInCourse($isAllowedToEdit, Course $course, Session $session = null): array
    {
        $conditionSession = api_get_session_condition(
            $session ? $session->getId() : 0,
            true,
            true,
            'fcat.sessionId'
        );
        $conditionVisibility = $isAllowedToEdit ? 'ip.visibility != 2' : 'ip.visibility = 1';

        $dql = "SELECT ip, fcat
            FROM ChamiloCourseBundle:CItemProperty AS ip
            INNER JOIN ChamiloCourseBundle:CForumCategory fcat
                WITH (fcat.catId = ip.ref AND ip.course = fcat.cId)
            WHERE
                ip.tool = :tool AND
                ip.course = :course
                $conditionSession AND
                $conditionVisibility
                ORDER BY fcat.catOrder ASC";

        $result = $this
            ->_em
            ->createQuery($dql)
            ->setParameters(['course' => $course, 'tool' => TOOL_FORUM_CATEGORY])
            ->getResult();

        $categories = [];

        for ($i = 0; $i < count($result); $i += 2) {
            /** @var CItemProperty $ip */
            $ip = $result[$i];
            /** @var CForumCategory $fc */
            $fc = $result[$i + 1];

            $fc->setItemProperty($ip);

            $categories[] = $fc;
        }

        return $categories;
    }

    /**
     * @param int     $id
     * @param Course  $course
     * @param Session $session
     *
     * @return CForumCategory|null
     *
     * @todo Remove api_get_session_condition
     */
    public function findOneInCourse($id, Course $course, Session $session)
    {
        $conditionSession = api_get_session_condition(
            $session ? $session->getId() : 0,
            true,
            true,
            'fcat.sessionId'
        );

        $dql = "SELECT ip, fcat
            FROM ChamiloCourseBundle:CItemProperty AS ip
            INNER JOIN ChamiloCourseBundle:CForumCategory fcat
                WITH (fcat.catId = ip.ref AND ip.course = fcat.cId)
            WHERE
                ip.tool = :tool AND
                ip.course = :course
                fcat.iid = :id
                $conditionSession AND
                ORDER BY fcat.catOrder ASC";

        $result = $this
            ->_em
            ->createQuery($dql)
            ->setParameters(['tool' => TOOL_FORUM_CATEGORY, 'course' => $course, 'id' => (int) $id])
            ->getResult();

        if (empty($result)) {
            return null;
        }

        /** @var CItemProperty $ip */
        $ip = $result[0];
        /** @var CForumCategory $fc */
        $fc = $result[1];

        $fc->setItemProperty($ip);

        return $fc;
    }
}
