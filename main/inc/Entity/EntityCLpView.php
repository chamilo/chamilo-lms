<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * EntityCLpView
 *
 * @Table(name="c_lp_view")
 * @Entity
 */
class EntityCLpView
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
     * @var integer
     *
     * @Column(name="lp_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $lpId;

    /**
     * @var integer
     *
     * @Column(name="user_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $userId;

    /**
     * @var integer
     *
     * @Column(name="view_count", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $viewCount;

    /**
     * @var integer
     *
     * @Column(name="last_item", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $lastItem;

    /**
     * @var integer
     *
     * @Column(name="progress", type="integer", precision=0, scale=0, nullable=true, unique=false)
     */
    private $progress;

    /**
     * @var integer
     *
     * @Column(name="session_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $sessionId;


    /**
     * Set cId
     *
     * @param integer $cId
     * @return EntityCLpView
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
     * @return EntityCLpView
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
     * Set lpId
     *
     * @param integer $lpId
     * @return EntityCLpView
     */
    public function setLpId($lpId)
    {
        $this->lpId = $lpId;

        return $this;
    }

    /**
     * Get lpId
     *
     * @return integer 
     */
    public function getLpId()
    {
        return $this->lpId;
    }

    /**
     * Set userId
     *
     * @param integer $userId
     * @return EntityCLpView
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;

        return $this;
    }

    /**
     * Get userId
     *
     * @return integer 
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * Set viewCount
     *
     * @param integer $viewCount
     * @return EntityCLpView
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
     * Set lastItem
     *
     * @param integer $lastItem
     * @return EntityCLpView
     */
    public function setLastItem($lastItem)
    {
        $this->lastItem = $lastItem;

        return $this;
    }

    /**
     * Get lastItem
     *
     * @return integer 
     */
    public function getLastItem()
    {
        return $this->lastItem;
    }

    /**
     * Set progress
     *
     * @param integer $progress
     * @return EntityCLpView
     */
    public function setProgress($progress)
    {
        $this->progress = $progress;

        return $this;
    }

    /**
     * Get progress
     *
     * @return integer 
     */
    public function getProgress()
    {
        return $this->progress;
    }

    /**
     * Set sessionId
     *
     * @param integer $sessionId
     * @return EntityCLpView
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
}
