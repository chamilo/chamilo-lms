<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use Chamilo\CoreBundle\Entity\User;
use Doctrine\ORM\Mapping as ORM;

/**
 * CAttendanceSheet.
 *
 * @ORM\Table(
 *     name="c_attendance_sheet",
 *     indexes={
 *         @ORM\Index(name="presence", columns={"presence"})
 *     }
 * )
 * @ORM\Entity
 */
class CAttendanceSheet
{
    /**
     * @ORM\Column(name="iid", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected int $iid;

    /**
     * @ORM\Column(name="presence", type="boolean", nullable=false)
     */
    protected bool $presence;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     */
    protected User $user;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CourseBundle\Entity\CAttendanceCalendar")
     * @ORM\JoinColumn(name="attendance_calendar_id", referencedColumnName="iid")
     */
    protected CAttendanceCalendar $attendanceCalendar;

    /**
     * Set presence.
     *
     * @param bool $presence
     *
     * @return CAttendanceSheet
     */
    public function setPresence($presence)
    {
        $this->presence = $presence;

        return $this;
    }

    /**
     * Get presence.
     *
     * @return bool
     */
    public function getPresence()
    {
        return $this->presence;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getAttendanceCalendar(): CAttendanceCalendar
    {
        return $this->attendanceCalendar;
    }

    public function setAttendanceCalendar(CAttendanceCalendar $attendanceCalendar): self
    {
        $this->attendanceCalendar = $attendanceCalendar;

        return $this;
    }
}
