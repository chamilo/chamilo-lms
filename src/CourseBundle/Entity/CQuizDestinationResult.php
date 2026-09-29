<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use Chamilo\CoreBundle\Entity\TrackEExercise;
use Chamilo\CoreBundle\Entity\User;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'c_quiz_destination_result')]
#[ORM\Entity]
class CQuizDestinationResult
{
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: true, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\ManyToOne(targetEntity: TrackEExercise::class)]
    #[ORM\JoinColumn(name: 'exe_id', referencedColumnName: 'exe_id', nullable: true, onDelete: 'CASCADE')]
    private ?TrackEExercise $exe = null;

    #[ORM\Column(name: 'achieved_level', type: 'string', length: 255)]
    private string $achievedLevel = '';

    #[ORM\Column(name: 'hash', type: 'string', length: 255)]
    private string $hash = '';

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getExe(): ?TrackEExercise
    {
        return $this->exe;
    }

    public function setExe(?TrackEExercise $exe): self
    {
        $this->exe = $exe;

        return $this;
    }

    public function getAchievedLevel(): string
    {
        return $this->achievedLevel;
    }

    public function setAchievedLevel(string $achievedLevel): self
    {
        $this->achievedLevel = $achievedLevel;

        return $this;
    }

    public function getHash(): string
    {
        return $this->hash;
    }

    public function setHash(string $hash): self
    {
        $this->hash = $hash;

        return $this;
    }
}
