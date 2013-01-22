<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * EntityCAttendance
 *
 * @Table(name="c_attendance")
 * @Entity
 */
class EntityCAttendance
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
     * @var string
     *
     * @Column(name="name", type="text", precision=0, scale=0, nullable=false, unique=false)
     */
    private $name;

    /**
     * @var string
     *
     * @Column(name="description", type="text", precision=0, scale=0, nullable=true, unique=false)
     */
    private $description;

    /**
     * @var boolean
     *
     * @Column(name="active", type="boolean", precision=0, scale=0, nullable=false, unique=false)
     */
    private $active;

    /**
     * @var string
     *
     * @Column(name="attendance_qualify_title", type="string", length=255, precision=0, scale=0, nullable=true, unique=false)
     */
    private $attendanceQualifyTitle;

    /**
     * @var integer
     *
     * @Column(name="attendance_qualify_max", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $attendanceQualifyMax;

    /**
     * @var float
     *
     * @Column(name="attendance_weight", type="float", precision=0, scale=0, nullable=false, unique=false)
     */
    private $attendanceWeight;

    /**
     * @var integer
     *
     * @Column(name="session_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $sessionId;

    /**
     * @var integer
     *
     * @Column(name="locked", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $locked;


    /**
     * Set cId
     *
     * @param integer $cId
     * @return EntityCAttendance
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
     * @return EntityCAttendance
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
     * Set name
     *
     * @param string $name
     * @return EntityCAttendance
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string 
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set description
     *
     * @param string $description
     * @return EntityCAttendance
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description
     *
     * @return string 
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set active
     *
     * @param boolean $active
     * @return EntityCAttendance
     */
    public function setActive($active)
    {
        $this->active = $active;

        return $this;
    }

    /**
     * Get active
     *
     * @return boolean 
     */
    public function getActive()
    {
        return $this->active;
    }

    /**
     * Set attendanceQualifyTitle
     *
     * @param string $attendanceQualifyTitle
     * @return EntityCAttendance
     */
    public function setAttendanceQualifyTitle($attendanceQualifyTitle)
    {
        $this->attendanceQualifyTitle = $attendanceQualifyTitle;

        return $this;
    }

    /**
     * Get attendanceQualifyTitle
     *
     * @return string 
     */
    public function getAttendanceQualifyTitle()
    {
        return $this->attendanceQualifyTitle;
    }

    /**
     * Set attendanceQualifyMax
     *
     * @param integer $attendanceQualifyMax
     * @return EntityCAttendance
     */
    public function setAttendanceQualifyMax($attendanceQualifyMax)
    {
        $this->attendanceQualifyMax = $attendanceQualifyMax;

        return $this;
    }

    /**
     * Get attendanceQualifyMax
     *
     * @return integer 
     */
    public function getAttendanceQualifyMax()
    {
        return $this->attendanceQualifyMax;
    }

    /**
     * Set attendanceWeight
     *
     * @param float $attendanceWeight
     * @return EntityCAttendance
     */
    public function setAttendanceWeight($attendanceWeight)
    {
        $this->attendanceWeight = $attendanceWeight;

        return $this;
    }

    /**
     * Get attendanceWeight
     *
     * @return float 
     */
    public function getAttendanceWeight()
    {
        return $this->attendanceWeight;
    }

    /**
     * Set sessionId
     *
     * @param integer $sessionId
     * @return EntityCAttendance
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

    /**
     * Set locked
     *
     * @param integer $locked
     * @return EntityCAttendance
     */
    public function setLocked($locked)
    {
        $this->locked = $locked;

        return $this;
    }

    /**
     * Get locked
     *
     * @return integer 
     */
    public function getLocked()
    {
        return $this->locked;
    }
}
