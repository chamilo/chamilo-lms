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
     * @param int $courseId
     * @param  int $sessionId
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

    /**
     * @param int $exerciseId
     * @param int $courseId
     * @param int $sessionId
     * @param int $distributionId
     * @return array
     */
    public function getResults($exerciseId, $courseId, $sessionId, $distributionId)
    {
        $qb = $this->createQueryBuilder('e');
        $qb->select('e.exeId, e.exeResult')
            ->where('e.exeExoId = :exerciseId AND e.cId = :courseId AND e.sessionId = :sessionId AND e.status = :status')
            ->andWhere('e.quizDistributionId = :distributionId')
            ->setParameters(
                array(
                    'exerciseId' => $exerciseId,
                    'courseId' => $courseId,
                    'sessionId' => $sessionId,
                    'status' => '',
                    'distributionId' => $distributionId
                )
            );
        $results = $qb->getQuery()->getArrayResult();
        $results = \array_column($results, 'exeResult', 'exeId');
        return $results;
    }

    /**
     * @param int $exerciseId
     * @param int $courseId
     * @param int $distributionId
     * @return array
     */
    public function getResultsWithNoSession($exerciseId, $courseId, $distributionId)
    {
        $qb = $this->createQueryBuilder('e');
        $qb->select('e.exeId, e.exeResult')
            ->where('e.exeExoId = :exerciseId AND e.cId = :courseId AND e.status = :status')
            ->andWhere('e.quizDistributionId = :distributionId')
            ->setParameters(
                array(
                    'exerciseId' => $exerciseId,
                    'courseId' => $courseId,
                    'status' => '',
                    'distributionId' => $distributionId
                )
            );
        $results = $qb->getQuery()->getArrayResult();
        $results = \array_column($results, 'exeResult', 'exeId');
        return $results;
    }

    /*public function getResultsPerGlobalCategory($exerciseId, $courseId, $sessionId, $distributionId, $globalCategory)
    {
        $qb = $this->createQueryBuilder('e');
        $qb->select('e.exeId')
            ->innerJoin('e.exercise', 'ex')
            ->where('e.exeExoId = :exerciseId AND e.cId = :courseId AND e.sessionId = :sessionId AND e.status = :status')
            ->andWhere('e.quizDistributionId = :distributionId')
            ->setParameters(
                array(
                    'e.exerciseId' => $exerciseId,
                    'e.courseId' => $courseId,
                    'e.sessionId' => $sessionId,
                    'e.status' => '',
                    'e.distributionId' => $distributionId,
                    'ex.globalCategoryId' => $globalCategory
                )
            );
        $results = $qb->getQuery()->getArrayResult();
        $results = \array_column($results, 'exeId');
        return $results;
    }*/


}
