<?php
/* For licensing terms, see /license.txt */
namespace Chamilo\CoreBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Chamilo\CoreBundle\Entity\Session;
use \Doctrine\ORM\Query\Expr\Join;
use Chamilo\CoreBundle\Entity\Course;

/**
 * SessionRepository
 *
 * @package Chamilo\CoreBundle\Entity\Repository
 * @author Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com>
 */
class SessionRepository extends EntityRepository
{

    /**
     * Get session's courses ordered by position in session_rel_course
     * @param Session $session The session
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function getCoursesOrderedByPosition(Session $session)
    {
        $queryBuilder = $this->createQueryBuilder('s');

        $queryBuilder->select('c')
            ->innerJoin('s.courses', 'session_courses')
            ->innerJoin(
                'ChamiloCoreBundle:SessionRelCourse',
                'src',
                Join::WITH,
                'session_courses.course = src.course'
            )
            ->innerJoin(
                'ChamiloCoreBundle:Course',
                'c',
                Join::WITH,
                'src.course = c.id'
            )
            ->where(
                $queryBuilder->expr()->eq('s.id', $session->getId())
            )
            ->orderBy('src.position');

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * Get the coach for a course in session
     * @param Session $session The session
     * @param Course $course The course
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function getCourseCoachesForCoach(Session $session, Course $course)
    {
        $queryBuilder = $this->createQueryBuilder('s');

        $queryBuilder->select('u')
            ->innerJoin('s.courses', 'session_courses')
            ->innerJoin(
                'ChamiloCoreBundle:SessionRelCourseRelUser',
                'scu',
                Join::WITH,
                'session_courses.course = scu.course'
            )
            ->innerJoin(
                'ChamiloUserBundle:User',
                'u',
                Join::WITH,
                'scu.user = u.id'
            )
            ->where(
                $queryBuilder->expr()->andX(
                    $queryBuilder->expr()->eq('s.id', $session->getId()),
                    $queryBuilder->expr()->eq('scu.course', $course->getId())
                )
            );

        return $queryBuilder->getQuery()->getResult();
    }

}
