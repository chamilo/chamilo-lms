<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * EntityTrackEExercices
 *
 * @Table(name="track_e_exercices")
 * @Entity
 */
class EntityTrackEExercices
{
    /**
     * @var integer
     *
     * @Column(name="exe_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     * @Id
     * @GeneratedValue(strategy="IDENTITY")
     */
    private $exeId;

    /**
     * @var integer
     *
     * @Column(name="exe_user_id", type="integer", precision=0, scale=0, nullable=true, unique=false)
     */
    private $exeUserId;

    /**
     * @var \DateTime
     *
     * @Column(name="exe_date", type="datetime", precision=0, scale=0, nullable=false, unique=false)
     */
    private $exeDate;

    /**
     * @var string
     *
     * @Column(name="exe_cours_id", type="string", length=40, precision=0, scale=0, nullable=false, unique=false)
     */
    private $exeCoursId;

    /**
     * @var integer
     *
     * @Column(name="exe_exo_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $exeExoId;

    /**
     * @var float
     *
     * @Column(name="exe_result", type="float", precision=0, scale=0, nullable=false, unique=false)
     */
    private $exeResult;

    /**
     * @var float
     *
     * @Column(name="exe_weighting", type="float", precision=0, scale=0, nullable=false, unique=false)
     */
    private $exeWeighting;

    /**
     * @var string
     *
     * @Column(name="status", type="string", length=20, precision=0, scale=0, nullable=false, unique=false)
     */
    private $status;

    /**
     * @var string
     *
     * @Column(name="data_tracking", type="text", precision=0, scale=0, nullable=false, unique=false)
     */
    private $dataTracking;

    /**
     * @var \DateTime
     *
     * @Column(name="start_date", type="datetime", precision=0, scale=0, nullable=false, unique=false)
     */
    private $startDate;

    /**
     * @var integer
     *
     * @Column(name="steps_counter", type="smallint", precision=0, scale=0, nullable=false, unique=false)
     */
    private $stepsCounter;

    /**
     * @var integer
     *
     * @Column(name="session_id", type="smallint", precision=0, scale=0, nullable=false, unique=false)
     */
    private $sessionId;

    /**
     * @var integer
     *
     * @Column(name="orig_lp_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $origLpId;

    /**
     * @var integer
     *
     * @Column(name="orig_lp_item_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $origLpItemId;

    /**
     * @var integer
     *
     * @Column(name="exe_duration", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $exeDuration;

    /**
     * @var \DateTime
     *
     * @Column(name="expired_time_control", type="datetime", precision=0, scale=0, nullable=false, unique=false)
     */
    private $expiredTimeControl;

    /**
     * @var integer
     *
     * @Column(name="orig_lp_item_view_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $origLpItemViewId;

    /**
     * @var string
     *
     * @Column(name="questions_to_check", type="text", precision=0, scale=0, nullable=false, unique=false)
     */
    private $questionsToCheck;


    /**
     * Get exeId
     *
     * @return integer 
     */
    public function getExeId()
    {
        return $this->exeId;
    }

    /**
     * Set exeUserId
     *
     * @param integer $exeUserId
     * @return EntityTrackEExercices
     */
    public function setExeUserId($exeUserId)
    {
        $this->exeUserId = $exeUserId;

        return $this;
    }

    /**
     * Get exeUserId
     *
     * @return integer 
     */
    public function getExeUserId()
    {
        return $this->exeUserId;
    }

    /**
     * Set exeDate
     *
     * @param \DateTime $exeDate
     * @return EntityTrackEExercices
     */
    public function setExeDate($exeDate)
    {
        $this->exeDate = $exeDate;

        return $this;
    }

    /**
     * Get exeDate
     *
     * @return \DateTime 
     */
    public function getExeDate()
    {
        return $this->exeDate;
    }

    /**
     * Set exeCoursId
     *
     * @param string $exeCoursId
     * @return EntityTrackEExercices
     */
    public function setExeCoursId($exeCoursId)
    {
        $this->exeCoursId = $exeCoursId;

        return $this;
    }

    /**
     * Get exeCoursId
     *
     * @return string 
     */
    public function getExeCoursId()
    {
        return $this->exeCoursId;
    }

    /**
     * Set exeExoId
     *
     * @param integer $exeExoId
     * @return EntityTrackEExercices
     */
    public function setExeExoId($exeExoId)
    {
        $this->exeExoId = $exeExoId;

        return $this;
    }

    /**
     * Get exeExoId
     *
     * @return integer 
     */
    public function getExeExoId()
    {
        return $this->exeExoId;
    }

    /**
     * Set exeResult
     *
     * @param float $exeResult
     * @return EntityTrackEExercices
     */
    public function setExeResult($exeResult)
    {
        $this->exeResult = $exeResult;

        return $this;
    }

    /**
     * Get exeResult
     *
     * @return float 
     */
    public function getExeResult()
    {
        return $this->exeResult;
    }

    /**
     * Set exeWeighting
     *
     * @param float $exeWeighting
     * @return EntityTrackEExercices
     */
    public function setExeWeighting($exeWeighting)
    {
        $this->exeWeighting = $exeWeighting;

        return $this;
    }

    /**
     * Get exeWeighting
     *
     * @return float 
     */
    public function getExeWeighting()
    {
        return $this->exeWeighting;
    }

    /**
     * Set status
     *
     * @param string $status
     * @return EntityTrackEExercices
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return string 
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set dataTracking
     *
     * @param string $dataTracking
     * @return EntityTrackEExercices
     */
    public function setDataTracking($dataTracking)
    {
        $this->dataTracking = $dataTracking;

        return $this;
    }

    /**
     * Get dataTracking
     *
     * @return string 
     */
    public function getDataTracking()
    {
        return $this->dataTracking;
    }

    /**
     * Set startDate
     *
     * @param \DateTime $startDate
     * @return EntityTrackEExercices
     */
    public function setStartDate($startDate)
    {
        $this->startDate = $startDate;

        return $this;
    }

    /**
     * Get startDate
     *
     * @return \DateTime 
     */
    public function getStartDate()
    {
        return $this->startDate;
    }

    /**
     * Set stepsCounter
     *
     * @param integer $stepsCounter
     * @return EntityTrackEExercices
     */
    public function setStepsCounter($stepsCounter)
    {
        $this->stepsCounter = $stepsCounter;

        return $this;
    }

    /**
     * Get stepsCounter
     *
     * @return integer 
     */
    public function getStepsCounter()
    {
        return $this->stepsCounter;
    }

    /**
     * Set sessionId
     *
     * @param integer $sessionId
     * @return EntityTrackEExercices
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
     * Set origLpId
     *
     * @param integer $origLpId
     * @return EntityTrackEExercices
     */
    public function setOrigLpId($origLpId)
    {
        $this->origLpId = $origLpId;

        return $this;
    }

    /**
     * Get origLpId
     *
     * @return integer 
     */
    public function getOrigLpId()
    {
        return $this->origLpId;
    }

    /**
     * Set origLpItemId
     *
     * @param integer $origLpItemId
     * @return EntityTrackEExercices
     */
    public function setOrigLpItemId($origLpItemId)
    {
        $this->origLpItemId = $origLpItemId;

        return $this;
    }

    /**
     * Get origLpItemId
     *
     * @return integer 
     */
    public function getOrigLpItemId()
    {
        return $this->origLpItemId;
    }

    /**
     * Set exeDuration
     *
     * @param integer $exeDuration
     * @return EntityTrackEExercices
     */
    public function setExeDuration($exeDuration)
    {
        $this->exeDuration = $exeDuration;

        return $this;
    }

    /**
     * Get exeDuration
     *
     * @return integer 
     */
    public function getExeDuration()
    {
        return $this->exeDuration;
    }

    /**
     * Set expiredTimeControl
     *
     * @param \DateTime $expiredTimeControl
     * @return EntityTrackEExercices
     */
    public function setExpiredTimeControl($expiredTimeControl)
    {
        $this->expiredTimeControl = $expiredTimeControl;

        return $this;
    }

    /**
     * Get expiredTimeControl
     *
     * @return \DateTime 
     */
    public function getExpiredTimeControl()
    {
        return $this->expiredTimeControl;
    }

    /**
     * Set origLpItemViewId
     *
     * @param integer $origLpItemViewId
     * @return EntityTrackEExercices
     */
    public function setOrigLpItemViewId($origLpItemViewId)
    {
        $this->origLpItemViewId = $origLpItemViewId;

        return $this;
    }

    /**
     * Get origLpItemViewId
     *
     * @return integer 
     */
    public function getOrigLpItemViewId()
    {
        return $this->origLpItemViewId;
    }

    /**
     * Set questionsToCheck
     *
     * @param string $questionsToCheck
     * @return EntityTrackEExercices
     */
    public function setQuestionsToCheck($questionsToCheck)
    {
        $this->questionsToCheck = $questionsToCheck;

        return $this;
    }

    /**
     * Get questionsToCheck
     *
     * @return string 
     */
    public function getQuestionsToCheck()
    {
        return $this->questionsToCheck;
    }
}
