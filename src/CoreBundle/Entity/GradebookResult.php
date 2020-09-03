<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;

/**
 * GradebookResult.
 *

 * @ApiResource(
 *      attributes={"security"="is_granted('ROLE_ADMIN')"},
 *      iri="http://schema.org/Person",
 *      attributes={"security"="is_granted('ROLE_ADMIN')"},
 *      normalizationContext={"groups"={"user:read"}},
 *      denormalizationContext={"groups"={"user:write"}},
 *      collectionOperations={"get"},
 *      itemOperations={
 *          "get"={},
 *          "put"={},
 *          "delete"={},
 *     }
 * )
 * @ORM\Table(name="gradebook_result",
 *  indexes={
 *     @ORM\Index(name="idx_gb_uid_eid", columns={"user_id", "evaluation_id"}),
 * })
 *
 * @ORM\Entity
 */
class GradebookResult
{
    /**
     * @var int
     *
     * @ORM\Column(name="user_id", type="integer", nullable=false)
     * @deprecated Use user
     */
    protected $userId;

    /**
     * Set userId.
     *
     * @param int $userId
     *
     * @return GradebookResult
     * @deprecated Use setUser
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;

        return $this;
    }

    /**
     * Get userId.
     *
     * @return int
     * @deprecated Use getUser
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * @var User
     * @ORM\ManyToOne (
     *    targetEntity="Chamilo\CoreBundle\Entity\User",
     *    inversedBy="gradebookResult"
     * )
     * @ORM\JoinColumn(
     *    name="user_id",
     *    referencedColumnName="id",
     *    onDelete="CASCADE"
     * )
     */
    protected $user;

    /**
     * Get user.
     *
     * @return User
     */
    public function getUser(): User
    {
        return $this->user;
    }

    /**
     * @param User $user
     *
     * @return $this
     */
    public function setUser($user)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @var int
     *
     * @ORM\Column(name="evaluation_id", type="integer", nullable=false)
     */
    protected $evaluationId;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_at", type="datetime", nullable=false)
     */
    protected $createdAt;

    /**
     * @var float
     *
     * @ORM\Column(name="score", type="float", precision=10, scale=0, nullable=true)
     */
    protected $score;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * Set evaluationId.
     *
     * @param int $evaluationId
     *
     * @return GradebookResult
     */
    public function setEvaluationId($evaluationId)
    {
        $this->evaluationId = $evaluationId;

        return $this;
    }

    /**
     * Get evaluationId.
     *
     * @return int
     */
    public function getEvaluationId()
    {
        return $this->evaluationId;
    }

    /**
     * Set createdAt.
     *
     * @param \DateTime $createdAt
     *
     * @return GradebookResult
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Get createdAt.
     *
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Set score.
     *
     * @param float $score
     *
     * @return GradebookResult
     */
    public function setScore($score)
    {
        $this->score = $score;

        return $this;
    }

    /**
     * Get score.
     *
     * @return float
     */
    public function getScore()
    {
        return $this->score;
    }

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }
}
