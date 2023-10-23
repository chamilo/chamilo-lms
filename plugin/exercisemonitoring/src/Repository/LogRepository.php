<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\ExerciseMonitoring\Repository;

use Chamilo\CoreBundle\Entity\TrackEExercises;
use Doctrine\ORM\EntityRepository;

class LogRepository extends EntityRepository
{
    public function findByLevelAndExe(int $level, TrackEExercises $exe): array
    {
        return $this->findBy(
            [
                'level' => $level,
                'exe' => $exe,
            ],
            ['createdAt' => 'ASC']
        );
    }
}
