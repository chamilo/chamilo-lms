<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CLpView.
 *
 * @ORM\Table(
 *  name="c_lp_view",
 *  indexes={
 *      @ORM\Index(name="course", columns={"c_id"}),
 *      @ORM\Index(name="lp_id", columns={"lp_id"}),
 *      @ORM\Index(name="user_id", columns={"user_id"}),
 *      @ORM\Index(name="session_id", columns={"session_id"})
 *  }
 * )
 * @ORM\Entity
 */
class CLpView
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
     * @ORM\Column(name="c_id", type="integer")
     */
    protected $cId;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=true)
     */
    protected $id;

    /**
     * @var int
     *
     * @ORM\Column(name="lp_id", type="integer", nullable=false)
     */
    protected $lpId;

    /**
     * @var int
     *
     * @ORM\Column(name="user_id", type="integer", nullable=false)
     */
    protected $userId;

    /**
     * @var int
     *
     * @ORM\Column(name="view_count", type="integer", nullable=false)
     */
    protected $viewCount;

    /**
     * @var int
     *
     * @ORM\Column(name="last_item", type="integer", nullable=false)
     */
    protected $lastItem;

    /**
     * @var int
     *
     * @ORM\Column(name="progress", type="integer", nullable=true)
     */
    protected $progress;

    /**
     * @var int
     *
     * @ORM\Column(name="session_id", type="integer", nullable=false)
     */
    protected $sessionId;

    /**
     * Set lpId.
     *
     * @param int $lpId
     *
     * @return CLpView
     */
    public function setLpId($lpId)
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
     * @param int $userId
     *
     * @return CLpView
     */
    public function setUserId($userId)
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
     * @param int $viewCount
     *
     * @return CLpView
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
     * Set lastItem.
     *
     * @param int $lastItem
     *
     * @return CLpView
     */
    public function setLastItem($lastItem)
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
     * @param int $progress
     *
     * @return CLpView
     */
    public function setProgress($progress)
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
     * @param int $sessionId
     *
     * @return CLpView
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
     * Set id.
     *
     * @param int $id
     *
     * @return CLpView
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set cId.
     *
     * @param int $cId
     *
     * @return CLpView
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
