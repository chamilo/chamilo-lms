<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * SharedSurveyQuestionOption.
 *
 * @ORM\Table(name="shared_survey_question_option")
 * @ORM\Entity
 */
class SharedSurveyQuestionOption
{
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
     * @ORM\Column(name="question_option_id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected int $questionOptionId;

    /**
     * Set questionId.
     *
     * @return SharedSurveyQuestionOption
     */
    public function setQuestionId(int $questionId)
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
     * @return SharedSurveyQuestionOption
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
     * Set optionText.
     *
     * @return SharedSurveyQuestionOption
     */
    public function setOptionText(string $optionText)
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
     * @return SharedSurveyQuestionOption
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
     * Get questionOptionId.
     *
     * @return int
     */
    public function getQuestionOptionId()
    {
        return $this->questionOptionId;
    }
}
