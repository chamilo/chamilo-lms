<?php

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * TrackEExercices
 *
 * @ORM\Table(name="track_e_exercices")
 * @ORM\Entity
 */
class TrackEExercices
{
    /**
     * @var integer
     *
     * @ORM\Column(name="exe_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $exeId;

    /**
     * @var integer
     *
     * @ORM\Column(name="exe_user_id", type="integer", precision=0, scale=0, nullable=true, unique=false)
     */
    private $exeUserId;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="exe_date", type="datetime", precision=0, scale=0, nullable=false, unique=false)
     */
    private $exeDate;

    /**
     * @var integer
     *
     * @ORM\Column(name="exe_exo_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $exeExoId;

    /**
     * @var float
     *
     * @ORM\Column(name="exe_result", type="float", precision=0, scale=0, nullable=false, unique=false)
     */
    private $exeResult;

    /**
     * @var float
     *
     * @ORM\Column(name="exe_weighting", type="float", precision=0, scale=0, nullable=false, unique=false)
     */
    private $exeWeighting;

    /**
     * @var integer
     *
     * @ORM\Column(name="c_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $cId;

    /**
     * @var string
     *
     * @ORM\Column(name="status", type="string", length=20, precision=0, scale=0, nullable=false, unique=false)
     */
    private $status;

    /**
     * @var string
     *
     * @ORM\Column(name="data_tracking", type="text", precision=0, scale=0, nullable=false, unique=false)
     */
    private $dataTracking;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="start_date", type="datetime", precision=0, scale=0, nullable=false, unique=false)
     */
    private $startDate;

    /**
     * @var integer
     *
     * @ORM\Column(name="steps_counter", type="smallint", precision=0, scale=0, nullable=false, unique=false)
     */
    private $stepsCounter;

    /**
     * @var integer
     *
     * @ORM\Column(name="session_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $sessionId;

    /**
     * @var integer
     *
     * @ORM\Column(name="orig_lp_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $origLpId;

    /**
     * @var integer
     *
     * @ORM\Column(name="orig_lp_item_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $origLpItemId;

    /**
     * @var integer
     *
     * @ORM\Column(name="exe_duration", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $exeDuration;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="expired_time_control", type="datetime", precision=0, scale=0, nullable=false, unique=false)
     */
    private $expiredTimeControl;

    /**
     * @var integer
     *
     * @ORM\Column(name="orig_lp_item_view_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $origLpItemViewId;

    /**
     * @var string
     *
     * @ORM\Column(name="questions_to_check", type="text", precision=0, scale=0, nullable=false, unique=false)
     */
    private $questionsToCheck;

     /**
     * @var float
     *
     * @ORM\Column(name="jury_score", type="float", precision=0, scale=0, nullable=false, unique=false)
     */
    private $juryScore;

    /**
     * @var integer
     *
     * @ORM\Column(name="jury_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $juryId;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CourseBundle\Entity\CQuiz")
     * @ORM\JoinColumn(name="exe_exo_id", referencedColumnName="iid")
     */
    private $exercise;

    /**
     * @ORM\ManyToOne(targetEntity="Jury", inversedBy="exerciseAttempts")
     * @ORM\JoinColumn(name="exe_exo_id", referencedColumnName="exercise_id")
     */
    private $attempt;

     /**
     * @ORM\ManyToOne(targetEntity="Application\Sonata\UserBundle\Entity\User")
     * @ORM\JoinColumn(name="exe_user_id", referencedColumnName="id")
     */
    private $user;

     /**
     * @ORM\OneToMany(targetEntity="TrackAttemptJury", mappedBy="attempt")
     **/
    private $juryAttempts;

    public function getAttempt()
    {
        return $this->attempt;
    }

    public function getUser()
    {
        return $this->user;
    }

    public function getExercise()
    {
        return $this->exercise;
    }

    /**
     * @return mixed
     */
    public function getJuryAttempts()
    {
        return $this->juryAttempts;
    }

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
     * @return TrackEExercices
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
     * @return TrackEExercices
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
     * Set exeExoId
     *
     * @param integer $exeExoId
     * @return TrackEExercices
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
     * @return TrackEExercices
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
     * @return TrackEExercices
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
     * Set cId
     *
     * @param integer $cId
     * @return TrackEExercices
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
     * Set status
     *
     * @param string $status
     * @return TrackEExercices
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
     * @return TrackEExercices
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
     * @return TrackEExercices
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
     * @return TrackEExercices
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
     * @return TrackEExercices
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
     * @return TrackEExercices
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
     * @return TrackEExercices
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
     * @return TrackEExercices
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
     * @return TrackEExercices
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
     * @return TrackEExercices
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
     * @return TrackEExercices
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

     /**
     * Get juryScore
     *
     * @return integer
     */
    public function getJuryScore()
    {
        return $this->juryScore;
    }

    /**
     * Set juryScore
     *
     * @param integer $juryScore
     * @return TrackEExercices
     */
    public function setJuryScore($juryScore)
    {
        $this->juryScore = $juryScore;

        return $this;
    }

    /**
     * Get juryId
     *
     * @return integer
     */
    public function getJuryId()
    {
        return $this->juryId;
    }

    /**
     * Set juryId
     *
     * @param integer $juryId
     * @return TrackEExercices
     */
    public function setJuryId($juryId)
    {
        $this->juryId = $juryId;

        return $this;
    }

    /**
     * Get juryMembers
     *
     * @return integer
     */
    public function getJuryMembers()
    {
        return $this->juryMembers;
    }

    /**
     * Set juryMembers
     *
     * @param JuryMembers $juryMembers
     * @return TrackEExercices
     */
    public function setJuryMembers(JuryMembers $juryMembers)
    {
        $this->juryMembers = $juryMembers;

        return $this;
    }
}
