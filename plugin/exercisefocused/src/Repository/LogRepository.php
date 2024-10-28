<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\ExerciseFocused\Repository;

use Chamilo\CoreBundle\Entity\TrackEExercises;
use Doctrine\ORM\EntityRepository;

class LogRepository extends EntityRepository
{
    public function countByActionInExe(TrackEExercises $exe, string $action): int
    {
        return $this->count([
            'exe' => $exe,
            'action' => $action,
        ]);
    }

    public function countByActionAndLevel(TrackEExercises $exe, string $action, int $level): int
    {
        return $this->count([
            'exe' => $exe,
            'action' => $action,
            'level' => $level,
        ]);
    }
}
