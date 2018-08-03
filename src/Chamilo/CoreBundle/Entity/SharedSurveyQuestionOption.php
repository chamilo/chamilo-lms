<?php
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
     * @var int
     *
     * @ORM\Column(name="question_id", type="integer", nullable=false)
     */
    protected $questionId;

    /**
     * @var int
     *
     * @ORM\Column(name="survey_id", type="integer", nullable=false)
     */
    protected $surveyId;

    /**
     * @var string
     *
     * @ORM\Column(name="option_text", type="text", nullable=false)
     */
    protected $optionText;

    /**
     * @var int
     *
     * @ORM\Column(name="sort", type="integer", nullable=false)
     */
    protected $sort;

    /**
     * @var int
     *
     * @ORM\Column(name="question_option_id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $questionOptionId;

    /**
     * Set questionId.
     *
     * @param int $questionId
     *
     * @return SharedSurveyQuestionOption
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
     * @return SharedSurveyQuestionOption
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
     * @return SharedSurveyQuestionOption
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
     * @return SharedSurveyQuestionOption
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
     * Get questionOptionId.
     *
     * @return int
     */
    public function getQuestionOptionId()
    {
        return $this->questionOptionId;
    }
}
