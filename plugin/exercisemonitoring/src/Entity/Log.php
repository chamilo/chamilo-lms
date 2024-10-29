<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\ExerciseMonitoring\Entity;

use Chamilo\CoreBundle\Entity\TrackEExercises;
use Chamilo\CoreBundle\Traits\TimestampableTypedEntity;
use Chamilo\CourseBundle\Entity\CQuiz;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="Chamilo\PluginBundle\ExerciseMonitoring\Repository\LogRepository")
 * @ORM\Table(name="plugin_exercisemonitoring_log")
 */
class Log
{
    use TimestampableTypedEntity;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected $id;

    /**
     * @var CQuiz
     *
     * @ORM\ManyToOne(targetEntity="Chamilo\CourseBundle\Entity\CQuiz")
     * @ORM\JoinColumn(name="exercise_id", referencedColumnName="iid")
     */
    protected $exercise;

    /**
     * @var TrackEExercises
     *
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\TrackEExercises")
     * @ORM\JoinColumn(name="exe_id", referencedColumnName="exe_id")
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
     * @ORM\Column(name="image_filename", type="string")
     */
    private $imageFilename;

    /**
     * @var bool
     *
     * @ORM\Column(name="removed", type="boolean", nullable=false, options={"default": false})
     */
    private $removed;

    public function __construct()
    {
        $this->removed = false;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getExercise(): CQuiz
    {
        return $this->exercise;
    }

    public function setExercise(CQuiz $exercise): Log
    {
        $this->exercise = $exercise;

        return $this;
    }

    public function getExe(): ?TrackEExercises
    {
        return $this->exe;
    }

    public function setExe(?TrackEExercises $exe): Log
    {
        $this->exe = $exe;

        return $this;
    }

    public function getLevel(): int
    {
        return $this->level;
    }

    public function setLevel(int $level): Log
    {
        $this->level = $level;

        return $this;
    }

    public function getImageFilename(): string
    {
        return $this->imageFilename;
    }

    public function setImageFilename(string $imageFilename): Log
    {
        $this->imageFilename = $imageFilename;

        return $this;
    }

    public function isRemoved(): bool
    {
        return $this->removed;
    }

    public function setRemoved(bool $removed): void
    {
        $this->removed = $removed;
    }
}
