<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity\Repository;

use Chamilo\CoreBundle\Entity\Course;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;

/**
 * Class CourseRepository
 * The functions inside this class must return an instance of QueryBuilder.
 */
class CourseRepository extends EntityRepository
{
    /**
     * Get all users that are registered in the course. No matter the status.
     *
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function getSubscribedUsers(Course $course)
    {
        // Course builder
        $queryBuilder = $this->createQueryBuilder('c');

        // Selecting user info.
        $queryBuilder->select('DISTINCT user');

        // Selecting courses for users.
        $queryBuilder->innerJoin('c.users', 'subscriptions');
        $queryBuilder->innerJoin(
            'ChamiloUserBundle:User',
            'user',
            Join::WITH,
            'subscriptions.user = user.id'
        );

        if (api_is_western_name_order()) {
            $queryBuilder->add('orderBy', 'user.firstname ASC');
        } else {
            $queryBuilder->add('orderBy', 'user.lastname ASC');
        }

        $wherePart = $queryBuilder->expr()->andx();

        // Get only users subscribed to this course
        $wherePart->add($queryBuilder->expr()->eq('c.id', $course->getId()));
        $queryBuilder->where($wherePart);

        return $queryBuilder;
    }

    /**
     * Gets students subscribed in the course.
     *
     * @return QueryBuilder
     */
    public function getSubscribedStudents(Course $course)
    {
        return self::getSubscribedUsersByStatus($course, STUDENT);
    }

    /**
     * Gets the students subscribed in the course.
     *
     * @return QueryBuilder
     */
    public function getSubscribedCoaches(Course $course)
    {
        $queryBuilder = $this->getSubscribedUsers($course);
        //@todo add criterias
        return $queryBuilder;
    }

    /**
     * Gets the teachers subscribed in the course.
     *
     * @return QueryBuilder
     */
    public function getSubscribedTeachers(Course $course)
    {
        return self::getSubscribedUsersByStatus($course, COURSEMANAGER);
    }

    /**
     * @param int $status use legacy chamilo constants COURSEMANAGER|STUDENT
     *
     * @return QueryBuilder
     */
    public function getSubscribedUsersByStatus(Course $course, $status)
    {
        $queryBuilder = $this->getSubscribedUsers($course);
        $wherePart = $queryBuilder->expr()->andx();
        $wherePart->add($queryBuilder->expr()->eq('c.status', $status));

        return $queryBuilder;
    }

    public function getCoursesWithNoSession($urlId)
    {
        $queryBuilder = $this->createQueryBuilder('c');
        $criteria = Criteria::create();
        $queryBuilder = $queryBuilder
            ->select('c')
            ->leftJoin('c.urls', 'u')
            ->leftJoin('c.sessions', 's')
            /*->leftJoin(
                'ChamiloCoreBundle:SessionRelCourse',
                'sc',
                Join::WITH,
                'c != sc.course'
            )->leftJoin(
                'ChamiloCoreBundle:AccessUrlRelCourse',
                'ac',
                Join::WITH,
                'c = ac.course'
            )*/
            ->where($queryBuilder->expr()->isNull('s'))
            //->where($queryBuilder->expr()->eq('s', 0))
            ->where($queryBuilder->expr()->eq('u.url', $urlId))
            ->getQuery();

        $courses = $queryBuilder->getResult();
        $courseList = [];
        /** @var Course $course */
        foreach ($courses as $course) {
            if (empty($course->getSessions()->count() == 0)) {
                $courseList[] = $course;
            }
        }

        return $courseList;
    }
}
