<?php

namespace Chamilo\CoreBundle\Entity;

use Chamilo\CoreBundle\Repository\XApiInternalLogRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Entity(repositoryClass: XApiInternalLogRepository::class)]
class XApiInternalLog
{
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    private ?User $user = null;

    #[ORM\Column(length: 255)]
    private ?string $statementId = null;

    #[ORM\Column(length: 255)]
    private ?string $verb = null;

    #[ORM\Column(length: 255)]
    private ?string $objectId = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $activityName = null;

    #[ORM\Column(length: 255)]
    private ?string $activityDescription = null;

    #[ORM\Column(nullable: true)]
    private ?float $scoreScaled = null;

    #[ORM\Column(nullable: true)]
    private ?float $scoreRaw = null;

    #[ORM\Column(nullable: true)]
    private ?float $scoreMin = null;

    #[ORM\Column(nullable: true)]
    private ?float $scoreMax = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getStatementId(): ?string
    {
        return $this->statementId;
    }

    public function setStatementId(string $statementId): static
    {
        $this->statementId = $statementId;

        return $this;
    }

    public function getVerb(): ?string
    {
        return $this->verb;
    }

    public function setVerb(string $verb): static
    {
        $this->verb = $verb;

        return $this;
    }

    public function getObjectId(): ?string
    {
        return $this->objectId;
    }

    public function setObjectId(string $objectId): static
    {
        $this->objectId = $objectId;

        return $this;
    }

    public function getActivityName(): ?string
    {
        return $this->activityName;
    }

    public function setActivityName(?string $activityName): static
    {
        $this->activityName = $activityName;

        return $this;
    }

    public function getActivityDescription(): ?string
    {
        return $this->activityDescription;
    }

    public function setActivityDescription(string $activityDescription): static
    {
        $this->activityDescription = $activityDescription;

        return $this;
    }

    public function getScoreScaled(): ?float
    {
        return $this->scoreScaled;
    }

    public function setScoreScaled(?float $scoreScaled): static
    {
        $this->scoreScaled = $scoreScaled;

        return $this;
    }

    public function getScoreRaw(): ?float
    {
        return $this->scoreRaw;
    }

    public function setScoreRaw(?float $scoreRaw): static
    {
        $this->scoreRaw = $scoreRaw;

        return $this;
    }

    public function getScoreMin(): ?float
    {
        return $this->scoreMin;
    }

    public function setScoreMin(?float $scoreMin): static
    {
        $this->scoreMin = $scoreMin;

        return $this;
    }

    public function getScoreMax(): ?float
    {
        return $this->scoreMax;
    }

    public function setScoreMax(?float $scoreMax): static
    {
        $this->scoreMax = $scoreMax;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }
}
