<?php

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * SharedSurveyQuestionOption
 *
 * @ORM\Table(name="shared_survey_question_option")
 * @ORM\Entity
 */
class SharedSurveyQuestionOption
{
    /**
     * @var integer
     *
     * @ORM\Column(name="question_id", type="integer", nullable=false)
     */
    private $questionId;

    /**
     * @var integer
     *
     * @ORM\Column(name="survey_id", type="integer", nullable=false)
     */
    private $surveyId;

    /**
     * @var string
     *
     * @ORM\Column(name="option_text", type="text", nullable=false)
     */
    private $optionText;

    /**
     * @var integer
     *
     * @ORM\Column(name="sort", type="integer", nullable=false)
     */
    private $sort;

    /**
     * @var integer
     *
     * @ORM\Column(name="question_option_id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $questionOptionId;



    /**
     * Set questionId
     *
     * @param integer $questionId
     * @return SharedSurveyQuestionOption
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

    /**
     * Set surveyId
     *
     * @param integer $surveyId
     * @return SharedSurveyQuestionOption
     */
    public function setSurveyId($surveyId)
    {
        $this->surveyId = $surveyId;

        return $this;
    }

    /**
     * Get surveyId
     *
     * @return integer
     */
    public function getSurveyId()
    {
        return $this->surveyId;
    }

    /**
     * Set optionText
     *
     * @param string $optionText
     * @return SharedSurveyQuestionOption
     */
    public function setOptionText($optionText)
    {
        $this->optionText = $optionText;

        return $this;
    }

    /**
     * Get optionText
     *
     * @return string
     */
    public function getOptionText()
    {
        return $this->optionText;
    }

    /**
     * Set sort
     *
     * @param integer $sort
     * @return SharedSurveyQuestionOption
     */
    public function setSort($sort)
    {
        $this->sort = $sort;

        return $this;
    }

    /**
     * Get sort
     *
     * @return integer
     */
    public function getSort()
    {
        return $this->sort;
    }

    /**
     * Get questionOptionId
     *
     * @return integer
     */
    public function getQuestionOptionId()
    {
        return $this->questionOptionId;
    }
}
