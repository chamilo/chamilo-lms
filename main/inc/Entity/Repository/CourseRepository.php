<?php

namespace Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\Common\Collections\Criteria;

/**
 * CourseRepository
 *
 */
class CourseRepository extends EntityRepository
{
    /**
     * Get all users that are registered in the course. No matter the status
     *
     * @param \Entity\Course $course
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function getSubscribedUsers(\Entity\Course $course)
    {
        $qb = $this->createQueryBuilder('a');

        //Selecting user info
        $qb->select('DISTINCT u');

        //Loading EntityUser
        $qb->from('Entity\User', 'u');

        //Selecting courses for users
        $qb->innerJoin('u.courses', 'c');

        //@todo check app settings
        $qb->add('orderBy', 'u.lastname ASC');

        $wherePart = $qb->expr()->andx();

        //Get only users subscribed to this course
        $wherePart->add($qb->expr()->eq('c.cId', $course->getId()));

        //$wherePart->add($qb->expr()->eq('c.status', $status));

        $qb->where($wherePart);
        //$q = $qb->getQuery();
        //return $q->execute();
        return $qb;
    }

    /**
     * Gets students subscribed in the course
     *
     * @param \Entity\Course $course
     *
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function getSubscribedStudents(\Entity\Course $course)
    {
        $qb = $this->getSubscribedUsers($course);
        $wherePart = $qb->expr()->andx();
        $wherePart->add($qb->expr()->eq('c.status', STUDENT));
        return $qb;
    }

    /**
     * Gets the students subscribed in the course
     * @param \Entity\Course $course
     *
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function getSubscribedCoaches(\Entity\Course $course)
    {
        $qb = $this->getSubscribedUsers($course);
        //Do something
        return $qb;
    }

    /**
     *
     * Gets the teachers subscribed in the course
     * @param \Entity\Course $course
     *
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function getSubscribedTeachers(\Entity\Course $course)
    {
        $qb = $this->getSubscribedUsers($course);
        $wherePart = $qb->expr()->andx();
        $wherePart->add($qb->expr()->eq('c.status', COURSEMANAGER));

        return $qb;
    }
}