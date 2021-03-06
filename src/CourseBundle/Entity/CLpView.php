<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CLpView.
 *
 * @ORM\Table(
 *     name="c_lp_view",
 *     indexes={
 *         @ORM\Index(name="course", columns={"c_id"}),
 *         @ORM\Index(name="lp_id", columns={"lp_id"}),
 *         @ORM\Index(name="user_id", columns={"user_id"}),
 *         @ORM\Index(name="session_id", columns={"session_id"})
 *     }
 * )
 * @ORM\Entity
 */
class CLpView
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
     * @ORM\Column(name="lp_id", type="integer", nullable=false)
     */
    protected int $lpId;

    /**
     * @ORM\Column(name="user_id", type="integer", nullable=false)
     */
    protected int $userId;

    /**
     * @ORM\Column(name="view_count", type="integer", nullable=false)
     */
    protected int $viewCount;

    /**
     * @ORM\Column(name="last_item", type="integer", nullable=false)
     */
    protected int $lastItem;

    /**
     * @ORM\Column(name="progress", type="integer", nullable=true)
     */
    protected ?int $progress = null;

    /**
     * @ORM\Column(name="session_id", type="integer", nullable=false)
     */
    protected int $sessionId;

    /**
     * Set lpId.
     *
     * @return CLpView
     */
    public function setLpId(int $lpId)
    {
        $this->lpId = $lpId;

        return $this;
    }

    /**
     * Get lpId.
     *
     * @return int
     */
    public function getLpId()
    {
        return $this->lpId;
    }

    /**
     * Set userId.
     *
     * @return CLpView
     */
    public function setUserId(int $userId)
    {
        $this->userId = $userId;

        return $this;
    }

    /**
     * Get userId.
     *
     * @return int
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * Set viewCount.
     *
     * @return CLpView
     */
    public function setViewCount(int $viewCount)
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
     * Set lastItem.
     *
     * @return CLpView
     */
    public function setLastItem(int $lastItem)
    {
        $this->lastItem = $lastItem;

        return $this;
    }

    /**
     * Get lastItem.
     *
     * @return int
     */
    public function getLastItem()
    {
        return $this->lastItem;
    }

    /**
     * Set progress.
     *
     * @return CLpView
     */
    public function setProgress(int $progress)
    {
        $this->progress = $progress;

        return $this;
    }

    /**
     * Get progress.
     *
     * @return int
     */
    public function getProgress()
    {
        return $this->progress;
    }

    /**
     * Set sessionId.
     *
     * @return CLpView
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
     * Set cId.
     *
     * @return CLpView
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
}
