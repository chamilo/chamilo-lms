<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * EntityTrackELastaccess
 *
 * @Table(name="track_e_lastaccess")
 * @Entity
 */
class EntityTrackELastaccess
{
    /**
     * @var integer
     *
     * @Column(name="access_id", type="bigint", precision=0, scale=0, nullable=false, unique=false)
     * @Id
     * @GeneratedValue(strategy="IDENTITY")
     */
    private $accessId;

    /**
     * @var integer
     *
     * @Column(name="access_user_id", type="integer", precision=0, scale=0, nullable=true, unique=false)
     */
    private $accessUserId;

    /**
     * @var \DateTime
     *
     * @Column(name="access_date", type="datetime", precision=0, scale=0, nullable=false, unique=false)
     */
    private $accessDate;

    /**
     * @var string
     *
     * @Column(name="access_cours_code", type="string", length=40, precision=0, scale=0, nullable=false, unique=false)
     */
    private $accessCoursCode;

    /**
     * @var string
     *
     * @Column(name="access_tool", type="string", length=30, precision=0, scale=0, nullable=true, unique=false)
     */
    private $accessTool;

    /**
     * @var integer
     *
     * @Column(name="access_session_id", type="integer", precision=0, scale=0, nullable=true, unique=false)
     */
    private $accessSessionId;


    /**
     * Get accessId
     *
     * @return integer 
     */
    public function getAccessId()
    {
        return $this->accessId;
    }

    /**
     * Set accessUserId
     *
     * @param integer $accessUserId
     * @return EntityTrackELastaccess
     */
    public function setAccessUserId($accessUserId)
    {
        $this->accessUserId = $accessUserId;

        return $this;
    }

    /**
     * Get accessUserId
     *
     * @return integer 
     */
    public function getAccessUserId()
    {
        return $this->accessUserId;
    }

    /**
     * Set accessDate
     *
     * @param \DateTime $accessDate
     * @return EntityTrackELastaccess
     */
    public function setAccessDate($accessDate)
    {
        $this->accessDate = $accessDate;

        return $this;
    }

    /**
     * Get accessDate
     *
     * @return \DateTime 
     */
    public function getAccessDate()
    {
        return $this->accessDate;
    }

    /**
     * Set accessCoursCode
     *
     * @param string $accessCoursCode
     * @return EntityTrackELastaccess
     */
    public function setAccessCoursCode($accessCoursCode)
    {
        $this->accessCoursCode = $accessCoursCode;

        return $this;
    }

    /**
     * Get accessCoursCode
     *
     * @return string 
     */
    public function getAccessCoursCode()
    {
        return $this->accessCoursCode;
    }

    /**
     * Set accessTool
     *
     * @param string $accessTool
     * @return EntityTrackELastaccess
     */
    public function setAccessTool($accessTool)
    {
        $this->accessTool = $accessTool;

        return $this;
    }

    /**
     * Get accessTool
     *
     * @return string 
     */
    public function getAccessTool()
    {
        return $this->accessTool;
    }

    /**
     * Set accessSessionId
     *
     * @param integer $accessSessionId
     * @return EntityTrackELastaccess
     */
    public function setAccessSessionId($accessSessionId)
    {
        $this->accessSessionId = $accessSessionId;

        return $this;
    }

    /**
     * Get accessSessionId
     *
     * @return integer 
     */
    public function getAccessSessionId()
    {
        return $this->accessSessionId;
    }
}
