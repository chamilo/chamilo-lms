<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Repository;

use Chamilo\CoreBundle\Entity\TrackEExercise;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class TrackEExerciseRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TrackEExercise::class);
    }

    public function delete(TrackEExercise $track): void
    {
        $this->getEntityManager()->remove($track);
        $this->getEntityManager()->flush();
    }

    /**
     * Get exercises with pending corrections grouped by exercise ID.
     */
    public function getPendingCorrectionsByExercise(int $courseId): array
    {
        $qb = $this->createQueryBuilder('te');

        $qb->select('IDENTITY(te.quiz) AS exerciseId, COUNT(te.exeId) AS pendingCount')
            ->where('te.status = :status')
            ->andWhere('te.course = :courseId')
            ->setParameter('status', 'incomplete')
            ->setParameter('courseId', $courseId)
            ->groupBy('te.quiz');

        return $qb->getQuery()->getResult();
    }
}
