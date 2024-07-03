<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

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
     * @var int
     *
     * @ORM\Column(name="user_id", type="integer")
     */
    protected $userId;

    /**
     * @var int
     *
     * @ORM\Column(name="attendance_calendar_id", type="integer")
     */
    protected $attendanceCalendarId;

    /**
     * @var string
     *
     * ORM\Column(name="signature", type="string", nullable=true)
     */
    protected $signature;

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
     * Set userId.
     *
     * @param int $userId
     *
     * @return CAttendanceSheet
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;

        return $this;
    }

    /**
     * Get userId.
     *
     * @return int
     */
    public function getUserId()
    {
        return $this->userId;
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

    /**
     * @return int
     */
    public function getIid()
    {
        return $this->iid;
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
}
