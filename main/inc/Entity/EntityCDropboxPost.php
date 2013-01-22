<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * EntityCDropboxPost
 *
 * @Table(name="c_dropbox_post")
 * @Entity
 */
class EntityCDropboxPost
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
     * @Column(name="file_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     * @Id
     * @GeneratedValue(strategy="NONE")
     */
    private $fileId;

    /**
     * @var integer
     *
     * @Column(name="dest_user_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     * @Id
     * @GeneratedValue(strategy="NONE")
     */
    private $destUserId;

    /**
     * @var \DateTime
     *
     * @Column(name="feedback_date", type="datetime", precision=0, scale=0, nullable=false, unique=false)
     */
    private $feedbackDate;

    /**
     * @var string
     *
     * @Column(name="feedback", type="text", precision=0, scale=0, nullable=true, unique=false)
     */
    private $feedback;

    /**
     * @var integer
     *
     * @Column(name="cat_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $catId;

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
     * @return EntityCDropboxPost
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
     * Set fileId
     *
     * @param integer $fileId
     * @return EntityCDropboxPost
     */
    public function setFileId($fileId)
    {
        $this->fileId = $fileId;

        return $this;
    }

    /**
     * Get fileId
     *
     * @return integer 
     */
    public function getFileId()
    {
        return $this->fileId;
    }

    /**
     * Set destUserId
     *
     * @param integer $destUserId
     * @return EntityCDropboxPost
     */
    public function setDestUserId($destUserId)
    {
        $this->destUserId = $destUserId;

        return $this;
    }

    /**
     * Get destUserId
     *
     * @return integer 
     */
    public function getDestUserId()
    {
        return $this->destUserId;
    }

    /**
     * Set feedbackDate
     *
     * @param \DateTime $feedbackDate
     * @return EntityCDropboxPost
     */
    public function setFeedbackDate($feedbackDate)
    {
        $this->feedbackDate = $feedbackDate;

        return $this;
    }

    /**
     * Get feedbackDate
     *
     * @return \DateTime 
     */
    public function getFeedbackDate()
    {
        return $this->feedbackDate;
    }

    /**
     * Set feedback
     *
     * @param string $feedback
     * @return EntityCDropboxPost
     */
    public function setFeedback($feedback)
    {
        $this->feedback = $feedback;

        return $this;
    }

    /**
     * Get feedback
     *
     * @return string 
     */
    public function getFeedback()
    {
        return $this->feedback;
    }

    /**
     * Set catId
     *
     * @param integer $catId
     * @return EntityCDropboxPost
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
     * Set sessionId
     *
     * @param integer $sessionId
     * @return EntityCDropboxPost
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
