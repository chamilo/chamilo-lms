<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CQuiz.
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
     * @deprecated Now using iid
     * @ORM\Column(name="c_id", type="integer")
     */
    protected $cId;

    /**
     * @var int
     *
     * @deprecated Now using iid
     * @ORM\Column(name="id", type="integer", nullable=true)
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="title", type="string", length=255, nullable=false)
     */
    protected $title;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="text", nullable=true)
     */
    protected $description;

    /**
     * @var string
     *
     * @ORM\Column(name="sound", type="string", length=255, nullable=true)
     */
    protected $sound;

    /**
     * @var bool
     *
     * @ORM\Column(name="type", type="boolean", nullable=false)
     */
    protected $type;

    /**
     * @var int
     *
     * @ORM\Column(name="random", type="integer", nullable=false)
     */
    protected $random;

    /**
     * @var bool
     *
     * @ORM\Column(name="random_answers", type="boolean", nullable=false)
     */
    protected $randomAnswers;

    /**
     * @var bool
     *
     * @ORM\Column(name="active", type="boolean", nullable=false)
     */
    protected $active;

    /**
     * @var int
     *
     * @ORM\Column(name="results_disabled", type="integer", nullable=false)
     */
    protected $resultsDisabled;

    /**
     * @var string
     *
     * @ORM\Column(name="access_condition", type="text", nullable=true)
     */
    protected $accessCondition;

    /**
     * @var int
     *
     * @ORM\Column(name="max_attempt", type="integer", nullable=false)
     */
    protected $maxAttempt;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="start_time", type="datetime", nullable=true)
     */
    protected $startTime;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="end_time", type="datetime", nullable=true)
     */
    protected $endTime;

    /**
     * @var int
     *
     * @ORM\Column(name="feedback_type", type="integer", nullable=false)
     */
    protected $feedbackType;

    /**
     * @var int
     *
     * @ORM\Column(name="expired_time", type="integer", nullable=false)
     */
    protected $expiredTime;

    /**
     * @var int
     *
     * @ORM\Column(name="session_id", type="integer", nullable=true)
     */
    protected $sessionId;

    /**
     * @var int
     *
     * @ORM\Column(name="propagate_neg", type="integer", nullable=false)
     */
    protected $propagateNeg;

    /**
     * @var bool
     * @ORm\Column(name="save_correct_answers", type="boolean", nullable=false)
     */
    protected $saveCorrectAnswers;

    /**
     * @var int
     *
     * @ORM\Column(name="review_answers", type="integer", nullable=false)
     */
    protected $reviewAnswers;

    /**
     * @var int
     *
     * @ORM\Column(name="random_by_category", type="integer", nullable=false)
     */
    protected $randomByCategory;

    /**
     * @var string
     *
     * @ORM\Column(name="text_when_finished", type="text", nullable=true)
     */
    protected $textWhenFinished;

    /**
     * @var int
     *
     * @ORM\Column(name="display_category_name", type="integer", nullable=false)
     */
    protected $displayCategoryName;

    /**
     * @var int
     *
     * @ORM\Column(name="pass_percentage", type="integer", nullable=true)
     */
    protected $passPercentage;

    /**
     * @var int
     *
     * @ORM\Column(name="question_selection_type", type="integer", nullable=true)
     */
    protected $questionSelectionType;

    /**
     * @var bool
     *
     * @ORM\Column(name="hide_question_title", type="boolean", nullable=true)
     */
    protected $hideQuestionTitle;

    /**
     * @var int
     *
     * ORM\Column(name="exercise_category_id", type="integer", nullable=true)
     */
    protected $exerciseCategoryId;

    /**
     * CQuiz constructor.
     */
    public function __construct()
    {
        $this->hideQuestionTitle = false;
    }

    /**
     * Set title.
     *
     * @param string $title
     *
     * @return CQuiz
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title.
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set description.
     *
     * @param string $description
     *
     * @return CQuiz
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description.
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set sound.
     *
     * @param string $sound
     *
     * @return CQuiz
     */
    public function setSound($sound)
    {
        $this->sound = $sound;

        return $this;
    }

    /**
     * Get sound.
     *
     * @return string
     */
    public function getSound()
    {
        return $this->sound;
    }

    /**
     * Set type.
     *
     * @param bool $type
     *
     * @return CQuiz
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type.
     *
     * @return bool
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set random.
     *
     * @param int $random
     *
     * @return CQuiz
     */
    public function setRandom($random)
    {
        $this->random = $random;

        return $this;
    }

    /**
     * Get random.
     *
     * @return int
     */
    public function getRandom()
    {
        return $this->random;
    }

    /**
     * Set randomAnswers.
     *
     * @param bool $randomAnswers
     *
     * @return CQuiz
     */
    public function setRandomAnswers($randomAnswers)
    {
        $this->randomAnswers = $randomAnswers;

        return $this;
    }

    /**
     * Get randomAnswers.
     *
     * @return bool
     */
    public function getRandomAnswers()
    {
        return $this->randomAnswers;
    }

    /**
     * Set active.
     *
     * @param bool $active
     *
     * @return CQuiz
     */
    public function setActive($active)
    {
        $this->active = $active;

        return $this;
    }

    /**
     * Get active.
     *
     * @return bool
     */
    public function getActive()
    {
        return $this->active;
    }

    /**
     * Set resultsDisabled.
     *
     * @param int $resultsDisabled
     *
     * @return CQuiz
     */
    public function setResultsDisabled($resultsDisabled)
    {
        $this->resultsDisabled = $resultsDisabled;

        return $this;
    }

    /**
     * Get resultsDisabled.
     *
     * @return int
     */
    public function getResultsDisabled()
    {
        return $this->resultsDisabled;
    }

    /**
     * Set accessCondition.
     *
     * @param string $accessCondition
     *
     * @return CQuiz
     */
    public function setAccessCondition($accessCondition)
    {
        $this->accessCondition = $accessCondition;

        return $this;
    }

    /**
     * Get accessCondition.
     *
     * @return string
     */
    public function getAccessCondition()
    {
        return $this->accessCondition;
    }

    /**
     * Set maxAttempt.
     *
     * @param int $maxAttempt
     *
     * @return CQuiz
     */
    public function setMaxAttempt($maxAttempt)
    {
        $this->maxAttempt = $maxAttempt;

        return $this;
    }

    /**
     * Get maxAttempt.
     *
     * @return int
     */
    public function getMaxAttempt()
    {
        return $this->maxAttempt;
    }

    /**
     * Set startTime.
     *
     * @param \DateTime $startTime
     *
     * @return CQuiz
     */
    public function setStartTime($startTime)
    {
        $this->startTime = $startTime;

        return $this;
    }

    /**
     * Get startTime.
     *
     * @return \DateTime
     */
    public function getStartTime()
    {
        return $this->startTime;
    }

    /**
     * Set endTime.
     *
     * @param \DateTime $endTime
     *
     * @return CQuiz
     */
    public function setEndTime($endTime)
    {
        $this->endTime = $endTime;

        return $this;
    }

    /**
     * Get endTime.
     *
     * @return \DateTime
     */
    public function getEndTime()
    {
        return $this->endTime;
    }

    /**
     * Set feedbackType.
     *
     * @param int $feedbackType
     *
     * @return CQuiz
     */
    public function setFeedbackType($feedbackType)
    {
        $this->feedbackType = $feedbackType;

        return $this;
    }

    /**
     * Get feedbackType.
     *
     * @return int
     */
    public function getFeedbackType()
    {
        return $this->feedbackType;
    }

    /**
     * Set expiredTime.
     *
     * @param int $expiredTime
     *
     * @return CQuiz
     */
    public function setExpiredTime($expiredTime)
    {
        $this->expiredTime = $expiredTime;

        return $this;
    }

    /**
     * Get expiredTime.
     *
     * @return int
     */
    public function getExpiredTime()
    {
        return $this->expiredTime;
    }

    /**
     * Set sessionId.
     *
     * @param int $sessionId
     *
     * @return CQuiz
     */
    public function setSessionId($sessionId)
    {
        $this->sessionId = $sessionId;

        return $this;
    }

    /**
     * Get sessionId.
     *
     * @return int
     */
    public function getSessionId()
    {
        return $this->sessionId;
    }

    /**
     * Set propagateNeg.
     *
     * @param int $propagateNeg
     *
     * @return CQuiz
     */
    public function setPropagateNeg($propagateNeg)
    {
        $this->propagateNeg = $propagateNeg;

        return $this;
    }

    /**
     * Get propagateNeg.
     *
     * @return int
     */
    public function getPropagateNeg()
    {
        return $this->propagateNeg;
    }

    /**
     * @param $saveCorrectAnswers boolean
     *
     * @return CQuiz
     */
    public function setSaveCorrectAnswers($saveCorrectAnswers)
    {
        $this->saveCorrectAnswers = $saveCorrectAnswers;

        return $this;
    }

    /**
     * @return bool
     */
    public function getSaveCorrectAnswers()
    {
        return $this->saveCorrectAnswers;
    }

    /**
     * Set reviewAnswers.
     *
     * @param int $reviewAnswers
     *
     * @return CQuiz
     */
    public function setReviewAnswers($reviewAnswers)
    {
        $this->reviewAnswers = $reviewAnswers;

        return $this;
    }

    /**
     * Get reviewAnswers.
     *
     * @return int
     */
    public function getReviewAnswers()
    {
        return $this->reviewAnswers;
    }

    /**
     * Set randomByCategory.
     *
     * @param int $randomByCategory
     *
     * @return CQuiz
     */
    public function setRandomByCategory($randomByCategory)
    {
        $this->randomByCategory = $randomByCategory;

        return $this;
    }

    /**
     * Get randomByCategory.
     *
     * @return int
     */
    public function getRandomByCategory()
    {
        return $this->randomByCategory;
    }

    /**
     * Set textWhenFinished.
     *
     * @param string $textWhenFinished
     *
     * @return CQuiz
     */
    public function setTextWhenFinished($textWhenFinished)
    {
        $this->textWhenFinished = $textWhenFinished;

        return $this;
    }

    /**
     * Get textWhenFinished.
     *
     * @return string
     */
    public function getTextWhenFinished()
    {
        return $this->textWhenFinished;
    }

    /**
     * Set displayCategoryName.
     *
     * @param int $displayCategoryName
     *
     * @return CQuiz
     */
    public function setDisplayCategoryName($displayCategoryName)
    {
        $this->displayCategoryName = $displayCategoryName;

        return $this;
    }

    /**
     * Get displayCategoryName.
     *
     * @return int
     */
    public function getDisplayCategoryName()
    {
        return $this->displayCategoryName;
    }

    /**
     * Set passPercentage.
     *
     * @param int $passPercentage
     *
     * @return CQuiz
     */
    public function setPassPercentage($passPercentage)
    {
        $this->passPercentage = $passPercentage;

        return $this;
    }

    /**
     * Get passPercentage.
     *
     * @return int
     */
    public function getPassPercentage()
    {
        return $this->passPercentage;
    }

    /**
     * Set id.
     *
     * @param int $iid
     *
     * @return CQuiz
     */
    public function setId($iid)
    {
        $this->iid = $iid;

        return $this;
    }

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->iid;
    }

    /**
     * Set cId.
     *
     * @param int $cId
     *
     * @return CQuiz
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
     * @return int
     */
    public function getQuestionSelectionType()
    {
        return $this->questionSelectionType;
    }

    /**
     * @param int $questionSelectionType
     *
     * @return CQuiz
     */
    public function setQuestionSelectionType($questionSelectionType)
    {
        $this->questionSelectionType = $questionSelectionType;

        return $this;
    }

    /**
     * @return bool
     */
    public function isHideQuestionTitle()
    {
        return $this->hideQuestionTitle;
    }

    /**
     * @param bool $hideQuestionTitle
     *
     * @return CQuiz
     */
    public function setHideQuestionTitle($hideQuestionTitle)
    {
        $this->hideQuestionTitle = $hideQuestionTitle;

        return $this;
    }
}
