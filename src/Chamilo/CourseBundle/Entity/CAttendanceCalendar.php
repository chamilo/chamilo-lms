<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CAttendanceCalendar.
 *
 * @ORM\Table(
 *  name="c_attendance_calendar",
 *  indexes={
 *      @ORM\Index(name="course", columns={"c_id"}),
 *      @ORM\Index(name="attendance_id", columns={"attendance_id"}),
 *      @ORM\Index(name="done_attendance", columns={"done_attendance"})
 *  }
 * )
 * @ORM\Entity
 */
class CAttendanceCalendar
{
    /**
     * @var int
     *
     * @ORM\Column(name="iid", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected $iid;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=true)
     */
    protected $id;

    /**
     * @var int
     *
     * @ORM\Column(name="c_id", type="integer")
     */
    protected $cId;

    /**
     * @var int
     *
     * @ORM\Column(name="attendance_id", type="integer", nullable=false)
     */
    protected $attendanceId;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date_time", type="datetime", nullable=false)
     */
    protected $dateTime;

    /**
     * @var bool
     *
     * @ORM\Column(name="done_attendance", type="boolean", nullable=false)
     */
    protected $doneAttendance;

    /**
     * @var bool
     *
     * ORM\Column(name="blocked", type="boolean", nullable=true)
     */
    protected $blocked;

    /**
     * Set attendanceId.
     *
     * @param int $attendanceId
     *
     * @return CAttendanceCalendar
     */
    public function setAttendanceId($attendanceId)
    {
        $this->attendanceId = $attendanceId;

        return $this;
    }

    /**
     * Get attendanceId.
     *
     * @return int
     */
    public function getAttendanceId()
    {
        return $this->attendanceId;
    }

    /**
     * Set dateTime.
     *
     * @param \DateTime $dateTime
     *
     * @return CAttendanceCalendar
     */
    public function setDateTime($dateTime)
    {
        $this->dateTime = $dateTime;

        return $this;
    }

    /**
     * Get dateTime.
     *
     * @return \DateTime
     */
    public function getDateTime()
    {
        return $this->dateTime;
    }

    /**
     * Set doneAttendance.
     *
     * @param bool $doneAttendance
     *
     * @return CAttendanceCalendar
     */
    public function setDoneAttendance($doneAttendance)
    {
        $this->doneAttendance = $doneAttendance;

        return $this;
    }

    /**
     * Get doneAttendance.
     *
     * @return bool
     */
    public function getDoneAttendance()
    {
        return $this->doneAttendance;
    }

    /**
     * Set id.
     *
     * @param int $id
     *
     * @return CAttendanceCalendar
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set cId.
     *
     * @param int $cId
     *
     * @return CAttendanceCalendar
     */
    public function setCId($cId)
    {
        $this->cId = $cId;

        return $this;
    }

    /**
     * Get cId.
     *
     * @return int
     */
    public function getCId()
    {
        return $this->cId;
    }

    /**
     * Set blocked.
     *
     * @param bool $blocked
     *
     * @return CAttendanceCalendar
     */
    public function setBlocked($blocked)
    {
        $this->blocked = $blocked;

        return $this;
    }

    /**
     * Get blocked.
     *
     * @return bool
     */
    public function getBlocked()
    {
        return $this->blocked;
    }
}
