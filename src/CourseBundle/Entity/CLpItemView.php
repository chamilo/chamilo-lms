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
 *         @ORM\Index(name="lp_item_id", columns={"lp_item_id"}),
 *         @ORM\Index(name="lp_view_id", columns={"lp_view_id"}),
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
     * @ORM\ManyToOne(targetEntity="Chamilo\CourseBundle\Entity\CLpItem")
     * @ORM\JoinColumn(name="lp_item_id", referencedColumnName="iid", onDelete="CASCADE")
     */
    protected CLpItem $item;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CourseBundle\Entity\CLpView")
     * @ORM\JoinColumn(name="lp_view_id", referencedColumnName="iid", onDelete="CASCADE")
     */
    protected CLpView $view;

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
    protected ?string $suspendData = null;

    /**
     * @ORM\Column(name="lesson_location", type="text", nullable=true)
     */
    protected ?string $lessonLocation = null;

    /**
     * @ORM\Column(name="core_exit", type="string", length=32, nullable=false, options={"default":"none"})
     */
    protected string $coreExit;

    /**
     * @ORM\Column(name="max_score", type="string", length=8, nullable=true)
     */
    protected ?string $maxScore = null;

    public function __construct()
    {
        $this->status = 'not attempted';
        $this->coreExit = 'none';
    }

    public function getIid(): int
    {
        return $this->iid;
    }

    public function setViewCount(int $viewCount): self
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

    public function setStartTime(int $startTime): self
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

    public function setTotalTime(int $totalTime): self
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

    public function setScore(float $score): self
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

    public function setStatus(string $status): self
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

    public function setSuspendData(string $suspendData): self
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

    public function setLessonLocation(string $lessonLocation): self
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

    public function setCoreExit(string $coreExit): self
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

    public function setMaxScore(string $maxScore): self
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

    public function getItem(): CLpItem
    {
        return $this->item;
    }

    public function setItem(CLpItem $item): self
    {
        $this->item = $item;

        return $this;
    }

    public function getView(): CLpView
    {
        return $this->view;
    }

    public function setView(CLpView $view): self
    {
        $this->view = $view;

        return $this;
    }
}
