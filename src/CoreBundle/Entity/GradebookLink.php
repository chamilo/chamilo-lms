<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Chamilo\CoreBundle\Traits\CourseTrait;
use Chamilo\CoreBundle\Traits\UserTrait;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * GradebookLink.
 *
 * @ORM\Table(name="gradebook_link",
 *     indexes={
 *         @ORM\Index(name="idx_gl_cat", columns={"category_id"}),
 *     }
 * )
 * @ORM\Entity
 */
class GradebookLink
{
    use CourseTrait;
    use UserTrait;

    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected int $id;

    /**
     * @Assert\NotBlank()
     * @ORM\Column(name="type", type="integer", nullable=false)
     */
    protected int $type;

    /**
     * @ORM\Column(name="ref_id", type="integer", nullable=false)
     */
    protected int $refId;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\User", inversedBy="gradeBookLinks")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected User $user;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\Course", inversedBy="gradebookLinks")
     * @ORM\JoinColumn(name="c_id", referencedColumnName="id")
     */
    protected Course $course;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\GradebookCategory")
     * @ORM\JoinColumn(name="category_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected GradebookCategory $category;

    /**
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(name="created_at", type="datetime", nullable=false)
     */
    protected DateTime $createdAt;

    /**
     * @ORM\Column(name="weight", type="float", precision=10, scale=0, nullable=false)
     */
    protected float $weight;

    /**
     * @ORM\Column(name="visible", type="integer", nullable=false)
     */
    protected int $visible;

    /**
     * @ORM\Column(name="locked", type="integer", nullable=false)
     */
    protected int $locked;

    /**
     * @ORM\Column(name="best_score", type="float", precision=6, scale=2, nullable=true)
     */
    protected ?float $bestScore;

    /**
     * @ORM\Column(name="average_score", type="float", precision=6, scale=2, nullable=true)
     */
    protected ?float $averageScore;

    /**
     * @ORM\Column(name="score_weight", type="float", precision=6, scale=2, nullable=true)
     */
    protected ?float $scoreWeight;

    /**
     * @ORM\Column(name="user_score_list", type="array", nullable=true)
     */
    protected ?array $userScoreList;

    public function __construct()
    {
        $this->locked = 0;
    }

    /**
     * Set type.
     *
     * @param int $type
     *
     * @return GradebookLink
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type.
     *
     * @return int
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set refId.
     *
     * @param int $refId
     *
     * @return GradebookLink
     */
    public function setRefId($refId)
    {
        $this->refId = $refId;

        return $this;
    }

    /**
     * Get refId.
     *
     * @return int
     */
    public function getRefId()
    {
        return $this->refId;
    }

    /**
     * Set createdAt.
     *
     * @param DateTime $createdAt
     *
     * @return GradebookLink
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Get createdAt.
     *
     * @return DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Set weight.
     *
     * @param float $weight
     *
     * @return GradebookLink
     */
    public function setWeight($weight)
    {
        $this->weight = $weight;

        return $this;
    }

    /**
     * Get weight.
     *
     * @return float
     */
    public function getWeight()
    {
        return $this->weight;
    }

    /**
     * Set visible.
     *
     * @param int $visible
     *
     * @return GradebookLink
     */
    public function setVisible($visible)
    {
        $this->visible = $visible;

        return $this;
    }

    /**
     * Get visible.
     *
     * @return int
     */
    public function getVisible()
    {
        return $this->visible;
    }

    /**
     * Set locked.
     *
     * @param int $locked
     *
     * @return GradebookLink
     */
    public function setLocked($locked)
    {
        $this->locked = $locked;

        return $this;
    }

    /**
     * Get locked.
     *
     * @return int
     */
    public function getLocked()
    {
        return $this->locked;
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

    /**
     * @return float
     */
    public function getBestScore()
    {
        return $this->bestScore;
    }

    /**
     * @param float $bestScore
     */
    public function setBestScore($bestScore): self
    {
        $this->bestScore = $bestScore;

        return $this;
    }

    /**
     * @return float
     */
    public function getAverageScore()
    {
        return $this->averageScore;
    }

    /**
     * @param float $averageScore
     */
    public function setAverageScore($averageScore): self
    {
        $this->averageScore = $averageScore;

        return $this;
    }

    /**
     * @return array
     */
    public function getUserScoreList()
    {
        if (empty($this->userScoreList)) {
            return [];
        }

        return $this->userScoreList;
    }

    /**
     * @param array $userScoreList
     */
    public function setUserScoreList($userScoreList): self
    {
        $this->userScoreList = $userScoreList;

        return $this;
    }

    /**
     * @return float
     */
    public function getScoreWeight()
    {
        return $this->scoreWeight;
    }

    /**
     * @param float $scoreWeight
     */
    public function setScoreWeight($scoreWeight): self
    {
        $this->scoreWeight = $scoreWeight;

        return $this;
    }

    public function getCategory(): GradebookCategory
    {
        return $this->category;
    }

    public function setCategory(GradebookCategory $category): self
    {
        $this->category = $category;

        return $this;
    }
}
