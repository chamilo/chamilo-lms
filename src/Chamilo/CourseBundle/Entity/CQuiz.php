<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CQuiz
 *
 * @ORM\Table(
 *  name="c_quiz",
 *  indexes={
 *      @ORM\Index(name="course", columns={"c_id"}),
 *      @ORM\Index(name="session_id", columns={"session_id"})
 *  }
 * )
 * @ORM\Entity
 */
class CQuiz
{
    /**
     * @var integer
     *
     * @ORM\Column(name="iid", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    private $iid;

    /**
     * @var integer
     *
     * @ORM\Column(name="c_id", type="integer")
     */
    private $cId;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer", nullable=true)
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="title", type="string", length=255, nullable=false)
     */
    private $title;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="text", nullable=true)
     */
    private $description;

    /**
     * @var string
     *
     * @ORM\Column(name="sound", type="string", length=255, nullable=true)
     */
    private $sound;

    /**
     * @var boolean
     *
     * @ORM\Column(name="type", type="boolean", nullable=false)
     */
    private $type;

    /**
     * @var integer
     *
     * @ORM\Column(name="random", type="integer", nullable=false)
     */
    private $random;

    /**
     * @var boolean
     *
     * @ORM\Column(name="random_answers", type="boolean", nullable=false)
     */
    private $randomAnswers;

    /**
     * @var boolean
     *
     * @ORM\Column(name="active", type="boolean", nullable=false)
     */
    private $active;

    /**
     * @var integer
     *
     * @ORM\Column(name="results_disabled", type="integer", nullable=false)
     */
    private $resultsDisabled;

    /**
     * @var string
     *
     * @ORM\Column(name="access_condition", type="text", nullable=true)
     */
    private $accessCondition;

    /**
     * @var integer
     *
     * @ORM\Column(name="max_attempt", type="integer", nullable=false)
     */
    private $maxAttempt;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="start_time", type="datetime", nullable=true)
     */
    private $startTime;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="end_time", type="datetime", nullable=true)
     */
    private $endTime;

    /**
     * @var integer
     *
     * @ORM\Column(name="feedback_type", type="integer", nullable=false)
     */
    private $feedbackType;

    /**
     * @var integer
     *
     * @ORM\Column(name="expired_time", type="integer", nullable=false)
     */
    private $expiredTime;

    /**
     * @var integer
     *
     * @ORM\Column(name="session_id", type="integer", nullable=true)
     */
    private $sessionId;

    /**
     * @var integer
     *
     * @ORM\Column(name="propagate_neg", type="integer", nullable=false)
     */
    private $propagateNeg;

    /**
     * @var boolean
     * @ORm\Column(name="save_correct_answers", type="boolean", nullable=false)
     */
    private $saveCorrectAnswers;

    /**
     * @var integer
     *
     * @ORM\Column(name="review_answers", type="integer", nullable=false)
     */
    private $reviewAnswers;

    /**
     * @var integer
     *
     * @ORM\Column(name="random_by_category", type="integer", nullable=false)
     */
    private $randomByCategory;

    /**
     * @var string
     *
     * @ORM\Column(name="text_when_finished", type="text", nullable=true)
     */
    private $textWhenFinished;

    /**
     * @var integer
     *
     * @ORM\Column(name="display_category_name", type="integer", nullable=false)
     */
    private $displayCategoryName;

    /**
     * @var integer
     *
     * @ORM\Column(name="pass_percentage", type="integer", nullable=true)
     */
    private $passPercentage;

    /**
     * @var integer
     *
     * @ORM\Column(name="question_selection_type", type="integer", nullable=true)
     */
    private $questionSelectionType;

    /**
     * @var boolean
     *
     * @ORM\Column(name="hide_question_title", type="boolean", nullable=true)
     */
    private $hideQuestionTitle;

    /**
     * CQuiz constructor.
     */
    public function __construct()
    {
        $this->hideQuestionTitle = false;
    }

    /**
     * Set title
     *
     * @param string $title
     * @return CQuiz
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set description
     *
     * @param string $description
     * @return CQuiz
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set sound
     *
     * @param string $sound
     * @return CQuiz
     */
    public function setSound($sound)
    {
        $this->sound = $sound;

        return $this;
    }

    /**
     * Get sound
     *
     * @return string
     */
    public function getSound()
    {
        return $this->sound;
    }

    /**
     * Set type
     *
     * @param boolean $type
     * @return CQuiz
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type
     *
     * @return boolean
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set random
     *
     * @param integer $random
     * @return CQuiz
     */
    public function setRandom($random)
    {
        $this->random = $random;

        return $this;
    }

    /**
     * Get random
     *
     * @return integer
     */
    public function getRandom()
    {
        return $this->random;
    }

    /**
     * Set randomAnswers
     *
     * @param boolean $randomAnswers
     * @return CQuiz
     */
    public function setRandomAnswers($randomAnswers)
    {
        $this->randomAnswers = $randomAnswers;

        return $this;
    }

    /**
     * Get randomAnswers
     *
     * @return boolean
     */
    public function getRandomAnswers()
    {
        return $this->randomAnswers;
    }

    /**
     * Set active
     *
     * @param boolean $active
     * @return CQuiz
     */
    public function setActive($active)
    {
        $this->active = $active;

        return $this;
    }

    /**
     * Get active
     *
     * @return boolean
     */
    public function getActive()
    {
        return $this->active;
    }

    /**
     * Set resultsDisabled
     *
     * @param integer $resultsDisabled
     * @return CQuiz
     */
    public function setResultsDisabled($resultsDisabled)
    {
        $this->resultsDisabled = $resultsDisabled;

        return $this;
    }

    /**
     * Get resultsDisabled
     *
     * @return integer
     */
    public function getResultsDisabled()
    {
        return $this->resultsDisabled;
    }

    /**
     * Set accessCondition
     *
     * @param string $accessCondition
     * @return CQuiz
     */
    public function setAccessCondition($accessCondition)
    {
        $this->accessCondition = $accessCondition;

        return $this;
    }

    /**
     * Get accessCondition
     *
     * @return string
     */
    public function getAccessCondition()
    {
        return $this->accessCondition;
    }

    /**
     * Set maxAttempt
     *
     * @param integer $maxAttempt
     * @return CQuiz
     */
    public function setMaxAttempt($maxAttempt)
    {
        $this->maxAttempt = $maxAttempt;

        return $this;
    }

    /**
     * Get maxAttempt
     *
     * @return integer
     */
    public function getMaxAttempt()
    {
        return $this->maxAttempt;
    }

    /**
     * Set startTime
     *
     * @param \DateTime $startTime
     * @return CQuiz
     */
    public function setStartTime($startTime)
    {
        $this->startTime = $startTime;

        return $this;
    }

    /**
     * Get startTime
     *
     * @return \DateTime
     */
    public function getStartTime()
    {
        return $this->startTime;
    }

    /**
     * Set endTime
     *
     * @param \DateTime $endTime
     * @return CQuiz
     */
    public function setEndTime($endTime)
    {
        $this->endTime = $endTime;

        return $this;
    }

    /**
     * Get endTime
     *
     * @return \DateTime
     */
    public function getEndTime()
    {
        return $this->endTime;
    }

    /**
     * Set feedbackType
     *
     * @param integer $feedbackType
     * @return CQuiz
     */
    public function setFeedbackType($feedbackType)
    {
        $this->feedbackType = $feedbackType;

        return $this;
    }

    /**
     * Get feedbackType
     *
     * @return integer
     */
    public function getFeedbackType()
    {
        return $this->feedbackType;
    }

    /**
     * Set expiredTime
     *
     * @param integer $expiredTime
     * @return CQuiz
     */
    public function setExpiredTime($expiredTime)
    {
        $this->expiredTime = $expiredTime;

        return $this;
    }

    /**
     * Get expiredTime
     *
     * @return integer
     */
    public function getExpiredTime()
    {
        return $this->expiredTime;
    }

    /**
     * Set sessionId
     *
     * @param integer $sessionId
     * @return CQuiz
     */
    public function setSessionId($sessionId)
    {
        $this->sessionId = $sessionId;

        return $this;
    }

    /**
     * Get sessionId
     *
     * @return integer
     */
    public function getSessionId()
    {
        return $this->sessionId;
    }

    /**
     * Set propagateNeg
     *
     * @param integer $propagateNeg
     * @return CQuiz
     */
    public function setPropagateNeg($propagateNeg)
    {
        $this->propagateNeg = $propagateNeg;

        return $this;
    }

    /**
     * Get propagateNeg
     *
     * @return integer
     */
    public function getPropagateNeg()
    {
        return $this->propagateNeg;
    }

    /**
     * @param $saveCorrectAnswers boolean
     * @return CQuiz
     */
    public function setSaveCorrectAnswers($saveCorrectAnswers)
    {
        $this->saveCorrectAnswers = $saveCorrectAnswers;

        return $this;
    }

    /**
     * @return boolean
     */
    public function getSaveCorrectAnswers()
    {
        return $this->saveCorrectAnswers;
    }

    /**
     * Set reviewAnswers
     *
     * @param integer $reviewAnswers
     * @return CQuiz
     */
    public function setReviewAnswers($reviewAnswers)
    {
        $this->reviewAnswers = $reviewAnswers;

        return $this;
    }

    /**
     * Get reviewAnswers
     *
     * @return integer
     */
    public function getReviewAnswers()
    {
        return $this->reviewAnswers;
    }

    /**
     * Set randomByCategory
     *
     * @param integer $randomByCategory
     * @return CQuiz
     */
    public function setRandomByCategory($randomByCategory)
    {
        $this->randomByCategory = $randomByCategory;

        return $this;
    }

    /**
     * Get randomByCategory
     *
     * @return integer
     */
    public function getRandomByCategory()
    {
        return $this->randomByCategory;
    }

    /**
     * Set textWhenFinished
     *
     * @param string $textWhenFinished
     * @return CQuiz
     */
    public function setTextWhenFinished($textWhenFinished)
    {
        $this->textWhenFinished = $textWhenFinished;

        return $this;
    }

    /**
     * Get textWhenFinished
     *
     * @return string
     */
    public function getTextWhenFinished()
    {
        return $this->textWhenFinished;
    }

    /**
     * Set displayCategoryName
     *
     * @param integer $displayCategoryName
     * @return CQuiz
     */
    public function setDisplayCategoryName($displayCategoryName)
    {
        $this->displayCategoryName = $displayCategoryName;

        return $this;
    }

    /**
     * Get displayCategoryName
     *
     * @return integer
     */
    public function getDisplayCategoryName()
    {
        return $this->displayCategoryName;
    }

    /**
     * Set passPercentage
     *
     * @param integer $passPercentage
     * @return CQuiz
     */
    public function setPassPercentage($passPercentage)
    {
        $this->passPercentage = $passPercentage;

        return $this;
    }

    /**
     * Get passPercentage
     *
     * @return integer
     */
    public function getPassPercentage()
    {
        return $this->passPercentage;
    }

    /**
     * Set id
     *
     * @param integer $id
     * @return CQuiz
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set cId
     *
     * @param integer $cId
     * @return CQuiz
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
     * @return int
     */
    public function getQuestionSelectionType()
    {
        return $this->questionSelectionType;
    }

    /**
     * @param int $questionSelectionType
     * @return CQuiz
     */
    public function setQuestionSelectionType($questionSelectionType)
    {
        $this->questionSelectionType = $questionSelectionType;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isHideQuestionTitle()
    {
        return $this->hideQuestionTitle;
    }

    /**
     * @param boolean $hideQuestionTitle
     * @return CQuiz
     */
    public function setHideQuestionTitle($hideQuestionTitle)
    {
        $this->hideQuestionTitle = $hideQuestionTitle;

        return $this;
    }




}
