<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use Chamilo\CoreBundle\Entity\User;
use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * CAttendanceSheetLog.
 *
 * @ORM\Table(
 *     name="c_attendance_sheet_log",
 *     indexes={
 *     }
 * )
 * @ORM\Entity
 */
class CAttendanceSheetLog
{
    /**
     * @ORM\Column(name="iid", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected int $iid;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CourseBundle\Entity\CAttendance", inversedBy="logs")
     * @ORM\JoinColumn(name="attendance_id", referencedColumnName="iid", onDelete="CASCADE")
     */
    protected CAttendance $attendance;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\User")
     * @ORM\JoinColumn(name="lastedit_user_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected User $user;

    /**
     * @ORM\Column(name="lastedit_date", type="datetime", nullable=false)
     */
    protected DateTime $lasteditDate;

    /**
     * @ORM\Column(name="lastedit_type", type="string", length=200, nullable=false)
     */
    protected string $lasteditType;

    /**
     * @ORM\Column(name="calendar_date_value", type="datetime", nullable=true)
     */
    protected ?DateTime $calendarDateValue = null;

    public function setLasteditDate(DateTime $lasteditDate): self
    {
        $this->lasteditDate = $lasteditDate;

        return $this;
    }

    public function getLasteditDate(): DateTime
    {
        return $this->lasteditDate;
    }

    public function setLasteditType(string $lasteditType): self
    {
        $this->lasteditType = $lasteditType;

        return $this;
    }

    /**
     * Get lasteditType.
     *
     * @return string
     */
    public function getLasteditType()
    {
        return $this->lasteditType;
    }

    public function setCalendarDateValue(?DateTime $calendarDateValue): self
    {
        $this->calendarDateValue = $calendarDateValue;

        return $this;
    }

    /**
     * Get calendarDateValue.
     *
     * @return DateTime
     */
    public function getCalendarDateValue()
    {
        return $this->calendarDateValue;
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

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;

        return $this;
    }
}
