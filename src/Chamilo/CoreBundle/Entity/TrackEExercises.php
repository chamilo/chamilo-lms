<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * TrackEExercises
 *
 * @ORM\Table(name="track_e_exercises", indexes={@ORM\Index(name="idx_tee_user_id", columns={"exe_user_id"}), @ORM\Index(name="idx_tee_c_id", columns={"c_id"}), @ORM\Index(name="session_id", columns={"session_id"})})
 * @ORM\Entity
 */
class TrackEExercises
{
    /**
     * @var integer
     *
     * @ORM\Column(name="exe_user_id", type="integer", nullable=true)
     */
    private $exeUserId;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="exe_date", type="datetime", nullable=false)
     */
    private $exeDate;

    /**
     * @var integer
     *
     * @ORM\Column(name="c_id", type="integer", nullable=false)
     */
    private $cId;

    /**
     * @var integer
     *
     * @ORM\Column(name="exe_exo_id", type="integer", nullable=false)
     */
    private $exeExoId;

    /**
     * @var float
     *
     * @ORM\Column(name="exe_result", type="float", precision=6, scale=2, nullable=false)
     */
    private $exeResult;

    /**
     * @var float
     *
     * @ORM\Column(name="exe_weighting", type="float", precision=6, scale=2, nullable=false)
     */
    private $exeWeighting;

    /**
     * @var string
     *
     * @ORM\Column(name="user_ip", type="string", length=39, nullable=false)
     */
    private $userIp;

    /**
     * @var string
     *
     * @ORM\Column(name="status", type="string", length=20, nullable=false)
     */
    private $status;

    /**
     * @var string
     *
     * @ORM\Column(name="data_tracking", type="text", nullable=false)
     */
    private $dataTracking;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="start_date", type="datetime", nullable=false)
     */
    private $startDate;

    /**
     * @var integer
     *
     * @ORM\Column(name="steps_counter", type="smallint", nullable=false)
     */
    private $stepsCounter;

    /**
     * @var integer
     *
     * @ORM\Column(name="session_id", type="smallint", nullable=false)
     */
    private $sessionId;

    /**
     * @var integer
     *
     * @ORM\Column(name="orig_lp_id", type="integer", nullable=false)
     */
    private $origLpId;

    /**
     * @var integer
     *
     * @ORM\Column(name="orig_lp_item_id", type="integer", nullable=false)
     */
    private $origLpItemId;

    /**
     * @var integer
     *
     * @ORM\Column(name="exe_duration", type="integer", nullable=false)
     */
    private $exeDuration;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="expired_time_control", type="datetime", nullable=true)
     */
    private $expiredTimeControl;

    /**
     * @var integer
     *
     * @ORM\Column(name="orig_lp_item_view_id", type="integer", nullable=false)
     */
    private $origLpItemViewId;

    /**
     * @var string
     *
     * @ORM\Column(name="questions_to_check", type="text", nullable=false)
     */
    private $questionsToCheck;

    /**
     * @var integer
     *
     * @ORM\Column(name="exe_id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $exeId;



    /**
     * Set exeUserId
     *
     * @param integer $exeUserId
     * @return TrackEExercises
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
     * @return TrackEExercises
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
     * Set cId
     *
     * @param integer $cId
     * @return TrackEExercises
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
     * Set exeExoId
     *
     * @param integer $exeExoId
     * @return TrackEExercises
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
     * @return TrackEExercises
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
     * @return TrackEExercises
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
     * Set userIp
     *
     * @param string $userIp
     * @return TrackEExercises
     */
    public function setUserIp($userIp)
    {
        $this->userIp = $userIp;

        return $this;
    }

    /**
     * Get userIp
     *
     * @return string
     */
    public function getUserIp()
    {
        return $this->userIp;
    }

    /**
     * Set status
     *
     * @param string $status
     * @return TrackEExercises
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
     * @return TrackEExercises
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
     * @return TrackEExercises
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
     * @return TrackEExercises
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
     * @return TrackEExercises
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
     * @return TrackEExercises
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
     * @return TrackEExercises
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
     * @return TrackEExercises
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
     * @return TrackEExercises
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
     * @return TrackEExercises
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
     * @return TrackEExercises
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
     * Get exeId
     *
     * @return integer
     */
    public function getExeId()
    {
        return $this->exeId;
    }
}
