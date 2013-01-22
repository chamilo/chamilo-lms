<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * EntityCQuiz
 *
 * @Table(name="c_quiz")
 * @Entity
 */
class EntityCQuiz
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
     * @Column(name="id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     * @Id
     * @GeneratedValue(strategy="NONE")
     */
    private $id;

    /**
     * @var string
     *
     * @Column(name="title", type="string", length=255, precision=0, scale=0, nullable=false, unique=false)
     */
    private $title;

    /**
     * @var string
     *
     * @Column(name="description", type="text", precision=0, scale=0, nullable=true, unique=false)
     */
    private $description;

    /**
     * @var string
     *
     * @Column(name="sound", type="string", length=255, precision=0, scale=0, nullable=true, unique=false)
     */
    private $sound;

    /**
     * @var boolean
     *
     * @Column(name="type", type="boolean", precision=0, scale=0, nullable=false, unique=false)
     */
    private $type;

    /**
     * @var integer
     *
     * @Column(name="random", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $random;

    /**
     * @var boolean
     *
     * @Column(name="random_answers", type="boolean", precision=0, scale=0, nullable=false, unique=false)
     */
    private $randomAnswers;

    /**
     * @var boolean
     *
     * @Column(name="active", type="boolean", precision=0, scale=0, nullable=false, unique=false)
     */
    private $active;

    /**
     * @var integer
     *
     * @Column(name="results_disabled", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $resultsDisabled;

    /**
     * @var string
     *
     * @Column(name="access_condition", type="text", precision=0, scale=0, nullable=true, unique=false)
     */
    private $accessCondition;

    /**
     * @var integer
     *
     * @Column(name="max_attempt", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $maxAttempt;

    /**
     * @var \DateTime
     *
     * @Column(name="start_time", type="datetime", precision=0, scale=0, nullable=false, unique=false)
     */
    private $startTime;

    /**
     * @var \DateTime
     *
     * @Column(name="end_time", type="datetime", precision=0, scale=0, nullable=false, unique=false)
     */
    private $endTime;

    /**
     * @var integer
     *
     * @Column(name="feedback_type", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $feedbackType;

    /**
     * @var integer
     *
     * @Column(name="expired_time", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $expiredTime;

    /**
     * @var integer
     *
     * @Column(name="session_id", type="integer", precision=0, scale=0, nullable=true, unique=false)
     */
    private $sessionId;

    /**
     * @var integer
     *
     * @Column(name="propagate_neg", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $propagateNeg;

    /**
     * @var integer
     *
     * @Column(name="review_answers", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $reviewAnswers;

    /**
     * @var integer
     *
     * @Column(name="random_by_category", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $randomByCategory;

    /**
     * @var string
     *
     * @Column(name="text_when_finished", type="text", precision=0, scale=0, nullable=true, unique=false)
     */
    private $textWhenFinished;

    /**
     * @var integer
     *
     * @Column(name="display_category_name", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $displayCategoryName;

    /**
     * @var integer
     *
     * @Column(name="pass_percentage", type="integer", precision=0, scale=0, nullable=true, unique=false)
     */
    private $passPercentage;


    /**
     * Set cId
     *
     * @param integer $cId
     * @return EntityCQuiz
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
     * Set id
     *
     * @param integer $id
     * @return EntityCQuiz
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
     * Set title
     *
     * @param string $title
     * @return EntityCQuiz
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
     * @return EntityCQuiz
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
     * @return EntityCQuiz
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
     * @return EntityCQuiz
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
     * @return EntityCQuiz
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
     * @return EntityCQuiz
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
     * @return EntityCQuiz
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
     * @return EntityCQuiz
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
     * @return EntityCQuiz
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
     * @return EntityCQuiz
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
     * @return EntityCQuiz
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
     * @return EntityCQuiz
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
     * @return EntityCQuiz
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
     * @return EntityCQuiz
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
     * @return EntityCQuiz
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
     * @return EntityCQuiz
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
     * Set reviewAnswers
     *
     * @param integer $reviewAnswers
     * @return EntityCQuiz
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
     * @return EntityCQuiz
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
     * @return EntityCQuiz
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
     * @return EntityCQuiz
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
     * @return EntityCQuiz
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
}
