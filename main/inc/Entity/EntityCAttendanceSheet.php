<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * EntityCAttendanceSheet
 *
 * @Table(name="c_attendance_sheet")
 * @Entity
 */
class EntityCAttendanceSheet
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
     * @Column(name="user_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     * @Id
     * @GeneratedValue(strategy="NONE")
     */
    private $userId;

    /**
     * @var integer
     *
     * @Column(name="attendance_calendar_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     * @Id
     * @GeneratedValue(strategy="NONE")
     */
    private $attendanceCalendarId;

    /**
     * @var boolean
     *
     * @Column(name="presence", type="boolean", precision=0, scale=0, nullable=false, unique=false)
     */
    private $presence;


    /**
     * Set cId
     *
     * @param integer $cId
     * @return EntityCAttendanceSheet
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
     * Set userId
     *
     * @param integer $userId
     * @return EntityCAttendanceSheet
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
     * Set attendanceCalendarId
     *
     * @param integer $attendanceCalendarId
     * @return EntityCAttendanceSheet
     */
    public function setAttendanceCalendarId($attendanceCalendarId)
    {
        $this->attendanceCalendarId = $attendanceCalendarId;

        return $this;
    }

    /**
     * Get attendanceCalendarId
     *
     * @return integer 
     */
    public function getAttendanceCalendarId()
    {
        return $this->attendanceCalendarId;
    }

    /**
     * Set presence
     *
     * @param boolean $presence
     * @return EntityCAttendanceSheet
     */
    public function setPresence($presence)
    {
        $this->presence = $presence;

        return $this;
    }

    /**
     * Get presence
     *
     * @return boolean 
     */
    public function getPresence()
    {
        return $this->presence;
    }
}
