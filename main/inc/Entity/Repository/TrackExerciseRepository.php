<?php

namespace Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\NoResultException;

/**
 * TrackExerciseRepository
 *
 */
class TrackExerciseRepository extends EntityRepository
{
    /**
     * @param int $exerciseId
     * @return array
     */
    public function getAverageScorePerForm($exerciseId, $courseId, $sessionId)
    {
        $qb = $this->createQueryBuilder('e');

        $qb->select('AVG(e.exeResult) average, d.title')
            ->innerJoin('e.distribution', 'd')
            ->where('e.exeExoId = :exerciseId AND e.cId = :courseId AND e.sessionId = :sessionId AND e.status = :status')
            ->setParameters(
                array(
                    'exerciseId' => $exerciseId,
                    'courseId' => $courseId,
                    'sessionId' => $sessionId,
                    'status' => '',
                )
            )
            ->groupBy('e.quizDistributionId');
        return $qb->getQuery()->getArrayResult();
    }
}
