<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use Chamilo\CourseBundle\Repository\CAttendanceCalendarRelGroupRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * CAttendanceCalendarRelGroup.
 */
#[ORM\Table(name: 'c_attendance_calendar_rel_group')]
#[ORM\Entity(repositoryClass: CAttendanceCalendarRelGroupRepository::class)]
class CAttendanceCalendarRelGroup
{
    #[ORM\Column(name: 'iid', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    protected ?int $iid = null;

    #[ORM\ManyToOne(targetEntity: CGroup::class)]
    #[ORM\JoinColumn(name: 'group_id', referencedColumnName: 'iid')]
    protected CGroup $group;

    #[ORM\ManyToOne(targetEntity: CAttendanceCalendar::class)]
    #[ORM\JoinColumn(name: 'calendar_id', referencedColumnName: 'iid')]
    protected CAttendanceCalendar $attendanceCalendar;

    public function getIid(): ?int
    {
        return $this->iid;
    }

    public function getGroup(): CGroup
    {
        return $this->group;
    }

    public function setGroup(CGroup $group): self
    {
        $this->group = $group;

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
