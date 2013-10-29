<?php

namespace Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\NoResultException;

/**
 * TrackAttemptRepository
 *
 */
class TrackAttemptRepository extends EntityRepository
{
    public function getResults($exeIdList)
    {
        if (empty($exeIdList)) {
            return array();
        }

        $qb = $this->createQueryBuilder('e');
        $qb->select('e.questionId, SUM(e.marks) as marks')
            //->innerJoin('e.distribution', 'd')
            ->where($qb->expr()->in('e.exeId', $exeIdList))
            ->groupBy('e.questionId');
        $results = $qb->getQuery()->getArrayResult();
        $results = \array_column($results, 'marks', 'questionId');
        return $results;
    }
}
