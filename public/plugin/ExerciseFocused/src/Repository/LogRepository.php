<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\ExerciseFocused\Repository;

use Chamilo\CoreBundle\Entity\TrackEExercise;
use Doctrine\ORM\EntityRepository;

class LogRepository extends EntityRepository
{
    public function countByActionInExe(TrackEExercise $exe, string $action): int
    {
        return $this->count([
            'exe' => $exe,
            'action' => $action,
        ]);
    }

    public function countByActionAndLevel(TrackEExercise $exe, string $action, int $level): int
    {
        return $this->count([
            'exe' => $exe,
            'action' => $action,
            'level' => $level,
        ]);
    }
}
