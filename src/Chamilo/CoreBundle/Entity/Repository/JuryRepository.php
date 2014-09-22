<?php

namespace Chamilo\CoreBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\Common\Collections\Criteria;

/**
 * JuryRepository
 *
 */
class JuryRepository extends EntityRepository
{
    /**
     * Get all users that are registered in the course. No matter the status
     *
     * @param \Chamilo\CoreBundle\Entity\Course $course
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function getJuryByPresidentId($userId)
    {
        $qb = $this->createQueryBuilder('a');

        //Selecting user info
        $qb->select('DISTINCT u');

        // Loading EntityUser
        $qb->from('Chamilo\CoreBundle\Entity\Jury', 'u');

        // Selecting members
        $qb->innerJoin('u.members', 'c');

        // Inner join with the table c_quiz_question_rel_category.
        $qb->innerJoin('c.role', 'r');

        //@todo check app settings
        //$qb->add('orderBy', 'u.lastname ASC');

        $wherePart = $qb->expr()->andx();

        //Get only users subscribed to this course
        $wherePart->add($qb->expr()->eq('r.role', $qb->expr()->literal('ROLE_JURY_PRESIDENT')));
        $wherePart->add($qb->expr()->eq('c.userId', $userId));

        $qb->where($wherePart);
        $q = $qb->getQuery();
        return $q->getSingleResult();
        //return $qb;
    }

    public function getExerciseAttemptsByJury($juryId)
    {

    }

}
