<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * EntityCDropboxCategory
 *
 * @Table(name="c_dropbox_category")
 * @Entity
 */
class EntityCDropboxCategory
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
     * @Column(name="cat_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     * @Id
     * @GeneratedValue(strategy="NONE")
     */
    private $catId;

    /**
     * @var string
     *
     * @Column(name="cat_name", type="text", precision=0, scale=0, nullable=false, unique=false)
     */
    private $catName;

    /**
     * @var boolean
     *
     * @Column(name="received", type="boolean", precision=0, scale=0, nullable=false, unique=false)
     */
    private $received;

    /**
     * @var boolean
     *
     * @Column(name="sent", type="boolean", precision=0, scale=0, nullable=false, unique=false)
     */
    private $sent;

    /**
     * @var integer
     *
     * @Column(name="user_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $userId;

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
     * @return EntityCDropboxCategory
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
     * Set catId
     *
     * @param integer $catId
     * @return EntityCDropboxCategory
     */
    public function setCatId($catId)
    {
        $this->catId = $catId;

        return $this;
    }

    /**
     * Get catId
     *
     * @return integer 
     */
    public function getCatId()
    {
        return $this->catId;
    }

    /**
     * Set catName
     *
     * @param string $catName
     * @return EntityCDropboxCategory
     */
    public function setCatName($catName)
    {
        $this->catName = $catName;

        return $this;
    }

    /**
     * Get catName
     *
     * @return string 
     */
    public function getCatName()
    {
        return $this->catName;
    }

    /**
     * Set received
     *
     * @param boolean $received
     * @return EntityCDropboxCategory
     */
    public function setReceived($received)
    {
        $this->received = $received;

        return $this;
    }

    /**
     * Get received
     *
     * @return boolean 
     */
    public function getReceived()
    {
        return $this->received;
    }

    /**
     * Set sent
     *
     * @param boolean $sent
     * @return EntityCDropboxCategory
     */
    public function setSent($sent)
    {
        $this->sent = $sent;

        return $this;
    }

    /**
     * Get sent
     *
     * @return boolean 
     */
    public function getSent()
    {
        return $this->sent;
    }

    /**
     * Set userId
     *
     * @param integer $userId
     * @return EntityCDropboxCategory
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
     * Set sessionId
     *
     * @param integer $sessionId
     * @return EntityCDropboxCategory
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
