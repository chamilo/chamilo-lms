<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity\Repository;

use Chamilo\CoreBundle\Entity\Course;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;

/**
 * Class CourseRepository
 * The functions inside this class must return an instance of QueryBuilder
 *
 * @package Chamilo\CoreBundle\Entity\Repository
 */
class CourseRepository extends EntityRepository
{
    /**
     * Get all users that are registered in the course. No matter the status
     *
     * @param Course $course
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

        // $wherePart->add($queryBuilder->expr()->eq('c.status', $status));

        $queryBuilder->where($wherePart);

        //var_dump($queryBuilder->getQuery()->getSQL());
        //$q = $queryBuilder->getQuery();
        //return $q->execute();
        return $queryBuilder;
    }

    /**
     * Gets students subscribed in the course
     *
     * @param Course $course
     *
     * @return QueryBuilder
     */
    public function getSubscribedStudents(Course $course)
    {
        return self::getSubscribedUsersByStatus($course, STUDENT);
    }

    /**
     * Gets the students subscribed in the course
     * @param Course $course
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
     *
     * Gets the teachers subscribed in the course
     * @param Course $course
     *
     * @return QueryBuilder
     */
    public function getSubscribedTeachers(Course $course)
    {
        return self::getSubscribedUsersByStatus($course, COURSEMANAGER);
    }

    /**
     * @param Course $course
     * @param int $status use legacy chamilo constants COURSEMANAGER|STUDENT
     * @return QueryBuilder
     */
    public function getSubscribedUsersByStatus(Course $course, $status)
    {
        $queryBuilder = $this->getSubscribedUsers($course);
        $wherePart = $queryBuilder->expr()->andx();
        $wherePart->add($queryBuilder->expr()->eq('c.status', $status));

        return $queryBuilder;
    }
}
