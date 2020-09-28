<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Chamilo\CoreBundle\Traits\CourseTrait;
use Chamilo\CoreBundle\Traits\UserTrait;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * GradebookLink.
 *
 * @ORM\Table(name="gradebook_link",
 *  indexes={
 *     @ORM\Index(name="idx_gl_cat", columns={"category_id"}),
 *  }
 * )
 * @ORM\Entity
 */
class GradebookLink
{
    use CourseTrait;
    use UserTrait;

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
     * @ORM\Column(name="type", type="integer", nullable=false)
     */
    protected $type;

    /**
     * @var int
     *
     * @ORM\Column(name="ref_id", type="integer", nullable=false)
     */
    protected $refId;

    /**
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\User", inversedBy="gradeBookLinks")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $user;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\Course", inversedBy="gradebookLinks")
     * @ORM\JoinColumn(name="c_id", referencedColumnName="id")
     */
    protected $course;

    /**
     * @var int
     *
     * @ORM\Column(name="category_id", type="integer", nullable=false)
     */
    protected $categoryId;

    /**
     * @var \DateTime
     *
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(name="created_at", type="datetime", nullable=false)
     */
    protected $createdAt;

    /**
     * @var float
     *
     * @ORM\Column(name="weight", type="float", precision=10, scale=0, nullable=false)
     */
    protected $weight;

    /**
     * @var int
     *
     * @ORM\Column(name="visible", type="integer", nullable=false)
     */
    protected $visible;

    /**
     * @var int
     *
     * @ORM\Column(name="locked", type="integer", nullable=false)
     */
    protected $locked;

    /**
     * @var float
     *
     * @ORM\Column(name="best_score", type="float", precision=6, scale=2, nullable=true)
     */
    protected $bestScore;

    /**
     * @var float
     *
     * @ORM\Column(name="average_score", type="float", precision=6, scale=2, nullable=true)
     */
    protected $averageScore;

    /**
     * @var float
     *
     * @ORM\Column(name="score_weight", type="float", precision=6, scale=2, nullable=true)
     */
    protected $scoreWeight;

    /**
     * @var array
     *
     * @ORM\Column(name="user_score_list", type="array", nullable=true)
     */
    protected $userScoreList;

    /**
     * GradebookEvaluation constructor.
     */
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
     * Set categoryId.
     *
     * @param int $categoryId
     *
     * @return GradebookLink
     */
    public function setCategoryId($categoryId)
    {
        $this->categoryId = $categoryId;

        return $this;
    }

    /**
     * Get categoryId.
     *
     * @return int
     */
    public function getCategoryId()
    {
        return $this->categoryId;
    }

    /**
     * Set createdAt.
     *
     * @param \DateTime $createdAt
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
     * @return \DateTime
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
     *
     * @return GradebookLink
     */
    public function setBestScore($bestScore)
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
     *
     * @return GradebookLink
     */
    public function setAverageScore($averageScore)
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
     *
     * @return GradebookLink
     */
    public function setUserScoreList($userScoreList)
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
     *
     * @return GradebookLink
     */
    public function setScoreWeight($scoreWeight)
    {
        $this->scoreWeight = $scoreWeight;

        return $this;
    }
}
