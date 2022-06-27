<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity\Repository;

use Chamilo\CourseBundle\Entity\CQuizRelQuestion;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Query\Expr\Join;

class CQuizQuestionRepository extends EntityRepository
{
    /**
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    public function countQuestionsInExercise(int $exerciseId): int
    {
        $query = $this->createQueryBuilder('qq')
            ->select('COUNT(qq)')
            ->innerJoin(CQuizRelQuestion::class, 'qrq', Join::WITH, 'qq.iid = qrq.questionId')
            ->where('qrq.exerciceId = :id')
            ->setParameters(['id' => $exerciseId])
            ->getQuery()
        ;

        return (int) $query->getSingleScalarResult();
    }

    /**
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    public function countEmbeddableQuestionsInExercise(int $exerciseId): int
    {
        $query = $this->createQueryBuilder('qq')
            ->select('COUNT(qq)')
            ->innerJoin(CQuizRelQuestion::class, 'qrq', Join::WITH, 'qq.iid = qrq.questionId')
            ->where('qrq.exerciceId = :id AND qq.type IN (:types)')
            ->setParameters(
                [
                    'id' => $exerciseId,
                    'types' => \ExerciseLib::getEmbeddableTypes(),
                ]
            )
            ->getQuery()
        ;

        return (int) $query->getSingleScalarResult();
    }
}
