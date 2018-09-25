<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CAttendanceCalendarRelGroup.
 *
 * @ORM\Table(
 *  name="c_attendance_calendar_rel_group",
 *  indexes={
 *      @ORM\Index(name="course", columns={"c_id"}),
 *      @ORM\Index(name="group", columns={"group_id"})
 *  }
 * )
 * @ORM\Entity
 */
class CAttendanceCalendarRelGroup
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
     * @ORM\Column(name="id", type="integer", nullable=true)
     */
    protected $id;

    /**
     * @var int
     *
     * @ORM\Column(name="c_id", type="integer")
     */
    protected $cId;

    /**
     * @var int
     *
     * @ORM\Column(name="group_id", type="integer", nullable=false)
     */
    protected $groupId;

    /**
     * @var int
     *
     * @ORM\Column(name="calendar_id", type="integer", nullable=false)
     */
    protected $calendarId;

    /**
     * Set cId.
     *
     * @param int $cId
     *
     * @return CAttendanceCalendarRelGroup
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
     * Set groupId.
     *
     * @param int $groupId
     *
     * @return CAttendanceCalendarRelGroup
     */
    public function setGroupId($groupId)
    {
        $this->groupId = $groupId;

        return $this;
    }

    /**
     * Get groupId.
     *
     * @return int
     */
    public function getGroupId()
    {
        return $this->groupId;
    }

    /**
     * Set calendarId.
     *
     * @param int $calendarId
     *
     * @return CAttendanceCalendarRelGroup
     */
    public function setCalendarId($calendarId)
    {
        $this->calendarId = $calendarId;

        return $this;
    }

    /**
     * Get calendarId.
     *
     * @return int
     */
    public function getCalendarId()
    {
        return $this->calendarId;
    }

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }
}
