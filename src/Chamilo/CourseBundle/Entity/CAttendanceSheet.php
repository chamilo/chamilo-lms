<?php

namespace Chamilo\CourseBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CAttendanceSheet
 *
 * @ORM\Table(name="c_attendance_sheet", indexes={@ORM\Index(name="presence", columns={"presence"})})
 * @ORM\Entity
 */
class CAttendanceSheet
{
    /**
     * @var boolean
     *
     * @ORM\Column(name="presence", type="boolean", nullable=false)
     */
    private $presence;

    /**
     * @var integer
     *
     * @ORM\Column(name="c_id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     */
    private $cId;

    /**
     * @var integer
     *
     * @ORM\Column(name="user_id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     */
    private $userId;

    /**
     * @var integer
     *
     * @ORM\Column(name="attendance_calendar_id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     */
    private $attendanceCalendarId;



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
}
