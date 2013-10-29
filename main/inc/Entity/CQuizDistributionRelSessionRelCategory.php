<?php

namespace Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CQuizDistributionRelSessionRelCategory
 *
 * @ORM\Table(name="c_quiz_distribution_rel_session_rel_category")
 * @ORM\Entity
 */
class CQuizDistributionRelSessionRelCategory
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var integer
     *
     * @ORM\Column(name="session_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $sessionId;

    /**
     * @var integer
     *
     * @ORM\Column(name="c_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $cId;

    /**
     * @var integer
     *
     * @ORM\Column(name="exercise_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $exerciseId;

    /**
     * @var integer
     *
     * @ORM\Column(name="quiz_distribution_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $quizDistributionId;

    /**
     * @var integer
     *
     * @ORM\Column(name="category_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $categoryId;

    /**
     * @var float
     *
     * @ORM\Column(name="modifier", type="float", precision=0, scale=0, nullable=false, unique=false)
     */
    private $modifier;


    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set sessionId
     *
     * @param integer $sessionId
     * @return CQuizDistributionRelSessionRelCategory
     */
    public function setSessionId($sessionId)
    {
        $this->sessionId = $sessionId;

        return $this;
    }

    /**
     * Get sessionId
     *
     * @return integer
     */
    public function getSessionId()
    {
        return $this->sessionId;
    }

    /**
     * Set cId
     *
     * @param integer $cId
     * @return CQuizDistributionRelSessionRelCategory
     */
    public function setCId($cId)
    {
        $this->cId = $cId;

        return $this;
    }

    /**
     * Get cId
     *
     * @return integer
     */
    public function getCId()
    {
        return $this->cId;
    }

    /**
     * Set exerciseId
     *
     * @param integer $exerciseId
     * @return CQuizDistributionRelSessionRelCategory
     */
    public function setExerciseId($exerciseId)
    {
        $this->exerciseId = $exerciseId;

        return $this;
    }

    /**
     * Get exerciseId
     *
     * @return integer
     */
    public function getExerciseId()
    {
        return $this->exerciseId;
    }

    /**
     * Set quizDistributionId
     *
     * @param integer $quizDistributionId
     * @return CQuizDistributionRelSessionRelCategory
     */
    public function setQuizDistributionId($quizDistributionId)
    {
        $this->quizDistributionId = $quizDistributionId;

        return $this;
    }

    /**
     * Get quizDistributionId
     *
     * @return integer
     */
    public function getQuizDistributionId()
    {
        return $this->quizDistributionId;
    }

    /**
     * Set categoryId
     *
     * @param integer $categoryId
     * @return CQuizDistributionRelSessionRelCategory
     */
    public function setCategoryId($categoryId)
    {
        $this->categoryId = $categoryId;

        return $this;
    }

    /**
     * Get categoryId
     *
     * @return integer
     */
    public function getCategoryId()
    {
        return $this->categoryId;
    }

    /**
     * Set modifier
     *
     * @param float $modifier
     * @return CQuizDistributionRelSessionRelCategory
     */
    public function setModifier($modifier)
    {
        $this->modifier = $modifier;

        return $this;
    }

    /**
     * Get modifier
     *
     * @return float
     */
    public function getModifier()
    {
        return $this->modifier;
    }
}
