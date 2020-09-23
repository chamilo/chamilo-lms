<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Traits\UserTrait;
use Doctrine\ORM\Mapping as ORM;

/**
 * CAttendanceSheet.
 *
 * @ORM\Table(
 *  name="c_attendance_sheet",
 *  indexes={
 *      @ORM\Index(name="course", columns={"c_id"}),
 *      @ORM\Index(name="user", columns={"user_id"}),
 *      @ORM\Index(name="presence", columns={"presence"})
 *  }
 * )
 * @ORM\Entity
 */
class CAttendanceSheet
{
    use UserTrait;

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
     * @ORM\Column(name="c_id", type="integer")
     */
    protected $cId;

    /**
     * @var bool
     *
     * @ORM\Column(name="presence", type="boolean", nullable=false)
     */
    protected $presence;

    /**
     * @var User
     * @ORM\ManyToOne (
     *    targetEntity="Chamilo\CoreBundle\Entity\User",
     *    inversedBy="cAttendanceSheets"
     * )
     * @ORM\JoinColumn(
     *    name="user_id",
     *    referencedColumnName="id",
     *    onDelete="CASCADE"
     * )
     */
    protected $user;

    /**
     * @var int
     *
     * @ORM\Column(name="attendance_calendar_id", type="integer")
     */
    protected $attendanceCalendarId;

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

    /**
     * Set cId.
     *
     * @param int $cId
     *
     * @return CAttendanceSheet
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
     * Set attendanceCalendarId.
     *
     * @param int $attendanceCalendarId
     *
     * @return CAttendanceSheet
     */
    public function setAttendanceCalendarId($attendanceCalendarId)
    {
        $this->attendanceCalendarId = $attendanceCalendarId;

        return $this;
    }

    /**
     * Get attendanceCalendarId.
     *
     * @return int
     */
    public function getAttendanceCalendarId()
    {
        return $this->attendanceCalendarId;
    }
}
