<?php

namespace Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\Common\Collections\Criteria;

/**
 * CourseRepository
 *
 */
class CQuizCategoryRepository extends EntityRepository
{

    public function getQuestions()
    {
        $qb = $this->createQueryBuilder('a');

        //Selecting user info
        $qb->select('DISTINCT u');

        //Loading EntityUser
        $qb->from('Entity\CQuizCategory', 'u');

        //Selecting courses for users
        $qb->innerJoin('u.questions', 'c');

        //@todo check app settings
        //$qb->add('orderBy', 'u.lastname ASC');

        $wherePart = $qb->expr()->andx();

        //Get only users subscribed to this course
        //$wherePart->add($qb->expr()->eq('c.cId', $course->getId()));

        //$wherePart->add($qb->expr()->eq('c.status', $status));

        $qb->where($wherePart);
        //$q = $qb->getQuery();
        //return $q->execute();
        return $qb;
    }
}