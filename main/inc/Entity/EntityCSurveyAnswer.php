<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * EntityCSurveyAnswer
 *
 * @Table(name="c_survey_answer")
 * @Entity
 */
class EntityCSurveyAnswer
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
     * @Column(name="answer_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     * @Id
     * @GeneratedValue(strategy="NONE")
     */
    private $answerId;

    /**
     * @var integer
     *
     * @Column(name="survey_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $surveyId;

    /**
     * @var integer
     *
     * @Column(name="question_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $questionId;

    /**
     * @var string
     *
     * @Column(name="option_id", type="text", precision=0, scale=0, nullable=false, unique=false)
     */
    private $optionId;

    /**
     * @var integer
     *
     * @Column(name="value", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $value;

    /**
     * @var string
     *
     * @Column(name="user", type="string", length=250, precision=0, scale=0, nullable=false, unique=false)
     */
    private $user;


    /**
     * Set cId
     *
     * @param integer $cId
     * @return EntityCSurveyAnswer
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
     * Set answerId
     *
     * @param integer $answerId
     * @return EntityCSurveyAnswer
     */
    public function setAnswerId($answerId)
    {
        $this->answerId = $answerId;

        return $this;
    }

    /**
     * Get answerId
     *
     * @return integer 
     */
    public function getAnswerId()
    {
        return $this->answerId;
    }

    /**
     * Set surveyId
     *
     * @param integer $surveyId
     * @return EntityCSurveyAnswer
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
     * Set questionId
     *
     * @param integer $questionId
     * @return EntityCSurveyAnswer
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
     * Set optionId
     *
     * @param string $optionId
     * @return EntityCSurveyAnswer
     */
    public function setOptionId($optionId)
    {
        $this->optionId = $optionId;

        return $this;
    }

    /**
     * Get optionId
     *
     * @return string 
     */
    public function getOptionId()
    {
        return $this->optionId;
    }

    /**
     * Set value
     *
     * @param integer $value
     * @return EntityCSurveyAnswer
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

    /**
     * Set user
     *
     * @param string $user
     * @return EntityCSurveyAnswer
     */
    public function setUser($user)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user
     *
     * @return string 
     */
    public function getUser()
    {
        return $this->user;
    }
}
