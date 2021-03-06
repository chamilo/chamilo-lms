<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * TrackEExercises.
 *
 * @ORM\Table(name="track_e_exercises", indexes={
 *     @ORM\Index(name="idx_tee_user_id", columns={"exe_user_id"}),
 *     @ORM\Index(name="idx_tee_c_id", columns={"c_id"}),
 *     @ORM\Index(name="session_id", columns={"session_id"})
 * })
 * @ORM\Entity
 */
class TrackEExercises
{
    /**
     * @ORM\Column(name="exe_id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected int $exeId;

    /**
     * @ORM\Column(name="exe_user_id", type="integer", nullable=false)
     */
    protected int $exeUserId;

    /**
     * @Assert\NotBlank()
     *
     * @ORM\Column(name="exe_date", type="datetime", nullable=false)
     */
    protected DateTime $exeDate;

    /**
     * @ORM\Column(name="c_id", type="integer", nullable=false)
     */
    protected int $cId;

    /**
     * @Assert\NotBlank()
     * @ORM\Column(name="exe_exo_id", type="integer", nullable=false)
     */
    protected int $exeExoId;

    /**
     * @ORM\Column(name="score", type="float", precision=6, scale=2, nullable=false)
     */
    protected float $score;

    /**
     * @ORM\Column(name="max_score", type="float", precision=6, scale=2, nullable=false)
     */
    protected float $maxScore;

    /**
     * @ORM\Column(name="user_ip", type="string", length=39, nullable=false)
     */
    protected string $userIp;

    /**
     * @ORM\Column(name="status", type="string", length=20, nullable=false)
     */
    protected string $status;

    /**
     * @ORM\Column(name="data_tracking", type="text", nullable=false)
     */
    protected string $dataTracking;

    /**
     * @ORM\Column(name="start_date", type="datetime", nullable=false)
     */
    protected DateTime $startDate;

    /**
     * @ORM\Column(name="steps_counter", type="smallint", nullable=false)
     */
    protected int $stepsCounter;

    /**
     * @ORM\Column(name="session_id", type="integer", nullable=false)
     */
    protected int $sessionId;

    /**
     * @ORM\Column(name="orig_lp_id", type="integer", nullable=false)
     */
    protected int $origLpId;

    /**
     * @ORM\Column(name="orig_lp_item_id", type="integer", nullable=false)
     */
    protected int $origLpItemId;

    /**
     * @ORM\Column(name="exe_duration", type="integer", nullable=false)
     */
    protected int $exeDuration;

    /**
     * @ORM\Column(name="expired_time_control", type="datetime", nullable=true)
     */
    protected ?DateTime $expiredTimeControl = null;

    /**
     * @ORM\Column(name="orig_lp_item_view_id", type="integer", nullable=false)
     */
    protected int $origLpItemViewId;

    /**
     * @ORM\Column(name="questions_to_check", type="text", nullable=false)
     */
    protected string $questionsToCheck;

    /**
     * @ORM\Column(name="blocked_categories", type="text", nullable=true)
     */
    protected ?string $blockedCategories;

    public function __construct()
    {
        $this->blockedCategories = '';
    }

    /**
     * Set exeUserId.
     *
     * @return TrackEExercises
     */
    public function setExeUserId(int $exeUserId)
    {
        $this->exeUserId = $exeUserId;

        return $this;
    }

    /**
     * Get exeUserId.
     *
     * @return int
     */
    public function getExeUserId()
    {
        return $this->exeUserId;
    }

    /**
     * Set exeDate.
     *
     * @return TrackEExercises
     */
    public function setExeDate(DateTime $exeDate)
    {
        $this->exeDate = $exeDate;

        return $this;
    }

    /**
     * Get exeDate.
     *
     * @return DateTime
     */
    public function getExeDate()
    {
        return $this->exeDate;
    }

    /**
     * Set cId.
     *
     * @return TrackEExercises
     */
    public function setCId(int $cId)
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
     * Set exeExoId.
     *
     * @return TrackEExercises
     */
    public function setExeExoId(int $exeExoId)
    {
        $this->exeExoId = $exeExoId;

        return $this;
    }

    public function getExeExoId(): int
    {
        return $this->exeExoId;
    }

    /**
     * Set userIp.
     *
     * @return TrackEExercises
     */
    public function setUserIp(string $userIp)
    {
        $this->userIp = $userIp;

        return $this;
    }

    /**
     * Get userIp.
     *
     * @return string
     */
    public function getUserIp()
    {
        return $this->userIp;
    }

    /**
     * Set status.
     *
     * @return TrackEExercises
     */
    public function setStatus(string $status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status.
     *
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set dataTracking.
     *
     * @return TrackEExercises
     */
    public function setDataTracking(string $dataTracking)
    {
        $this->dataTracking = $dataTracking;

        return $this;
    }

    /**
     * Get dataTracking.
     *
     * @return string
     */
    public function getDataTracking()
    {
        return $this->dataTracking;
    }

    /**
     * Set startDate.
     *
     * @return TrackEExercises
     */
    public function setStartDate(DateTime $startDate)
    {
        $this->startDate = $startDate;

        return $this;
    }

    /**
     * Get startDate.
     *
     * @return DateTime
     */
    public function getStartDate()
    {
        return $this->startDate;
    }

    /**
     * Set stepsCounter.
     *
     * @return TrackEExercises
     */
    public function setStepsCounter(int $stepsCounter)
    {
        $this->stepsCounter = $stepsCounter;

        return $this;
    }

    /**
     * Get stepsCounter.
     *
     * @return int
     */
    public function getStepsCounter()
    {
        return $this->stepsCounter;
    }

    /**
     * Set sessionId.
     *
     * @return TrackEExercises
     */
    public function setSessionId(int $sessionId)
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
     * Set origLpId.
     *
     * @return TrackEExercises
     */
    public function setOrigLpId(int $origLpId)
    {
        $this->origLpId = $origLpId;

        return $this;
    }

    /**
     * Get origLpId.
     *
     * @return int
     */
    public function getOrigLpId()
    {
        return $this->origLpId;
    }

    /**
     * Set origLpItemId.
     *
     * @return TrackEExercises
     */
    public function setOrigLpItemId(int $origLpItemId)
    {
        $this->origLpItemId = $origLpItemId;

        return $this;
    }

    /**
     * Get origLpItemId.
     *
     * @return int
     */
    public function getOrigLpItemId()
    {
        return $this->origLpItemId;
    }

    /**
     * Set exeDuration.
     *
     * @return TrackEExercises
     */
    public function setExeDuration(int $exeDuration)
    {
        $this->exeDuration = $exeDuration;

        return $this;
    }

    /**
     * Get exeDuration.
     *
     * @return int
     */
    public function getExeDuration()
    {
        return $this->exeDuration;
    }

    /**
     * Set expiredTimeControl.
     *
     * @return TrackEExercises
     */
    public function setExpiredTimeControl(DateTime $expiredTimeControl)
    {
        $this->expiredTimeControl = $expiredTimeControl;

        return $this;
    }

    /**
     * Get expiredTimeControl.
     *
     * @return DateTime
     */
    public function getExpiredTimeControl()
    {
        return $this->expiredTimeControl;
    }

    /**
     * Set origLpItemViewId.
     *
     * @return TrackEExercises
     */
    public function setOrigLpItemViewId(int $origLpItemViewId)
    {
        $this->origLpItemViewId = $origLpItemViewId;

        return $this;
    }

    /**
     * Get origLpItemViewId.
     *
     * @return int
     */
    public function getOrigLpItemViewId()
    {
        return $this->origLpItemViewId;
    }

    /**
     * Set questionsToCheck.
     *
     * @return TrackEExercises
     */
    public function setQuestionsToCheck(string $questionsToCheck)
    {
        $this->questionsToCheck = $questionsToCheck;

        return $this;
    }

    /**
     * Get questionsToCheck.
     *
     * @return string
     */
    public function getQuestionsToCheck()
    {
        return $this->questionsToCheck;
    }

    /**
     * Get exeId.
     *
     * @return int
     */
    public function getExeId()
    {
        return $this->exeId;
    }

    public function getScore(): float
    {
        return $this->score;
    }

    public function setScore(float $score): self
    {
        $this->score = $score;

        return $this;
    }

    public function getMaxScore(): float
    {
        return $this->maxScore;
    }

    public function setMaxScore(float $maxScore): self
    {
        $this->maxScore = $maxScore;

        return $this;
    }
}
