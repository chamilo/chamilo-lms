<?php

namespace ChamiloLMS\CourseBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CAttendanceCalendar
 *
 * @ORM\Table(name="c_attendance_calendar", uniqueConstraints={@ORM\UniqueConstraint(name="c_id", columns={"c_id", "id"})}, indexes={@ORM\Index(name="attendance_id", columns={"attendance_id"}), @ORM\Index(name="done_attendance", columns={"done_attendance"})})
 * @ORM\Entity
 */
class CAttendanceCalendar
{
    /**
     * @var integer
     *
     * @ORM\Column(name="iid", type="integer", precision=0, scale=0, nullable=false, unique=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
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
     * @ORM\Column(name="id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $id;

    /**
     * @var integer
     *
     * @ORM\Column(name="attendance_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $attendanceId;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date_time", type="datetime", precision=0, scale=0, nullable=false, unique=false)
     */
    private $dateTime;

    /**
     * @var boolean
     *
     * @ORM\Column(name="done_attendance", type="boolean", precision=0, scale=0, nullable=false, unique=false)
     */
    private $doneAttendance;


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
     * @return CAttendanceCalendar
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
     * @return CAttendanceCalendar
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
     * @return CAttendanceCalendar
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
     * @return CAttendanceCalendar
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
     * @return CAttendanceCalendar
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
