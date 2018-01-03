<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CQuizCategory
 *
 * @ORM\Table(name="c_quiz_rel_category")
 * @ORM\Entity
 */
class CQuizCategory
{
    /**
     * @var integer
     *
     * @ORM\Column(name="iid", type="bigint")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    private $iid;

    /**
     * @var integer
     *
     * @ORM\Column(name="c_id", type="integer")
     */
    private $cId;

    /**
     * @var integer
     *
     * @ORM\Column(name="category_id", type="integer", nullable=true)
     */
    private $categoryId;

    /**
     * @var integer
     *
     * @ORM\Column(name="exercise_id", type="integer", nullable=false)
     */
    private $exerciseId;

    /**
     * @var integer
     *
     * @ORM\Column(name="count_questions", type="integer", nullable=true)
     */
    private $countQuestions;

    /**
     * @return int
     */
    public function getIid()
    {
        return $this->iid;
    }

    /**
     * @param int $iid
     * @return CQuizCategory
     */
    public function setIid($iid)
    {
        $this->iid = $iid;
        return $this;
    }

    /**
     * @return int
     */
    public function getCId()
    {
        return $this->cId;
    }

    /**
     * @param int $cId
     * @return CQuizCategory
     */
    public function setCId($cId)
    {
        $this->cId = $cId;
        return $this;
    }

    /**
     * @return int
     */
    public function getCategoryId()
    {
        return $this->categoryId;
    }

    /**
     * @param int $categoryId
     * @return CQuizCategory
     */
    public function setCategoryId($categoryId)
    {
        $this->categoryId = $categoryId;
        return $this;
    }

    /**
     * @return int
     */
    public function getExerciseId()
    {
        return $this->exerciseId;
    }

    /**
     * @param int $exerciseId
     * @return CQuizCategory
     */
    public function setExerciseId($exerciseId)
    {
        $this->exerciseId = $exerciseId;
        return $this;
    }

    /**
     * @return int
     */
    public function getCountQuestions()
    {
        return $this->countQuestions;
    }

    /**
     * @param int $countQuestions
     * @return CQuizCategory
     */
    public function setCountQuestions($countQuestions)
    {
        $this->countQuestions = $countQuestions;
        return $this;
    }
}
