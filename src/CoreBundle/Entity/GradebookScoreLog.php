<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;

/**
 * GradebookScoreLog.
 *
 * @ApiResource(
 *      attributes={"security"="is_granted('ROLE_ADMIN')"},
 *      iri="http://schema.org/gradebookScoreLog",
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
 * @ORM\Table(
 *      name="gradebook_score_log", indexes={
 *          @ORM\Index(name="idx_gradebook_score_log_user", columns={"user_id"}),
 *          @ORM\Index(name="idx_gradebook_score_log_user_category", columns={"user_id", "category_id"})
 *      }
 * )
 * @ORM\Entity
 */
class GradebookScoreLog
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected $id;

    /**
     * @var int
     *
     * @ORM\Column(name="category_id", type="integer", nullable=false)
     */
    protected $categoryId;

    /**
     * @var User
     * @ORM\ManyToOne (
     *    targetEntity="Chamilo\CoreBundle\Entity\User",
     *    inversedBy="gradebookResultLogs"
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
     */
    public function getUser(): User
    {
        return $this->user;
    }

    /**
     *
     * @return $this
     */
    public function setUser($user)
    {
        $this->user = $user;

        return $this;
    }
    /**
     * @var float
     *
     * @ORM\Column(name="score", type="float", precision=10, scale=0, nullable=false)
     */
    protected $score;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="registered_at", type="datetime", nullable=false)
     */
    protected $registeredAt;

    /**
     * Get the category id.
     *
     * @return int
     */
    public function getCategoryId()
    {
        return $this->categoryId;
    }

    /**
     * Get the achieved score.
     *
     * @return float
     */
    public function getScore()
    {
        return $this->score;
    }

    /**
     * Get the datetime of register.
     *
     * @return \DateTime
     */
    public function getRegisteredAt()
    {
        return $this->registeredAt;
    }

    /**
     * Get the id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set the category id.
     *
     * @param int $categoryId
     *
     * @return $this
     */
    public function setCategoryId($categoryId)
    {
        $this->categoryId = $categoryId;

        return $this;
    }

    /**
     * Set the achieved score.
     *
     * @param float $score
     *
     * @return $this
     */
    public function setScore($score)
    {
        $this->score = $score;

        return $this;
    }

    /**
     * Set the datetime of register.
     *
     * @return $this
     */
    public function setRegisteredAt(\DateTime $registeredAt)
    {
        $this->registeredAt = $registeredAt;

        return $this;
    }

    /**
     * Set the id.
     *
     * @param int $id
     *
     * @return $this
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }
}
