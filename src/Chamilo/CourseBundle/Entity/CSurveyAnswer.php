<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CSurveyAnswer.
 *
 * @ORM\Table(
 *  name="c_survey_answer",
 *  indexes={
 *      @ORM\Index(name="course", columns={"c_id"})
 *  }
 * )
 * @ORM\Entity
 */
class CSurveyAnswer
{
    /**
     * @var int
     *
     * @ORM\Column(name="iid", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected $iid;

    /**
     * @var int
     *
     * @ORM\Column(name="c_id", type="integer")
     */
    protected $cId;

    /**
     * @var int
     *
     * @ORM\Column(name="answer_id", type="integer")
     */
    protected $answerId;

    /**
     * @var int
     *
     * @ORM\Column(name="survey_id", type="integer", nullable=false)
     */
    protected $surveyId;

    /**
     * @var int
     *
     * @ORM\Column(name="question_id", type="integer", nullable=false)
     */
    protected $questionId;

    /**
     * @var string
     *
     * @ORM\Column(name="option_id", type="text", nullable=false)
     */
    protected $optionId;

    /**
     * @var int
     *
     * @ORM\Column(name="value", type="integer", nullable=false)
     */
    protected $value;

    /**
     * @var string
     *
     * @ORM\Column(name="user", type="string", length=250, nullable=false)
     */
    protected $user;

    /**
     * @var int
     *
     * ORM\Column(name="session_id", type="integer", nullable=false)
     */
    protected $sessionId;

    /**
     * @var int
     *
     * ORM\Column(name="c_lp_item_id", type="integer", nullable=false)
     */
    protected $lpItemId;

    /**
     * Set surveyId.
     *
     * @param int $surveyId
     *
     * @return CSurveyAnswer
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
     * Set questionId.
     *
     * @param int $questionId
     *
     * @return CSurveyAnswer
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
     * Set optionId.
     *
     * @param string $optionId
     *
     * @return CSurveyAnswer
     */
    public function setOptionId($optionId)
    {
        $this->optionId = $optionId;

        return $this;
    }

    /**
     * Get optionId.
     *
     * @return string
     */
    public function getOptionId()
    {
        return $this->optionId;
    }

    /**
     * Set value.
     *
     * @param int $value
     *
     * @return CSurveyAnswer
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
     * Set user.
     *
     * @param string $user
     *
     * @return CSurveyAnswer
     */
    public function setUser($user)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user.
     *
     * @return string
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Set answerId.
     *
     * @param int $answerId
     *
     * @return CSurveyAnswer
     */
    public function setAnswerId($answerId)
    {
        $this->answerId = $answerId;

        return $this;
    }

    /**
     * Get answerId.
     *
     * @return int
     */
    public function getAnswerId()
    {
        return $this->answerId;
    }

    /**
     * Set cId.
     *
     * @param int $cId
     *
     * @return CSurveyAnswer
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

    /**
     * Get session Id.
     *
     * @return int
     */
    public function getSessionId()
    {
        return $this->sessionId;
    }

    /**
     * Set session Id.
     *
     * @param int $sessionId
     *
     * @return CSurveyAnswer
     */
    public function setSessionId($sessionId)
    {
        $this->sessionId = $sessionId;

        return $this;
    }

    /**
     * Get the lp item Id.
     *
     * @return int
     */
    public function getLpItemId()
    {
        return $this->lpItemId;
    }

    /**
     * Set lp item Id.
     *
     * @return CSurveyAnswer
     */
    public function setLpItemId(int $lpItemId)
    {
        $this->lpItemId = $lpItemId;

        return $this;
    }
}
