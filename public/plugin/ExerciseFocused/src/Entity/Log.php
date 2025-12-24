<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\ExerciseFocused\Entity;

use Chamilo\CoreBundle\Entity\TrackEExercise;
use Chamilo\CoreBundle\Traits\TimestampableTypedEntity;
use Chamilo\PluginBundle\ExerciseFocused\Repository\LogRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LogRepository::class)]
#[ORM\Table(name: 'plugin_exercisefocused_log')]
class Log
{
    use TimestampableTypedEntity;

    public const TYPE_RETURN = 'return';
    public const TYPE_OUTFOCUSED = 'outfocused';
    public const TYPE_OUTFOCUSED_LIMIT = 'outfocused_limit';
    public const TYPE_TIME_LIMIT = 'time_limit';

    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    private ?int $id;

    #[ORM\ManyToOne(targetEntity: TrackEExercise::class)]
    #[ORM\JoinColumn(name: 'exe_id', referencedColumnName: 'exe_id', onDelete: 'SET NULL')]
    private TrackEExercise $exe;

    #[ORM\Column(name: 'level', type: 'integer')]
    private int $level;

    #[ORM\Column(name: 'action', type: 'string', nullable: false)]
    private string $action;

    public function getId(): int
    {
        return $this->id;
    }

    public function getExe(): TrackEExercise
    {
        return $this->exe;
    }

    public function setExe(TrackEExercise $exe): Log
    {
        $this->exe = $exe;

        return $this;
    }

    public function getLevel(): int
    {
        return $this->level;
    }

    public function setLevel(int $level): self
    {
        $this->level = $level;

        return $this;
    }

    public function getAction(): string
    {
        return $this->action;
    }

    public function setAction(string $action): Log
    {
        $this->action = $action;

        return $this;
    }
}
