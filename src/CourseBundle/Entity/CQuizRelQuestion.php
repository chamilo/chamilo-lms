<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * CQuizRelQuestion.
 *
 * @ORM\Table(
 *  name="c_quiz_rel_question",
 *  indexes={
 *      @ORM\Index(name="course", columns={"c_id"}),
 *      @ORM\Index(name="question", columns={"question_id"}),
 *      @ORM\Index(name="exercise", columns={"quiz_id"})
 *  }
 * )
 * @ORM\Entity
 */
class CQuizRelQuestion
{
    /**
     * @ORM\Column(name="iid", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected int $iid;

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
     * @Assert\NotBlank()
     * @ORM\ManyToOne(targetEntity="CQuizQuestion", inversedBy="relQuizzes", cascade={"persist"})
     * @ORM\JoinColumn(name="question_id", referencedColumnName="iid", onDelete="CASCADE")
     */
    protected CQuizQuestion $question;

    /**
     * @Assert\NotBlank()
     * @ORM\ManyToOne(targetEntity="CQuiz", inversedBy="questions", cascade={"persist"})
     * @ORM\JoinColumn(name="quiz_id", referencedColumnName="iid", onDelete="CASCADE")
     */
    protected CQuiz $quiz;

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

    public function getQuiz(): CQuiz
    {
        return $this->quiz;
    }

    public function getQuestion(): CQuizQuestion
    {
        return $this->question;
    }
}
