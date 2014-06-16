<?php

namespace ChamiloLMS\CourseBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CLpItemView
 *
 * @ORM\Table(name="c_lp_item_view", indexes={@ORM\Index(name="lp_item_id", columns={"lp_item_id"}), @ORM\Index(name="lp_view_id", columns={"lp_view_id"}), @ORM\Index(name="idx_c_lp_item_view_cid_lp_view_id_lp_item_id", columns={"c_id", "lp_view_id", "lp_item_id"})})
 * @ORM\Entity
 */
class CLpItemView
{
    /**
     * @var integer
     *
     * @ORM\Column(name="iid", type="integer", precision=0, scale=0, nullable=false, unique=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
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
     * @ORM\Column(name="id", type="bigint", precision=0, scale=0, nullable=false, unique=false)
     */
    private $id;

    /**
     * @var integer
     *
     * @ORM\Column(name="lp_item_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $lpItemId;

    /**
     * @var integer
     *
     * @ORM\Column(name="lp_view_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $lpViewId;

    /**
     * @var integer
     *
     * @ORM\Column(name="view_count", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $viewCount;

    /**
     * @var integer
     *
     * @ORM\Column(name="start_time", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $startTime;

    /**
     * @var integer
     *
     * @ORM\Column(name="total_time", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $totalTime;

    /**
     * @var float
     *
     * @ORM\Column(name="score", type="float", precision=10, scale=0, nullable=false, unique=false)
     */
    private $score;

    /**
     * @var string
     *
     * @ORM\Column(name="status", type="string", length=32, precision=0, scale=0, nullable=false, unique=false)
     */
    private $status;

    /**
     * @var string
     *
     * @ORM\Column(name="suspend_data", type="text", precision=0, scale=0, nullable=true, unique=false)
     */
    private $suspendData;

    /**
     * @var string
     *
     * @ORM\Column(name="lesson_location", type="text", precision=0, scale=0, nullable=true, unique=false)
     */
    private $lessonLocation;

    /**
     * @var string
     *
     * @ORM\Column(name="core_exit", type="string", length=32, precision=0, scale=0, nullable=false, unique=false)
     */
    private $coreExit;

    /**
     * @var string
     *
     * @ORM\Column(name="max_score", type="string", length=8, precision=0, scale=0, nullable=true, unique=false)
     */
    private $maxScore;


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
     * @return CLpItemView
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
     * @return CLpItemView
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
     * Set lpItemId
     *
     * @param integer $lpItemId
     * @return CLpItemView
     */
    public function setLpItemId($lpItemId)
    {
        $this->lpItemId = $lpItemId;

        return $this;
    }

    /**
     * Get lpItemId
     *
     * @return integer
     */
    public function getLpItemId()
    {
        return $this->lpItemId;
    }

    /**
     * Set lpViewId
     *
     * @param integer $lpViewId
     * @return CLpItemView
     */
    public function setLpViewId($lpViewId)
    {
        $this->lpViewId = $lpViewId;

        return $this;
    }

    /**
     * Get lpViewId
     *
     * @return integer
     */
    public function getLpViewId()
    {
        return $this->lpViewId;
    }

    /**
     * Set viewCount
     *
     * @param integer $viewCount
     * @return CLpItemView
     */
    public function setViewCount($viewCount)
    {
        $this->viewCount = $viewCount;

        return $this;
    }

    /**
     * Get viewCount
     *
     * @return integer
     */
    public function getViewCount()
    {
        return $this->viewCount;
    }

    /**
     * Set startTime
     *
     * @param integer $startTime
     * @return CLpItemView
     */
    public function setStartTime($startTime)
    {
        $this->startTime = $startTime;

        return $this;
    }

    /**
     * Get startTime
     *
     * @return integer
     */
    public function getStartTime()
    {
        return $this->startTime;
    }

    /**
     * Set totalTime
     *
     * @param integer $totalTime
     * @return CLpItemView
     */
    public function setTotalTime($totalTime)
    {
        $this->totalTime = $totalTime;

        return $this;
    }

    /**
     * Get totalTime
     *
     * @return integer
     */
    public function getTotalTime()
    {
        return $this->totalTime;
    }

    /**
     * Set score
     *
     * @param float $score
     * @return CLpItemView
     */
    public function setScore($score)
    {
        $this->score = $score;

        return $this;
    }

    /**
     * Get score
     *
     * @return float
     */
    public function getScore()
    {
        return $this->score;
    }

    /**
     * Set status
     *
     * @param string $status
     * @return CLpItemView
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
     * Set suspendData
     *
     * @param string $suspendData
     * @return CLpItemView
     */
    public function setSuspendData($suspendData)
    {
        $this->suspendData = $suspendData;

        return $this;
    }

    /**
     * Get suspendData
     *
     * @return string
     */
    public function getSuspendData()
    {
        return $this->suspendData;
    }

    /**
     * Set lessonLocation
     *
     * @param string $lessonLocation
     * @return CLpItemView
     */
    public function setLessonLocation($lessonLocation)
    {
        $this->lessonLocation = $lessonLocation;

        return $this;
    }

    /**
     * Get lessonLocation
     *
     * @return string
     */
    public function getLessonLocation()
    {
        return $this->lessonLocation;
    }

    /**
     * Set coreExit
     *
     * @param string $coreExit
     * @return CLpItemView
     */
    public function setCoreExit($coreExit)
    {
        $this->coreExit = $coreExit;

        return $this;
    }

    /**
     * Get coreExit
     *
     * @return string
     */
    public function getCoreExit()
    {
        return $this->coreExit;
    }

    /**
     * Set maxScore
     *
     * @param string $maxScore
     * @return CLpItemView
     */
    public function setMaxScore($maxScore)
    {
        $this->maxScore = $maxScore;

        return $this;
    }

    /**
     * Get maxScore
     *
     * @return string
     */
    public function getMaxScore()
    {
        return $this->maxScore;
    }
}
