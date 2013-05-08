<?php

namespace Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * QuestionFieldValues
 *
 * @ORM\Table(name="question_field_values")
 * @ORM\Entity
 */
class QuestionFieldValues extends ExtraFieldValues
{

    /**
     * @var integer
     *
     * @ORM\Column(name="question_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $questionId;

    /**
     * @ORM\ManyToOne(targetEntity="CQuizQuestion")
     * @ORM\JoinColumn(name="question_id", referencedColumnName="iid")
     */
    private $question;

    /**
     * @ORM\OneToOne(targetEntity="QuestionField")
     * @ORM\JoinColumn(name="field_id", referencedColumnName="id")
     */
    private $field;

    /**
     * Set questionId
     *
     * @param integer $questionId
     * @return QuestionFieldValues
     */
    public function setQuestionId($questionId)
    {
        $this->questionId = $questionId;

        return $this;
    }

    /**
     * Get questionId
     *
     * @return integer
     */
    public function getQuestionId()
    {
        return $this->questionId;
    }
}
