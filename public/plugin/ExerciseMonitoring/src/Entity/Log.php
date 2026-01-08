<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\ExerciseMonitoring\Entity;

use Chamilo\CoreBundle\Entity\TrackEExercise;
use Chamilo\CoreBundle\Traits\TimestampableTypedEntity;
use Chamilo\CourseBundle\Entity\CQuiz;
use Chamilo\PluginBundle\ExerciseMonitoring\Repository\LogRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LogRepository::class)]
#[ORM\Table(name: 'plugin_exercisemonitoring_log')]
class Log
{
    use TimestampableTypedEntity;

    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    protected ?int $id;

    #[ORM\ManyToOne(targetEntity: CQuiz::class)]
    #[ORM\JoinColumn(name: 'exercise_id', referencedColumnName: 'iid')]
    protected ?CQuiz $exercise;

    #[ORM\ManyToOne(targetEntity: TrackEExercise::class)]
    #[ORM\JoinColumn(name: 'exe_id', referencedColumnName: 'exe_id')]
    private ?TrackEExercise $exe;

    #[ORM\Column(name: 'level', type: 'integer')]
    private int $level;

    #[ORM\Column(name: 'image_filename', type: 'string')]
    private string $imageFilename;

    #[ORM\Column(name: 'removed', type: 'boolean', nullable: false, options: ['default' => false])]
    private bool $removed = false;

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

    public function getExe(): ?TrackEExercise
    {
        return $this->exe;
    }

    public function setExe(?TrackEExercise $exe): Log
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
