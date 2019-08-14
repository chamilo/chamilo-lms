<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * GradebookEvaluation.
 *
 * @ORM\Table(name="gradebook_evaluation")
 * @ORM\Entity
 */
class GradebookEvaluation
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
     * @var string
     *
     * @ORM\Column(name="name", type="text", nullable=false)
     */
    protected $name;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="text", nullable=true)
     */
    protected $description;

    /**
     * @var int
     *
     * @ORM\Column(name="user_id", type="integer", nullable=false)
     */
    protected $userId;

    /**
     * @var string
     *
     * @ORM\Column(name="course_code", type="string", length=40, nullable=true)
     */
    protected $courseCode;

    /**
     * @var int
     *
     * @ORM\Column(name="category_id", type="integer", nullable=true)
     */
    protected $categoryId;

    /**
     * @var \DateTime
     *
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
     * @var float
     *
     * @ORM\Column(name="max", type="float", precision=10, scale=0, nullable=false)
     */
    protected $max;

    /**
     * @var int
     *
     * @ORM\Column(name="visible", type="integer", nullable=false)
     */
    protected $visible;

    /**
     * @var string
     *
     * @ORM\Column(name="type", type="string", length=40, nullable=false)
     */
    protected $type;

    /**
     * @var int
     *
     * @ORM\Column(name="locked", type="integer", nullable=false)
     */
    protected $locked;

    /**
     * @var float
     *
     * ORM\Column(name="best_score", type="float", precision=6, scale=2, nullable=true)
     */
    protected $bestScore;

    /**
     * @var float
     *
     * ORM\Column(name="average_score", type="float", precision=6, scale=2, nullable=true)
     */
    protected $averageScore;

    /**
     * @var float
     *
     * ORM\Column(name="score_weight", type="float", precision=6, scale=2, nullable=true)
     */
    protected $scoreWeight;

    /**
     * @var array
     *
     * ORM\Column(name="user_score_list", type="array", nullable=true)
     */
    protected $userScoreList;

    /**
     * Set name.
     *
     * @param string $name
     *
     * @return GradebookEvaluation
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set description.
     *
     * @param string $description
     *
     * @return GradebookEvaluation
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description.
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set userId.
     *
     * @param int $userId
     *
     * @return GradebookEvaluation
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
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * Set courseCode.
     *
     * @param string $courseCode
     *
     * @return GradebookEvaluation
     */
    public function setCourseCode($courseCode)
    {
        $this->courseCode = $courseCode;

        return $this;
    }

    /**
     * Get courseCode.
     *
     * @return string
     */
    public function getCourseCode()
    {
        return $this->courseCode;
    }

    /**
     * Set categoryId.
     *
     * @param int $categoryId
     *
     * @return GradebookEvaluation
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
     * @return GradebookEvaluation
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
     * @return GradebookEvaluation
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
     * Set max.
     *
     * @param float $max
     *
     * @return GradebookEvaluation
     */
    public function setMax($max)
    {
        $this->max = $max;

        return $this;
    }

    /**
     * Get max.
     *
     * @return float
     */
    public function getMax()
    {
        return $this->max;
    }

    /**
     * Set visible.
     *
     * @param int $visible
     *
     * @return GradebookEvaluation
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
     * Set type.
     *
     * @param string $type
     *
     * @return GradebookEvaluation
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type.
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set locked.
     *
     * @param int $locked
     *
     * @return GradebookEvaluation
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
     * @return GradebookEvaluation
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
     * @return GradebookEvaluation
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
     * @return GradebookEvaluation
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
     * @return GradebookEvaluation
     */
    public function setScoreWeight($scoreWeight)
    {
        $this->scoreWeight = $scoreWeight;

        return $this;
    }
}
