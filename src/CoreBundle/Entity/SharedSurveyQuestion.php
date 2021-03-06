<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * SharedSurveyQuestion.
 *
 * @ORM\Table(name="shared_survey_question")
 * @ORM\Entity
 */
class SharedSurveyQuestion
{
    /**
     * @ORM\Column(name="survey_id", type="integer", nullable=false)
     */
    protected int $surveyId;

    /**
     * @ORM\Column(name="survey_question", type="text", nullable=false)
     */
    protected string $surveyQuestion;

    /**
     * @ORM\Column(name="survey_question_comment", type="text", nullable=false)
     */
    protected string $surveyQuestionComment;

    /**
     * @ORM\Column(name="type", type="string", length=250, nullable=false)
     */
    protected string $type;

    /**
     * @ORM\Column(name="display", type="string", length=10, nullable=false)
     */
    protected string $display;

    /**
     * @ORM\Column(name="sort", type="integer", nullable=false)
     */
    protected int $sort;

    /**
     * @ORM\Column(name="code", type="string", length=40, nullable=false)
     */
    protected string $code;

    /**
     * @ORM\Column(name="max_value", type="integer", nullable=false)
     */
    protected int $maxValue;

    /**
     * @ORM\Column(name="question_id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected int $questionId;

    /**
     * Set surveyId.
     *
     * @return SharedSurveyQuestion
     */
    public function setSurveyId(int $surveyId)
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
     * Set surveyQuestion.
     *
     * @return SharedSurveyQuestion
     */
    public function setSurveyQuestion(string $surveyQuestion)
    {
        $this->surveyQuestion = $surveyQuestion;

        return $this;
    }

    /**
     * Get surveyQuestion.
     *
     * @return string
     */
    public function getSurveyQuestion()
    {
        return $this->surveyQuestion;
    }

    /**
     * Set surveyQuestionComment.
     *
     * @return SharedSurveyQuestion
     */
    public function setSurveyQuestionComment(string $surveyQuestionComment)
    {
        $this->surveyQuestionComment = $surveyQuestionComment;

        return $this;
    }

    /**
     * Get surveyQuestionComment.
     *
     * @return string
     */
    public function getSurveyQuestionComment()
    {
        return $this->surveyQuestionComment;
    }

    /**
     * Set type.
     *
     * @return SharedSurveyQuestion
     */
    public function setType(string $type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type.
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set display.
     *
     * @return SharedSurveyQuestion
     */
    public function setDisplay(string $display)
    {
        $this->display = $display;

        return $this;
    }

    /**
     * Get display.
     *
     * @return string
     */
    public function getDisplay()
    {
        return $this->display;
    }

    /**
     * Set sort.
     *
     * @return SharedSurveyQuestion
     */
    public function setSort(int $sort)
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
     * Set code.
     *
     * @return SharedSurveyQuestion
     */
    public function setCode(string $code)
    {
        $this->code = $code;

        return $this;
    }

    /**
     * Get code.
     *
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * Set maxValue.
     *
     * @return SharedSurveyQuestion
     */
    public function setMaxValue(int $maxValue)
    {
        $this->maxValue = $maxValue;

        return $this;
    }

    /**
     * Get maxValue.
     *
     * @return int
     */
    public function getMaxValue()
    {
        return $this->maxValue;
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
}
