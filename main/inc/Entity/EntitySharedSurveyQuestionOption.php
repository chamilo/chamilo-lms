<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * EntitySharedSurveyQuestionOption
 *
 * @Table(name="shared_survey_question_option")
 * @Entity
 */
class EntitySharedSurveyQuestionOption
{
    /**
     * @var integer
     *
     * @Column(name="question_option_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     * @Id
     * @GeneratedValue(strategy="IDENTITY")
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
     * @return EntitySharedSurveyQuestionOption
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
     * @return EntitySharedSurveyQuestionOption
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
     * @return EntitySharedSurveyQuestionOption
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
     * @return EntitySharedSurveyQuestionOption
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
}
