<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * EntityCAttendanceSheetLog
 *
 * @Table(name="c_attendance_sheet_log")
 * @Entity
 */
class EntityCAttendanceSheetLog
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
     * @Column(name="attendance_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $attendanceId;

    /**
     * @var \DateTime
     *
     * @Column(name="lastedit_date", type="datetime", precision=0, scale=0, nullable=false, unique=false)
     */
    private $lasteditDate;

    /**
     * @var string
     *
     * @Column(name="lastedit_type", type="string", length=200, precision=0, scale=0, nullable=false, unique=false)
     */
    private $lasteditType;

    /**
     * @var integer
     *
     * @Column(name="lastedit_user_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $lasteditUserId;

    /**
     * @var \DateTime
     *
     * @Column(name="calendar_date_value", type="datetime", precision=0, scale=0, nullable=true, unique=false)
     */
    private $calendarDateValue;


    /**
     * Set cId
     *
     * @param integer $cId
     * @return EntityCAttendanceSheetLog
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
     * @return EntityCAttendanceSheetLog
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
     * Set attendanceId
     *
     * @param integer $attendanceId
     * @return EntityCAttendanceSheetLog
     */
    public function setAttendanceId($attendanceId)
    {
        $this->attendanceId = $attendanceId;

        return $this;
    }

    /**
     * Get attendanceId
     *
     * @return integer 
     */
    public function getAttendanceId()
    {
        return $this->attendanceId;
    }

    /**
     * Set lasteditDate
     *
     * @param \DateTime $lasteditDate
     * @return EntityCAttendanceSheetLog
     */
    public function setLasteditDate($lasteditDate)
    {
        $this->lasteditDate = $lasteditDate;

        return $this;
    }

    /**
     * Get lasteditDate
     *
     * @return \DateTime 
     */
    public function getLasteditDate()
    {
        return $this->lasteditDate;
    }

    /**
     * Set lasteditType
     *
     * @param string $lasteditType
     * @return EntityCAttendanceSheetLog
     */
    public function setLasteditType($lasteditType)
    {
        $this->lasteditType = $lasteditType;

        return $this;
    }

    /**
     * Get lasteditType
     *
     * @return string 
     */
    public function getLasteditType()
    {
        return $this->lasteditType;
    }

    /**
     * Set lasteditUserId
     *
     * @param integer $lasteditUserId
     * @return EntityCAttendanceSheetLog
     */
    public function setLasteditUserId($lasteditUserId)
    {
        $this->lasteditUserId = $lasteditUserId;

        return $this;
    }

    /**
     * Get lasteditUserId
     *
     * @return integer 
     */
    public function getLasteditUserId()
    {
        return $this->lasteditUserId;
    }

    /**
     * Set calendarDateValue
     *
     * @param \DateTime $calendarDateValue
     * @return EntityCAttendanceSheetLog
     */
    public function setCalendarDateValue($calendarDateValue)
    {
        $this->calendarDateValue = $calendarDateValue;

        return $this;
    }

    /**
     * Get calendarDateValue
     *
     * @return \DateTime 
     */
    public function getCalendarDateValue()
    {
        return $this->calendarDateValue;
    }
}
