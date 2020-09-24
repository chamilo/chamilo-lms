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
     * @var CQuizQuestion
     *
     * @ORM\ManyToOne(targetEntity="CQuizQuestion", inversedBy="relQuizzes", cascade={"persist"})
     * @ORM\JoinColumn(name="question_id", referencedColumnName="iid")
     */
    protected $question;

    /**
     * @var CQuiz
     *
     * @ORM\ManyToOne(targetEntity="CQuiz", inversedBy="questions", cascade={"persist"})
     * @ORM\JoinColumn(name="exercice_id", referencedColumnName="iid")
     */
    protected $quiz;

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

    public function getQuiz()
    {
        return $this->quiz;
    }

    public function getQuestion()
    {
        return $this->question;
    }
}
