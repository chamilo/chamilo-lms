<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CLpItemView.
 *
 * @ORM\Table(
 *     name="c_lp_item_view",
 *     indexes={
 *         @ORM\Index(name="course", columns={"c_id"}),
 *         @ORM\Index(name="lp_item_id", columns={"lp_item_id"}),
 *         @ORM\Index(name="lp_view_id", columns={"lp_view_id"}),
 *         @ORM\Index(name="idx_c_lp_item_view_cid_lp_view_id_lp_item_id", columns={"c_id", "lp_view_id", "lp_item_id"}),
 *         @ORM\Index(name="idx_c_lp_item_view_cid_id_view_count", columns={"c_id", "iid", "view_count"})
 *
 *     }
 * )
 * @ORM\Entity
 */
class CLpItemView
{
    /**
     * @ORM\Column(name="iid", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected int $iid;

    /**
     * @ORM\Column(name="c_id", type="integer")
     */
    protected int $cId;

    /**
     * @ORM\Column(name="lp_item_id", type="integer", nullable=false)
     */
    protected int $lpItemId;

    /**
     * @ORM\Column(name="lp_view_id", type="integer", nullable=false)
     */
    protected int $lpViewId;

    /**
     * @ORM\Column(name="view_count", type="integer", nullable=false)
     */
    protected int $viewCount;

    /**
     * @ORM\Column(name="start_time", type="integer", nullable=false)
     */
    protected int $startTime;

    /**
     * @ORM\Column(name="total_time", type="integer", nullable=false)
     */
    protected int $totalTime;

    /**
     * @ORM\Column(name="score", type="float", precision=10, scale=0, nullable=false)
     */
    protected float $score;

    /**
     * @ORM\Column(name="status", type="string", length=32, nullable=false, options={"default":"not attempted"})
     */
    protected string $status;

    /**
     * @ORM\Column(name="suspend_data", type="text", nullable=true)
     */
    protected ?string $suspendData;

    /**
     * @ORM\Column(name="lesson_location", type="text", nullable=true)
     */
    protected ?string $lessonLocation;

    /**
     * @ORM\Column(name="core_exit", type="string", length=32, nullable=false, options={"default":"none"})
     */
    protected string $coreExit;

    /**
     * @ORM\Column(name="max_score", type="string", length=8, nullable=true)
     */
    protected ?string $maxScore;

    public function __construct()
    {
        $this->status = 'not attempted';
        $this->coreExit = 'none';
    }

    public function getIid(): int
    {
        return $this->iid;
    }

    /**
     * Set lpItemId.
     *
     * @param int $lpItemId
     *
     * @return CLpItemView
     */
    public function setLpItemId($lpItemId)
    {
        $this->lpItemId = $lpItemId;

        return $this;
    }

    /**
     * Get lpItemId.
     *
     * @return int
     */
    public function getLpItemId()
    {
        return $this->lpItemId;
    }

    /**
     * Set lpViewId.
     *
     * @param int $lpViewId
     *
     * @return CLpItemView
     */
    public function setLpViewId($lpViewId)
    {
        $this->lpViewId = $lpViewId;

        return $this;
    }

    /**
     * Get lpViewId.
     *
     * @return int
     */
    public function getLpViewId()
    {
        return $this->lpViewId;
    }

    /**
     * Set viewCount.
     *
     * @param int $viewCount
     *
     * @return CLpItemView
     */
    public function setViewCount($viewCount)
    {
        $this->viewCount = $viewCount;

        return $this;
    }

    /**
     * Get viewCount.
     *
     * @return int
     */
    public function getViewCount()
    {
        return $this->viewCount;
    }

    /**
     * Set startTime.
     *
     * @param int $startTime
     *
     * @return CLpItemView
     */
    public function setStartTime($startTime)
    {
        $this->startTime = $startTime;

        return $this;
    }

    /**
     * Get startTime.
     *
     * @return int
     */
    public function getStartTime()
    {
        return $this->startTime;
    }

    /**
     * Set totalTime.
     *
     * @param int $totalTime
     *
     * @return CLpItemView
     */
    public function setTotalTime($totalTime)
    {
        $this->totalTime = $totalTime;

        return $this;
    }

    /**
     * Get totalTime.
     *
     * @return int
     */
    public function getTotalTime()
    {
        return $this->totalTime;
    }

    /**
     * Set score.
     *
     * @param float $score
     *
     * @return CLpItemView
     */
    public function setScore($score)
    {
        $this->score = $score;

        return $this;
    }

    /**
     * Get score.
     *
     * @return float
     */
    public function getScore()
    {
        return $this->score;
    }

    /**
     * Set status.
     *
     * @param string $status
     *
     * @return CLpItemView
     */
    public function setStatus($status)
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
     * Set suspendData.
     *
     * @param string $suspendData
     *
     * @return CLpItemView
     */
    public function setSuspendData($suspendData)
    {
        $this->suspendData = $suspendData;

        return $this;
    }

    /**
     * Get suspendData.
     *
     * @return string
     */
    public function getSuspendData()
    {
        return $this->suspendData;
    }

    /**
     * Set lessonLocation.
     *
     * @param string $lessonLocation
     *
     * @return CLpItemView
     */
    public function setLessonLocation($lessonLocation)
    {
        $this->lessonLocation = $lessonLocation;

        return $this;
    }

    /**
     * Get lessonLocation.
     *
     * @return string
     */
    public function getLessonLocation()
    {
        return $this->lessonLocation;
    }

    /**
     * Set coreExit.
     *
     * @param string $coreExit
     *
     * @return CLpItemView
     */
    public function setCoreExit($coreExit)
    {
        $this->coreExit = $coreExit;

        return $this;
    }

    /**
     * Get coreExit.
     *
     * @return string
     */
    public function getCoreExit()
    {
        return $this->coreExit;
    }

    /**
     * Set maxScore.
     *
     * @param string $maxScore
     *
     * @return CLpItemView
     */
    public function setMaxScore($maxScore)
    {
        $this->maxScore = $maxScore;

        return $this;
    }

    /**
     * Get maxScore.
     *
     * @return string
     */
    public function getMaxScore()
    {
        return $this->maxScore;
    }

    /**
     * Set cId.
     *
     * @param int $cId
     *
     * @return CLpItemView
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
