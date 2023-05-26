<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use Chamilo\CoreBundle\Entity\User;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Table(name: 'c_attendance_sheet')]
#[ORM\Index(name: 'presence', columns: ['presence'])]
#[ORM\Entity]
class CAttendanceSheet
{
    #[ORM\Column(name: 'iid', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    protected int $iid;

    #[Assert\NotNull]
    #[ORM\Column(name: 'presence', type: 'boolean', nullable: false)]
    protected bool $presence;

    #[ORM\ManyToOne(targetEntity: \Chamilo\CoreBundle\Entity\User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id')]
    protected User $user;

    #[ORM\ManyToOne(targetEntity: \Chamilo\CourseBundle\Entity\CAttendanceCalendar::class, inversedBy: 'sheets')]
    #[ORM\JoinColumn(name: 'attendance_calendar_id', referencedColumnName: 'iid', onDelete: 'CASCADE')]
    protected CAttendanceCalendar $attendanceCalendar;

    #[ORM\Column(name: 'signature', type: 'string', nullable: false)]
    protected $signature;

    public function setPresence(bool $presence): self
    {
        $this->presence = $presence;

        return $this;
    }

    public function getPresence(): bool
    {
        return $this->presence;
    }

    /**
     * Set signature.
     *
     * @return CAttendanceSheet
     */
    public function setSignature(string $signature)
    {
        $this->signature = $signature;

        return $this;
    }

    /**
     * Get signature.
     *
     * @return string
     */
    public function getSignature()
    {
        return $this->signature;
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
