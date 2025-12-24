<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\ExerciseMonitoring\Repository;

use Chamilo\CoreBundle\Entity\TrackEExercise;
use Chamilo\CourseBundle\Entity\CQuizQuestion;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Exercise;

class LogRepository extends EntityRepository
{
    public function findByLevelAndExe(int $level, TrackEExercise $exe): array
    {
        return $this->findBy(
            [
                'level' => $level,
                'exe' => $exe,
            ],
            ['createdAt' => 'ASC']
        );
    }

    public function findSnapshots(Exercise $objExercise, TrackEExercise $trackExe)
    {
        $qb = $this->createQueryBuilder('l');

        $qb->select(['l.imageFilename', 'l.createdAt']);

        if (ONE_PER_PAGE == $objExercise->selectType()) {
            $qb
                ->addSelect(['qq.question AS log_level'])
                ->leftJoin(CQuizQuestion::class, 'qq', Join::WITH, 'l.level = qq.iid');
        }

        $query = $qb
            ->andWhere(
                $qb->expr()->eq('l.exe', $trackExe->getExeId())
            )
            ->addOrderBy('l.createdAt')
            ->getQuery();

        return $query->getResult();
    }
}
