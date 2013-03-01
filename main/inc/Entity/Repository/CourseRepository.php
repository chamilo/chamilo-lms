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
    public function getSubscribedStudents(\Entity\EntityCourse $course)
    {
        $qb = $this->createQueryBuilder('a');
        //Selecting user info
        $qb->select('u');

        //Loading EntityUser
        $qb->from('Entity\EntityUser', 'u');

        //Selecting courses for users
        $qb->innerJoin('u.courses', 'c');
/*
        $wherePart = $qb->expr()->andx();
        //Get only users subscribed to this course
        $wherePart->add($qb->expr()->eq('c.id', $course->getId()));
        //$wherePart->add($qb->expr()->eq('r.status', 2));
        $qb->where($wherePart);*/

        //$q = $qb->getQuery();
        //return $q->execute();
        return $qb;
    }


    public function getSubscribedTeachers() {

    }

    public function getSubscribedUsers(){

    }
}