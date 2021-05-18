<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
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
     * @ORM\ManyToOne(targetEntity="CAttendance", inversedBy="calendars", cascade={"remove"})
     * @ORM\JoinColumn(name="attendance_id", referencedColumnName="iid", onDelete="CASCADE")
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

    public function setDoneAttendance(bool $doneAttendance): self
    {
        $this->doneAttendance = $doneAttendance;

        return $this;
    }

    public function getDoneAttendance(): bool
    {
        return $this->doneAttendance;
    }
}
