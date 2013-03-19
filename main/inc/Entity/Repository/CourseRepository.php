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
     * @param \Entity\EntityCourse $course
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function getSubscribedStudents(\Entity\EntityCourse $course)
    {
        $qb = $this->createQueryBuilder('a');

        //Selecting user info
        $qb->select('DISTINCT u');

        //Loading EntityUser
        $qb->from('Entity\EntityUser', 'u');

        //Selecting courses for users
        $qb->innerJoin('u.courses', 'c');

        //@todo check app settings
        $qb->add('orderBy', 'u.lastname ASC');

        $wherePart = $qb->expr()->andx();

        //Get only users subscribed to this course
        $wherePart->add($qb->expr()->eq('c.cId', $course->getId()));
        $wherePart->add($qb->expr()->eq('c.status', STUDENT));
        $qb->where($wherePart);
        //$q = $qb->getQuery();
        //return $q->execute();
        return $qb;
    }


    public function getSubscribedTeachers() {

    }

    public function getSubscribedUsers(){

    }
}