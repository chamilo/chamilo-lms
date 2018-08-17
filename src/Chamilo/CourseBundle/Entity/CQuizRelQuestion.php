<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CQuizRelQuestion.
 *
 * @ORM\Table(
 *  name="c_quiz_rel_question",
 *  indexes={
 *      @ORM\Index(name="course", columns={"c_id"}),
 *      @ORM\Index(name="question", columns={"question_id"}),
 *      @ORM\Index(name="exercise", columns={"exercice_id"})
 *  }
 * )
 * @ORM\Entity
 */
class CQuizRelQuestion
{
    /**
     * @var int
     *
     * @ORM\Column(name="iid", type="integer")
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
     * @ORM\Column(name="question_order", type="integer", nullable=false)
     */
    protected $questionOrder;

    /**
     * @var int
     *
     * @ORM\Column(name="question_id", type="integer")
     */
    protected $questionId;

    /**
     * @var int
     *
     * @ORM\Column(name="exercice_id", type="integer")
     */
    protected $exerciceId;

    /**
     * Set questionOrder.
     *
     * @param int $questionOrder
     *
     * @return CQuizRelQuestion
     */
    public function setQuestionOrder($questionOrder)
    {
        $this->questionOrder = $questionOrder;

        return $this;
    }

    /**
     * Get questionOrder.
     *
     * @return int
     */
    public function getQuestionOrder()
    {
        return $this->questionOrder;
    }

    /**
     * Set cId.
     *
     * @param int $cId
     *
     * @return CQuizRelQuestion
     */
    public function setCId($cId)
    {
        $this->cId = $cId;

        return $this;
    }

    /**
     * Get cId.
     *
     * @return int
     */
    public function getCId()
    {
        return $this->cId;
    }

    /**
     * Set questionId.
     *
     * @param int $questionId
     *
     * @return CQuizRelQuestion
     */
    public function setQuestionId($questionId)
    {
        $this->questionId = $questionId;

        return $this;
    }

    /**
     * Get questionId.
     *
     * @return int
     */
    public function getQuestionId()
    {
        return $this->questionId;
    }

    /**
     * Set exerciceId.
     *
     * @param int $exerciceId
     *
     * @return CQuizRelQuestion
     */
    public function setExerciceId($exerciceId)
    {
        $this->exerciceId = $exerciceId;

        return $this;
    }

    /**
     * Get exerciceId.
     *
     * @return int
     */
    public function getExerciceId()
    {
        return $this->exerciceId;
    }
}
