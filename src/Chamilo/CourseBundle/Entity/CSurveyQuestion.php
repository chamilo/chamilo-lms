<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CSurveyQuestion.
 *
 * @ORM\Table(
 *  name="c_survey_question",
 *  indexes={
 *      @ORM\Index(name="course", columns={"c_id"})
 *  }
 * )
 * @ORM\Entity
 */
class CSurveyQuestion
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
     * @ORM\Column(name="question_id", type="integer")
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
     * @ORM\Column(name="survey_question", type="text", nullable=false)
     */
    protected $surveyQuestion;

    /**
     * @var string
     *
     * @ORM\Column(name="survey_question_comment", type="text", nullable=false)
     */
    protected $surveyQuestionComment;

    /**
     * @var string
     *
     * @ORM\Column(name="type", type="string", length=250, nullable=false)
     */
    protected $type;

    /**
     * @var string
     *
     * @ORM\Column(name="display", type="string", length=10, nullable=false)
     */
    protected $display;

    /**
     * @var int
     *
     * @ORM\Column(name="sort", type="integer", nullable=false)
     */
    protected $sort;

    /**
     * @var int
     *
     * @ORM\Column(name="shared_question_id", type="integer", nullable=true)
     */
    protected $sharedQuestionId;

    /**
     * @var int
     *
     * @ORM\Column(name="max_value", type="integer", nullable=true)
     */
    protected $maxValue;

    /**
     * @var int
     *
     * @ORM\Column(name="survey_group_pri", type="integer", nullable=false)
     */
    protected $surveyGroupPri;

    /**
     * @var int
     *
     * @ORM\Column(name="survey_group_sec1", type="integer", nullable=false)
     */
    protected $surveyGroupSec1;

    /**
     * @var int
     *
     * @ORM\Column(name="survey_group_sec2", type="integer", nullable=false)
     */
    protected $surveyGroupSec2;

    /**
     * Set surveyId.
     *
     * @param int $surveyId
     *
     * @return CSurveyQuestion
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
     * Set surveyQuestion.
     *
     * @param string $surveyQuestion
     *
     * @return CSurveyQuestion
     */
    public function setSurveyQuestion($surveyQuestion)
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
     * @param string $surveyQuestionComment
     *
     * @return CSurveyQuestion
     */
    public function setSurveyQuestionComment($surveyQuestionComment)
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
     * @param string $type
     *
     * @return CSurveyQuestion
     */
    public function setType($type)
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
     * @param string $display
     *
     * @return CSurveyQuestion
     */
    public function setDisplay($display)
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
     * @param int $sort
     *
     * @return CSurveyQuestion
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
     * Set sharedQuestionId.
     *
     * @param int $sharedQuestionId
     *
     * @return CSurveyQuestion
     */
    public function setSharedQuestionId($sharedQuestionId)
    {
        $this->sharedQuestionId = $sharedQuestionId;

        return $this;
    }

    /**
     * Get sharedQuestionId.
     *
     * @return int
     */
    public function getSharedQuestionId()
    {
        return $this->sharedQuestionId;
    }

    /**
     * Set maxValue.
     *
     * @param int $maxValue
     *
     * @return CSurveyQuestion
     */
    public function setMaxValue($maxValue)
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
     * Set surveyGroupPri.
     *
     * @param int $surveyGroupPri
     *
     * @return CSurveyQuestion
     */
    public function setSurveyGroupPri($surveyGroupPri)
    {
        $this->surveyGroupPri = $surveyGroupPri;

        return $this;
    }

    /**
     * Get surveyGroupPri.
     *
     * @return int
     */
    public function getSurveyGroupPri()
    {
        return $this->surveyGroupPri;
    }

    /**
     * Set surveyGroupSec1.
     *
     * @param int $surveyGroupSec1
     *
     * @return CSurveyQuestion
     */
    public function setSurveyGroupSec1($surveyGroupSec1)
    {
        $this->surveyGroupSec1 = $surveyGroupSec1;

        return $this;
    }

    /**
     * Get surveyGroupSec1.
     *
     * @return int
     */
    public function getSurveyGroupSec1()
    {
        return $this->surveyGroupSec1;
    }

    /**
     * Set surveyGroupSec2.
     *
     * @param int $surveyGroupSec2
     *
     * @return CSurveyQuestion
     */
    public function setSurveyGroupSec2($surveyGroupSec2)
    {
        $this->surveyGroupSec2 = $surveyGroupSec2;

        return $this;
    }

    /**
     * Get surveyGroupSec2.
     *
     * @return int
     */
    public function getSurveyGroupSec2()
    {
        return $this->surveyGroupSec2;
    }

    /**
     * Set questionId.
     *
     * @param int $questionId
     *
     * @return CSurveyQuestion
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
     * Set cId.
     *
     * @param int $cId
     *
     * @return CSurveyQuestion
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
}
