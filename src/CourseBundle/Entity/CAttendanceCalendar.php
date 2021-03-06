<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * CAttendanceCalendar.
 *
 * @ORM\Table(
 *     name="c_attendance_calendar",
 *     indexes={
 *         @ORM\Index(name="done_attendance", columns={"done_attendance"})
 *     }
 * )
 * @ORM\Entity
 */
class CAttendanceCalendar
{
    /**
     * @ORM\Column(name="iid", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected int $iid;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CourseBundle\Entity\CAttendance")
     * @ORM\JoinColumn(name="attendance_id", referencedColumnName="iid")
     */
    protected CAttendance $attendance;

    /**
     * @ORM\Column(name="date_time", type="datetime", nullable=false)
     */
    protected DateTime $dateTime;

    /**
     * @ORM\Column(name="done_attendance", type="boolean", nullable=false)
     */
    protected bool $doneAttendance;

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

    public function setDateTime(DateTime $dateTime): self
    {
        $this->dateTime = $dateTime;

        return $this;
    }

    /**
     * Get dateTime.
     *
     * @return DateTime
     */
    public function getDateTime()
    {
        return $this->dateTime;
    }

    /**
     * Set doneAttendance.
     *
     * @return CAttendanceCalendar
     */
    public function setDoneAttendance(bool $doneAttendance)
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
