<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * EntityTrackEUploads
 *
 * @Table(name="track_e_uploads")
 * @Entity
 */
class EntityTrackEUploads
{
    /**
     * @var integer
     *
     * @Column(name="upload_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     * @Id
     * @GeneratedValue(strategy="IDENTITY")
     */
    private $uploadId;

    /**
     * @var integer
     *
     * @Column(name="upload_user_id", type="integer", precision=0, scale=0, nullable=true, unique=false)
     */
    private $uploadUserId;

    /**
     * @var \DateTime
     *
     * @Column(name="upload_date", type="datetime", precision=0, scale=0, nullable=false, unique=false)
     */
    private $uploadDate;

    /**
     * @var string
     *
     * @Column(name="upload_cours_id", type="string", length=40, precision=0, scale=0, nullable=false, unique=false)
     */
    private $uploadCoursId;

    /**
     * @var integer
     *
     * @Column(name="upload_work_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $uploadWorkId;

    /**
     * @var integer
     *
     * @Column(name="upload_session_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $uploadSessionId;


    /**
     * Get uploadId
     *
     * @return integer 
     */
    public function getUploadId()
    {
        return $this->uploadId;
    }

    /**
     * Set uploadUserId
     *
     * @param integer $uploadUserId
     * @return EntityTrackEUploads
     */
    public function setUploadUserId($uploadUserId)
    {
        $this->uploadUserId = $uploadUserId;

        return $this;
    }

    /**
     * Get uploadUserId
     *
     * @return integer 
     */
    public function getUploadUserId()
    {
        return $this->uploadUserId;
    }

    /**
     * Set uploadDate
     *
     * @param \DateTime $uploadDate
     * @return EntityTrackEUploads
     */
    public function setUploadDate($uploadDate)
    {
        $this->uploadDate = $uploadDate;

        return $this;
    }

    /**
     * Get uploadDate
     *
     * @return \DateTime 
     */
    public function getUploadDate()
    {
        return $this->uploadDate;
    }

    /**
     * Set uploadCoursId
     *
     * @param string $uploadCoursId
     * @return EntityTrackEUploads
     */
    public function setUploadCoursId($uploadCoursId)
    {
        $this->uploadCoursId = $uploadCoursId;

        return $this;
    }

    /**
     * Get uploadCoursId
     *
     * @return string 
     */
    public function getUploadCoursId()
    {
        return $this->uploadCoursId;
    }

    /**
     * Set uploadWorkId
     *
     * @param integer $uploadWorkId
     * @return EntityTrackEUploads
     */
    public function setUploadWorkId($uploadWorkId)
    {
        $this->uploadWorkId = $uploadWorkId;

        return $this;
    }

    /**
     * Get uploadWorkId
     *
     * @return integer 
     */
    public function getUploadWorkId()
    {
        return $this->uploadWorkId;
    }

    /**
     * Set uploadSessionId
     *
     * @param integer $uploadSessionId
     * @return EntityTrackEUploads
     */
    public function setUploadSessionId($uploadSessionId)
    {
        $this->uploadSessionId = $uploadSessionId;

        return $this;
    }

    /**
     * Get uploadSessionId
     *
     * @return integer 
     */
    public function getUploadSessionId()
    {
        return $this->uploadSessionId;
    }
}
