<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity\Repository;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\UserBundle\Entity\User;
use Chamilo\UserBundle\ChamiloUserBundle;
use Doctrine\ORM\EntityRepository;
use Doctrine\Common\Collections\Criteria;
use Sonata\CoreBundle\Model\BaseEntityManager;

/**
 * Class CourseRepository
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
        $queryBuilder = $this->createQueryBuilder('a');

        // Selecting user info.
        $queryBuilder->select('DISTINCT u');

        // Loading EntityUser.
        $queryBuilder->from('Chamilo\UserBundle\Entity\User', 'u');

        // Selecting courses for users.
        $queryBuilder->innerJoin('u.courses', 'c');

        //@todo check app settings
        $queryBuilder->add('orderBy', 'u.lastname ASC');

        $wherePart = $queryBuilder->expr()->andx();

        // Get only users subscribed to this course
        $wherePart->add($queryBuilder->expr()->eq('c.cId', $course->getId()));

        // $wherePart->add($queryBuilder->expr()->eq('c.status', $status));

        $queryBuilder->where($wherePart);
        //$q = $queryBuilder->getQuery();
        //return $q->execute();
        return $queryBuilder;
    }

    /**
     * Gets students subscribed in the course
     *
     * @param Course $course
     *
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function getSubscribedStudents(Course $course)
    {
        return self::getSubscribedUsersByStatus($course, STUDENT);
    }

    /**
     * Gets the students subscribed in the course
     * @param Course $course
     *
     * @return \Doctrine\ORM\QueryBuilder
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
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function getSubscribedTeachers(Course $course)
    {
        return self::getSubscribedUsersByStatus($course, COURSEMANAGER);
    }

    /**
     * @param Course $course
     * @param int $status use legacy chamilo constants COURSEMANAGER|STUDENT
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function getSubscribedUsersByStatus(Course $course, $status)
    {
        $queryBuilder = $this->getSubscribedUsers($course);
        $wherePart = $queryBuilder->expr()->andx();
        $wherePart->add($queryBuilder->expr()->eq('c.status', $status));

        return $queryBuilder;
    }

    /**
     * @param User $user
     * @param Course $course
     * @return bool
     */
    public function isUserSubscribedInCourse(User $user, Course $course)
    {
        // $queryBuilder = $this->getSubscribedUsers($course);

        $userCollection = $course->getUsers();
        $criteria = Criteria::create()
            ->where(Criteria::expr()->eq("user", $user));

        $userCollection = $userCollection->matching($criteria);

        if ($userCollection->count()) {
            return true;
        }

        return false;

    }
}
