<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * EntityTrackEDownloads
 *
 * @Table(name="track_e_downloads")
 * @Entity
 */
class EntityTrackEDownloads
{
    /**
     * @var integer
     *
     * @Column(name="down_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     * @Id
     * @GeneratedValue(strategy="IDENTITY")
     */
    private $downId;

    /**
     * @var integer
     *
     * @Column(name="down_user_id", type="integer", precision=0, scale=0, nullable=true, unique=false)
     */
    private $downUserId;

    /**
     * @var \DateTime
     *
     * @Column(name="down_date", type="datetime", precision=0, scale=0, nullable=false, unique=false)
     */
    private $downDate;

    /**
     * @var string
     *
     * @Column(name="down_cours_id", type="string", length=40, precision=0, scale=0, nullable=false, unique=false)
     */
    private $downCoursId;

    /**
     * @var string
     *
     * @Column(name="down_doc_path", type="string", length=255, precision=0, scale=0, nullable=false, unique=false)
     */
    private $downDocPath;

    /**
     * @var integer
     *
     * @Column(name="down_session_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $downSessionId;


    /**
     * Get downId
     *
     * @return integer 
     */
    public function getDownId()
    {
        return $this->downId;
    }

    /**
     * Set downUserId
     *
     * @param integer $downUserId
     * @return EntityTrackEDownloads
     */
    public function setDownUserId($downUserId)
    {
        $this->downUserId = $downUserId;

        return $this;
    }

    /**
     * Get downUserId
     *
     * @return integer 
     */
    public function getDownUserId()
    {
        return $this->downUserId;
    }

    /**
     * Set downDate
     *
     * @param \DateTime $downDate
     * @return EntityTrackEDownloads
     */
    public function setDownDate($downDate)
    {
        $this->downDate = $downDate;

        return $this;
    }

    /**
     * Get downDate
     *
     * @return \DateTime 
     */
    public function getDownDate()
    {
        return $this->downDate;
    }

    /**
     * Set downCoursId
     *
     * @param string $downCoursId
     * @return EntityTrackEDownloads
     */
    public function setDownCoursId($downCoursId)
    {
        $this->downCoursId = $downCoursId;

        return $this;
    }

    /**
     * Get downCoursId
     *
     * @return string 
     */
    public function getDownCoursId()
    {
        return $this->downCoursId;
    }

    /**
     * Set downDocPath
     *
     * @param string $downDocPath
     * @return EntityTrackEDownloads
     */
    public function setDownDocPath($downDocPath)
    {
        $this->downDocPath = $downDocPath;

        return $this;
    }

    /**
     * Get downDocPath
     *
     * @return string 
     */
    public function getDownDocPath()
    {
        return $this->downDocPath;
    }

    /**
     * Set downSessionId
     *
     * @param integer $downSessionId
     * @return EntityTrackEDownloads
     */
    public function setDownSessionId($downSessionId)
    {
        $this->downSessionId = $downSessionId;

        return $this;
    }

    /**
     * Get downSessionId
     *
     * @return integer 
     */
    public function getDownSessionId()
    {
        return $this->downSessionId;
    }
}
