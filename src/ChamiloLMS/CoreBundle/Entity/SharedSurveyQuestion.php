<?php

namespace ChamiloLMS\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * SharedSurveyQuestion
 *
 * @ORM\Table(name="shared_survey_question")
 * @ORM\Entity
 */
class SharedSurveyQuestion
{
    /**
     * @var integer
     *
     * @ORM\Column(name="question_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $questionId;

    /**
     * @var integer
     *
     * @ORM\Column(name="survey_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $surveyId;

    /**
     * @var string
     *
     * @ORM\Column(name="survey_question", type="text", precision=0, scale=0, nullable=false, unique=false)
     */
    private $surveyQuestion;

    /**
     * @var string
     *
     * @ORM\Column(name="survey_question_comment", type="text", precision=0, scale=0, nullable=false, unique=false)
     */
    private $surveyQuestionComment;

    /**
     * @var string
     *
     * @ORM\Column(name="type", type="string", length=250, precision=0, scale=0, nullable=false, unique=false)
     */
    private $type;

    /**
     * @var string
     *
     * @ORM\Column(name="display", type="string", length=10, precision=0, scale=0, nullable=false, unique=false)
     */
    private $display;

    /**
     * @var integer
     *
     * @ORM\Column(name="sort", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $sort;

    /**
     * @var string
     *
     * @ORM\Column(name="code", type="string", length=40, precision=0, scale=0, nullable=false, unique=false)
     */
    private $code;

    /**
     * @var integer
     *
     * @ORM\Column(name="max_value", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $maxValue;


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
     * @return SharedSurveyQuestion
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
     * Set surveyQuestion
     *
     * @param string $surveyQuestion
     * @return SharedSurveyQuestion
     */
    public function setSurveyQuestion($surveyQuestion)
    {
        $this->surveyQuestion = $surveyQuestion;

        return $this;
    }

    /**
     * Get surveyQuestion
     *
     * @return string
     */
    public function getSurveyQuestion()
    {
        return $this->surveyQuestion;
    }

    /**
     * Set surveyQuestionComment
     *
     * @param string $surveyQuestionComment
     * @return SharedSurveyQuestion
     */
    public function setSurveyQuestionComment($surveyQuestionComment)
    {
        $this->surveyQuestionComment = $surveyQuestionComment;

        return $this;
    }

    /**
     * Get surveyQuestionComment
     *
     * @return string
     */
    public function getSurveyQuestionComment()
    {
        return $this->surveyQuestionComment;
    }

    /**
     * Set type
     *
     * @param string $type
     * @return SharedSurveyQuestion
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set display
     *
     * @param string $display
     * @return SharedSurveyQuestion
     */
    public function setDisplay($display)
    {
        $this->display = $display;

        return $this;
    }

    /**
     * Get display
     *
     * @return string
     */
    public function getDisplay()
    {
        return $this->display;
    }

    /**
     * Set sort
     *
     * @param integer $sort
     * @return SharedSurveyQuestion
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
     * Set code
     *
     * @param string $code
     * @return SharedSurveyQuestion
     */
    public function setCode($code)
    {
        $this->code = $code;

        return $this;
    }

    /**
     * Get code
     *
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * Set maxValue
     *
     * @param integer $maxValue
     * @return SharedSurveyQuestion
     */
    public function setMaxValue($maxValue)
    {
        $this->maxValue = $maxValue;

        return $this;
    }

    /**
     * Get maxValue
     *
     * @return integer
     */
    public function getMaxValue()
    {
        return $this->maxValue;
    }
}
