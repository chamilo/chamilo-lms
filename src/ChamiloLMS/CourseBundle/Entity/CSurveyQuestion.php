<?php

namespace ChamiloLMS\CourseBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CSurveyQuestion
 *
 * @ORM\Table(name="c_survey_question")
 * @ORM\Entity
 */
class CSurveyQuestion
{
    /**
     * @var integer
     *
     * @ORM\Column(name="iid", type="integer", precision=0, scale=0, nullable=false, unique=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $iid;

    /**
     * @var integer
     *
     * @ORM\Column(name="c_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $cId;

    /**
     * @var integer
     *
     * @ORM\Column(name="question_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
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
     * @var integer
     *
     * @ORM\Column(name="shared_question_id", type="integer", precision=0, scale=0, nullable=true, unique=false)
     */
    private $sharedQuestionId;

    /**
     * @var integer
     *
     * @ORM\Column(name="max_value", type="integer", precision=0, scale=0, nullable=true, unique=false)
     */
    private $maxValue;

    /**
     * @var integer
     *
     * @ORM\Column(name="survey_group_pri", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $surveyGroupPri;

    /**
     * @var integer
     *
     * @ORM\Column(name="survey_group_sec1", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $surveyGroupSec1;

    /**
     * @var integer
     *
     * @ORM\Column(name="survey_group_sec2", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $surveyGroupSec2;


    /**
     * Get iid
     *
     * @return integer 
     */
    public function getIid()
    {
        return $this->iid;
    }

    /**
     * Set cId
     *
     * @param integer $cId
     * @return CSurveyQuestion
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
     * Set questionId
     *
     * @param integer $questionId
     * @return CSurveyQuestion
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
     * @return CSurveyQuestion
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
     * @return CSurveyQuestion
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
     * @return CSurveyQuestion
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
     * @return CSurveyQuestion
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
     * @return CSurveyQuestion
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
     * @return CSurveyQuestion
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
     * Set sharedQuestionId
     *
     * @param integer $sharedQuestionId
     * @return CSurveyQuestion
     */
    public function setSharedQuestionId($sharedQuestionId)
    {
        $this->sharedQuestionId = $sharedQuestionId;

        return $this;
    }

    /**
     * Get sharedQuestionId
     *
     * @return integer 
     */
    public function getSharedQuestionId()
    {
        return $this->sharedQuestionId;
    }

    /**
     * Set maxValue
     *
     * @param integer $maxValue
     * @return CSurveyQuestion
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

    /**
     * Set surveyGroupPri
     *
     * @param integer $surveyGroupPri
     * @return CSurveyQuestion
     */
    public function setSurveyGroupPri($surveyGroupPri)
    {
        $this->surveyGroupPri = $surveyGroupPri;

        return $this;
    }

    /**
     * Get surveyGroupPri
     *
     * @return integer 
     */
    public function getSurveyGroupPri()
    {
        return $this->surveyGroupPri;
    }

    /**
     * Set surveyGroupSec1
     *
     * @param integer $surveyGroupSec1
     * @return CSurveyQuestion
     */
    public function setSurveyGroupSec1($surveyGroupSec1)
    {
        $this->surveyGroupSec1 = $surveyGroupSec1;

        return $this;
    }

    /**
     * Get surveyGroupSec1
     *
     * @return integer 
     */
    public function getSurveyGroupSec1()
    {
        return $this->surveyGroupSec1;
    }

    /**
     * Set surveyGroupSec2
     *
     * @param integer $surveyGroupSec2
     * @return CSurveyQuestion
     */
    public function setSurveyGroupSec2($surveyGroupSec2)
    {
        $this->surveyGroupSec2 = $surveyGroupSec2;

        return $this;
    }

    /**
     * Get surveyGroupSec2
     *
     * @return integer 
     */
    public function getSurveyGroupSec2()
    {
        return $this->surveyGroupSec2;
    }
}
