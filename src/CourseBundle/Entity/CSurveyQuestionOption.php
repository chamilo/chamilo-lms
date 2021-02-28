<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CSurveyQuestionOption.
 *
 * @ORM\Table(
 *  name="c_survey_question_option",
 *  indexes={
 *     @ORM\Index(name="course", columns={"c_id"}),
 *     @ORM\Index(name="idx_survey_qo_qid", columns={"question_id"})
 *  }
 * )
 * @ORM\Entity
 */
class CSurveyQuestionOption
{
    /**
     * @ORM\Column(name="iid", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected int $iid;

    /**
     * @ORM\Column(name="c_id", type="integer")
     */
    protected int $cId;

    /**
     * @ORM\Column(name="question_id", type="integer", nullable=false)
     */
    protected int $questionId;

    /**
     * @ORM\Column(name="survey_id", type="integer", nullable=false)
     */
    protected int $surveyId;

    /**
     * @ORM\Column(name="option_text", type="text", nullable=false)
     */
    protected string $optionText;

    /**
     * @ORM\Column(name="sort", type="integer", nullable=false)
     */
    protected int $sort;

    /**
     * @ORM\Column(name="value", type="integer", nullable=false)
     */
    protected int $value;

    public function __construct()
    {
    }

    public function getIid(): int
    {
        return $this->iid;
    }

    /**
     * Set questionId.
     *
     * @param int $questionId
     *
     * @return CSurveyQuestionOption
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
     * Set surveyId.
     *
     * @param int $surveyId
     *
     * @return CSurveyQuestionOption
     */
    public function setSurveyId($surveyId)
    {
        $this->surveyId = $surveyId;

        return $this;
    }

    /**
     * Get surveyId.
     *
     * @return int
     */
    public function getSurveyId()
    {
        return $this->surveyId;
    }

    /**
     * Set optionText.
     *
     * @param string $optionText
     *
     * @return CSurveyQuestionOption
     */
    public function setOptionText($optionText)
    {
        $this->optionText = $optionText;

        return $this;
    }

    /**
     * Get optionText.
     *
     * @return string
     */
    public function getOptionText()
    {
        return $this->optionText;
    }

    /**
     * Set sort.
     *
     * @param int $sort
     *
     * @return CSurveyQuestionOption
     */
    public function setSort($sort)
    {
        $this->sort = $sort;

        return $this;
    }

    /**
     * Get sort.
     *
     * @return int
     */
    public function getSort()
    {
        return $this->sort;
    }

    /**
     * Set value.
     *
     * @param int $value
     *
     * @return CSurveyQuestionOption
     */
    public function setValue($value)
    {
        $this->value = $value;

        return $this;
    }

    /**
     * Get value.
     *
     * @return int
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Set cId.
     *
     * @param int $cId
     *
     * @return CSurveyQuestionOption
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
}
