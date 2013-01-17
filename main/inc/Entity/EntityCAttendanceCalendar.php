<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * EntityCAttendanceCalendar
 *
 * @Table(name="c_attendance_calendar")
 * @Entity
 */
class EntityCAttendanceCalendar
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
     * @Column(name="date_time", type="datetime", precision=0, scale=0, nullable=false, unique=false)
     */
    private $dateTime;

    /**
     * @var boolean
     *
     * @Column(name="done_attendance", type="boolean", precision=0, scale=0, nullable=false, unique=false)
     */
    private $doneAttendance;


    /**
     * Set cId
     *
     * @param integer $cId
     * @return EntityCAttendanceCalendar
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
     * @return EntityCAttendanceCalendar
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
     * @return EntityCAttendanceCalendar
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
     * Set dateTime
     *
     * @param \DateTime $dateTime
     * @return EntityCAttendanceCalendar
     */
    public function setDateTime($dateTime)
    {
        $this->dateTime = $dateTime;

        return $this;
    }

    /**
     * Get dateTime
     *
     * @return \DateTime 
     */
    public function getDateTime()
    {
        return $this->dateTime;
    }

    /**
     * Set doneAttendance
     *
     * @param boolean $doneAttendance
     * @return EntityCAttendanceCalendar
     */
    public function setDoneAttendance($doneAttendance)
    {
        $this->doneAttendance = $doneAttendance;

        return $this;
    }

    /**
     * Get doneAttendance
     *
     * @return boolean 
     */
    public function getDoneAttendance()
    {
        return $this->doneAttendance;
    }
}
