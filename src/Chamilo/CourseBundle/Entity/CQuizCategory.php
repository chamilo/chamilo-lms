<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CQuizCategory.
 *
 * Add @ to the ORM\Index line if $_configuration['quiz_allow_time_control_per_category'] is true.
 *
 * @ORM\Table(
 *     name="c_quiz_rel_category",
 *     indexes={
 *          ORM\Index(name="idx_course_category_exercise", columns={"c_id", "category_id", "exercise_id"})
 *     }
 * )
 * @ORM\Entity
 */
class CQuizCategory
{
    /**
     * @var int
     *
     * @ORM\Column(name="iid", type="bigint")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected $iid;

    /**
     * @var int
     *
     * @ORM\Column(name="c_id", type="integer")
     */
    protected $cId;

    /**
     * @var int
     *
     * @ORM\Column(name="category_id", type="integer", nullable=true)
     */
    protected $categoryId;

    /**
     * @var int
     *
     * @ORM\Column(name="exercise_id", type="integer", nullable=false)
     */
    protected $exerciseId;

    /**
     * @var int
     *
     * @ORM\Column(name="count_questions", type="integer", nullable=true)
     */
    protected $countQuestions;

    /**
     * @var string
     *
     * Add @ to the next line if api_get_configuration_value('quiz_question_category_destinations') is true
     * ORM\Column(name="destinations", type="text", nullable=true)
     */
    protected $destinations;

    /**
     * @var int
     *
     * Add @ to the next line if $_configuration['quiz_allow_time_control_per_category'] is true
     * ORM\Column(name="expired_time", type="integer", nullable=false, options={"default": 0})
     */
    protected $expiredTime;

    /**
     * @return int
     */
    public function getIid()
    {
        return $this->iid;
    }

    /**
     * @param int $iid
     *
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
     *
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
     *
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
     *
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
     *
     * @return CQuizCategory
     */
    public function setCountQuestions($countQuestions)
    {
        $this->countQuestions = $countQuestions;

        return $this;
    }

    /**
     * @return string
     */
    public function getDestinations()
    {
        return $this->destinations;
    }

    /**
     * @param string $destinations
     */
    public function setDestinations($destinations)
    {
        $this->destinations = $destinations;
    }

    /**
     * @return int
     */
    public function getExpiredTime()
    {
        return $this->expiredTime;
    }

    /**
     * @param int $expiredTime
     *
     * @return CQuizCategory
     */
    public function setExpiredTime($expiredTime)
    {
        $this->expiredTime = $expiredTime;

        return $this;
    }
}
