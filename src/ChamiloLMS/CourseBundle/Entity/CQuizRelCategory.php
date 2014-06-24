<?php

namespace ChamiloLMS\CourseBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CQuizRelCategory
 *
 * @ORM\Table(name="c_quiz_rel_category")
 * @ORM\Entity
 */
class CQuizRelCategory
{
    /**
     * @var integer
     *
     * @ORM\Column(name="iid", type="bigint", precision=0, scale=0, nullable=false, unique=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $iid;

    /**
     * @var integer
     *
     * @ORM\Column(name="c_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $cId;

    /**
     * @var integer
     *
     * @ORM\Column(name="category_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $categoryId;

    /**
     * @var integer
     *
     * @ORM\Column(name="exercise_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $exerciseId;

    /**
     * @var integer
     *
     * @ORM\Column(name="count_questions", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $countQuestions;


    /**
     * Get iid
     *
     * @return integer
     */
    public function getIid()
    {
        return $this->iid;
    }

    /**
     * Set cId
     *
     * @param integer $cId
     * @return CQuizRelCategory
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
     * Set categoryId
     *
     * @param integer $categoryId
     * @return CQuizRelCategory
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
     * Set exerciseId
     *
     * @param integer $exerciseId
     * @return CQuizRelCategory
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
     * Set countQuestions
     *
     * @param integer $countQuestions
     * @return CQuizRelCategory
     */
    public function setCountQuestions($countQuestions)
    {
        $this->countQuestions = $countQuestions;

        return $this;
    }

    /**
     * Get countQuestions
     *
     * @return integer
     */
    public function getCountQuestions()
    {
        return $this->countQuestions;
    }
}
