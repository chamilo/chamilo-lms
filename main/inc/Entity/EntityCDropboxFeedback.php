<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * EntityCDropboxFeedback
 *
 * @Table(name="c_dropbox_feedback")
 * @Entity
 */
class EntityCDropboxFeedback
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
     * @Column(name="feedback_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     * @Id
     * @GeneratedValue(strategy="NONE")
     */
    private $feedbackId;

    /**
     * @var integer
     *
     * @Column(name="file_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $fileId;

    /**
     * @var integer
     *
     * @Column(name="author_user_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $authorUserId;

    /**
     * @var string
     *
     * @Column(name="feedback", type="text", precision=0, scale=0, nullable=false, unique=false)
     */
    private $feedback;

    /**
     * @var \DateTime
     *
     * @Column(name="feedback_date", type="datetime", precision=0, scale=0, nullable=false, unique=false)
     */
    private $feedbackDate;


    /**
     * Set cId
     *
     * @param integer $cId
     * @return EntityCDropboxFeedback
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
     * Set feedbackId
     *
     * @param integer $feedbackId
     * @return EntityCDropboxFeedback
     */
    public function setFeedbackId($feedbackId)
    {
        $this->feedbackId = $feedbackId;

        return $this;
    }

    /**
     * Get feedbackId
     *
     * @return integer 
     */
    public function getFeedbackId()
    {
        return $this->feedbackId;
    }

    /**
     * Set fileId
     *
     * @param integer $fileId
     * @return EntityCDropboxFeedback
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
     * Set authorUserId
     *
     * @param integer $authorUserId
     * @return EntityCDropboxFeedback
     */
    public function setAuthorUserId($authorUserId)
    {
        $this->authorUserId = $authorUserId;

        return $this;
    }

    /**
     * Get authorUserId
     *
     * @return integer 
     */
    public function getAuthorUserId()
    {
        return $this->authorUserId;
    }

    /**
     * Set feedback
     *
     * @param string $feedback
     * @return EntityCDropboxFeedback
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
     * Set feedbackDate
     *
     * @param \DateTime $feedbackDate
     * @return EntityCDropboxFeedback
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
}
