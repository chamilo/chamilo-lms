<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\ExerciseFocused\Entity;

use Chamilo\CoreBundle\Entity\TrackEExercises;
use Chamilo\CoreBundle\Traits\TimestampableTypedEntity;
use Doctrine\ORM\Mapping as ORM;

/**
 * Class EmbedRegistry.
 *
 * @package Chamilo\PluginBundle\Entity\EmbedRegistry
 *
 * @ORM\Entity(repositoryClass="Chamilo\PluginBundle\ExerciseFocused\Repository\LogRepository")
 * @ORM\Table(name="plugin_exercisefocused_log")
 */
class Log
{
    use TimestampableTypedEntity;

    public const TYPE_RETURN = 'return';
    public const TYPE_OUTFOCUSED = 'outfocused';
    public const TYPE_OUTFOCUSED_LIMIT = 'outfocused_limit';
    public const TYPE_TIME_LIMIT = 'time_limit';

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    private $id;

    /**
     * @var TrackEExercises
     *
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\TrackEExercises")
     * @ORM\JoinColumn(name="exe_id", referencedColumnName="exe_id", onDelete="SET NULL")
     */
    private $exe;

    /**
     * @var int
     *
     * @ORM\Column(name="level", type="integer")
     */
    private $level;

    /**
     * @var string
     *
     * @ORM\Column(name="action", type="string", nullable=false)
     */
    private $action;

    public function getId(): int
    {
        return $this->id;
    }

    public function getExe(): TrackEExercises
    {
        return $this->exe;
    }

    public function setExe(TrackEExercises $exe): Log
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
