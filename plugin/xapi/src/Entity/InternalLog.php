<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\Entity\XApi;

use Chamilo\UserBundle\Entity\User;
use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="xapi_internal_log")
 * @ORM\Entity()
 */
class InternalLog
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue()
     */
    private $id;
    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\UserBundle\Entity\User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", onDelete="CASCADE")
     */
    private $user;
    /**
     * @var string
     *
     * @ORM\Column(name="statement_id", type="string")
     */
    private $statementId;
    /**
     * @var string
     *
     * @ORM\Column(name="verb", type="string")
     */
    private $verb;
    /**
     * @var string
     *
     * @ORM\Column(name="object_id", type="string")
     */
    private $objectId;
    /**
     * @var string|null
     *
     * @ORM\Column(name="activity_name", type="string", nullable=true)
     */
    private $activityName;
    /**
     * @var string|null
     *
     * @ORM\Column(name="activity_description", type="string", nullable=true)
     */
    private $activityDescription;
    /**
     * @var float|null
     *
     * @ORM\Column(name="score_scaled", type="float", nullable=true)
     */
    private $scoreScaled;
    /**
     * @var float|null
     *
     * @ORM\Column(name="score_raw", type="float", nullable=true)
     */
    private $scoreRaw;
    /**
     * @var float|null
     *
     * @ORM\Column(name="score_min", type="float", nullable=true)
     */
    private $scoreMin;
    /**
     * @var float|null
     *
     * @ORM\Column(name="score_max", type="float", nullable=true)
     */
    private $scoreMax;
    /**
     * @var DateTime|null
     *
     * @ORM\Column(name="created_at", type="datetime", nullable=true)
     */
    private $createdAt;

    public function getId(): int
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): InternalLog
    {
        $this->user = $user;

        return $this;
    }

    public function getStatementId(): string
    {
        return $this->statementId;
    }

    public function setStatementId(string $statementId): InternalLog
    {
        $this->statementId = $statementId;

        return $this;
    }

    public function getVerb(): string
    {
        return $this->verb;
    }

    public function setVerb(string $verb): InternalLog
    {
        $this->verb = $verb;

        return $this;
    }

    public function getObjectId(): string
    {
        return $this->objectId;
    }

    public function setObjectId(string $objectId): InternalLog
    {
        $this->objectId = $objectId;

        return $this;
    }

    public function getActivityName(): ?string
    {
        return $this->activityName;
    }

    public function setActivityName(?string $activityName): InternalLog
    {
        $this->activityName = $activityName;

        return $this;
    }

    public function getActivityDescription(): ?string
    {
        return $this->activityDescription;
    }

    public function setActivityDescription(?string $activityDescription): InternalLog
    {
        $this->activityDescription = $activityDescription;

        return $this;
    }

    public function getScoreScaled(): ?float
    {
        return $this->scoreScaled;
    }

    public function setScoreScaled(?float $scoreScaled): InternalLog
    {
        $this->scoreScaled = $scoreScaled;

        return $this;
    }

    public function getScoreRaw(): ?float
    {
        return $this->scoreRaw;
    }

    public function setScoreRaw(?float $scoreRaw): InternalLog
    {
        $this->scoreRaw = $scoreRaw;

        return $this;
    }

    public function getScoreMin(): ?float
    {
        return $this->scoreMin;
    }

    public function setScoreMin(?float $scoreMin): InternalLog
    {
        $this->scoreMin = $scoreMin;

        return $this;
    }

    public function getScoreMax(): ?float
    {
        return $this->scoreMax;
    }

    public function setScoreMax(?float $scoreMax): InternalLog
    {
        $this->scoreMax = $scoreMax;

        return $this;
    }

    public function getCreatedAt(): ?DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?DateTime $createdAt): InternalLog
    {
        $this->createdAt = $createdAt;

        return $this;
    }
}
