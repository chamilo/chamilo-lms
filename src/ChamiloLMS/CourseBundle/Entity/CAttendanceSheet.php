<?php

namespace ChamiloLMS\CourseBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CAttendanceSheet
 *
 * @ORM\Table(name="c_attendance_sheet", uniqueConstraints={@ORM\UniqueConstraint(name="c_id", columns={"c_id", "user_id", "attendance_calendar_id"})}, indexes={@ORM\Index(name="presence", columns={"presence"})})
 * @ORM\Entity
 */
class CAttendanceSheet
{
    /**
     * @var integer
     *
     * @ORM\Column(name="iid", type="integer", precision=0, scale=0, nullable=false, unique=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $iid;

    /**
     * @var integer
     *
     * @ORM\Column(name="c_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $cId;

    /**
     * @var integer
     *
     * @ORM\Column(name="user_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $userId;

    /**
     * @var integer
     *
     * @ORM\Column(name="attendance_calendar_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $attendanceCalendarId;

    /**
     * @var boolean
     *
     * @ORM\Column(name="presence", type="boolean", precision=0, scale=0, nullable=false, unique=false)
     */
    private $presence;


    /**
     * Get iid
     *
     * @return integer
     */
    public function getIid()
    {
        return $this->iid;
    }

    /**
     * Set cId
     *
     * @param integer $cId
     * @return CAttendanceSheet
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
     * @return CAttendanceSheet
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
     * @return CAttendanceSheet
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
     * @return CAttendanceSheet
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
