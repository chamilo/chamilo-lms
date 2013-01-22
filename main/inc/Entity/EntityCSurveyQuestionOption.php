<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * EntityCSurveyQuestionOption
 *
 * @Table(name="c_survey_question_option")
 * @Entity
 */
class EntityCSurveyQuestionOption
{
    /**
     * @var integer
     *
     * @Column(name="c_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     * @Id
     * @GeneratedValue(strategy="NONE")
     */
    private $cId;

    /**
     * @var integer
     *
     * @Column(name="question_option_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     * @Id
     * @GeneratedValue(strategy="NONE")
     */
    private $questionOptionId;

    /**
     * @var integer
     *
     * @Column(name="question_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $questionId;

    /**
     * @var integer
     *
     * @Column(name="survey_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $surveyId;

    /**
     * @var string
     *
     * @Column(name="option_text", type="text", precision=0, scale=0, nullable=false, unique=false)
     */
    private $optionText;

    /**
     * @var integer
     *
     * @Column(name="sort", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $sort;

    /**
     * @var integer
     *
     * @Column(name="value", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $value;


    /**
     * Set cId
     *
     * @param integer $cId
     * @return EntityCSurveyQuestionOption
     */
    public function setCId($cId)
    {
        $this->cId = $cId;

        return $this;
    }

    /**
     * Get cId
     *
     * @return integer 
     */
    public function getCId()
    {
        return $this->cId;
    }

    /**
     * Set questionOptionId
     *
     * @param integer $questionOptionId
     * @return EntityCSurveyQuestionOption
     */
    public function setQuestionOptionId($questionOptionId)
    {
        $this->questionOptionId = $questionOptionId;

        return $this;
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

    /**
     * Set questionId
     *
     * @param integer $questionId
     * @return EntityCSurveyQuestionOption
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
     * @return EntityCSurveyQuestionOption
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
     * @return EntityCSurveyQuestionOption
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
     * @return EntityCSurveyQuestionOption
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
     * Set value
     *
     * @param integer $value
     * @return EntityCSurveyQuestionOption
     */
    public function setValue($value)
    {
        $this->value = $value;

        return $this;
    }

    /**
     * Get value
     *
     * @return integer 
     */
    public function getValue()
    {
        return $this->value;
    }
}
