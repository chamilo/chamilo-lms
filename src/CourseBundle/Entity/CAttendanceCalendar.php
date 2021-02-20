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
     * @ORM\ManyToOne(targetEntity="Chamilo\CourseBundle\Entity\CAttendance")
     * @ORM\JoinColumn(name="attendance_id", referencedColumnName="iid")
     */
    protected CAttendance $attendance;

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

    public function getIid(): int
    {
        return $this->iid;
    }

    public function getAttendance(): CAttendance
    {
        return $this->attendance;
    }

    public function setAttendance(CAttendance $attendance): self
    {
        $this->attendance = $attendance;

        return $this;
    }

    /**
     * Set dateTime.
     *
     * @param \DateTime $dateTime
     */
    public function setDateTime($dateTime): self
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
}
