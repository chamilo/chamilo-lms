<?php

namespace ChamiloLMS\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * QuestionFieldValues
 *
 * @ORM\Table(name="question_field_values")
 * @ORM\Entity
 * @Gedmo\Loggable
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
    //private $question;

    /**
     * @ORM\OneToOne(targetEntity="QuestionField")
     * @ORM\JoinColumn(name="field_id", referencedColumnName="id")
     */
    //private $field;

    /**
     * @var string
     * @Gedmo\Versioned
     *
     * @ORM\Column(name="field_value", type="text", precision=0, scale=0, nullable=true, unique=false)
     */
    private $fieldValue;

     /**
     * Set fieldValue
     *
     * @param string $fieldValue
     * @return ExtraFieldValues
     */
    public function setFieldValue($fieldValue)
    {
        $this->fieldValue = $fieldValue;

        return $this;
    }

    /**
     * Get fieldValue
     *
     * @return string
     */
    public function getFieldValue()
    {
        return $this->fieldValue;
    }

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
