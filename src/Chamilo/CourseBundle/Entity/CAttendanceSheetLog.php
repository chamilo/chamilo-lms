<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CAttendanceSheetLog.
 *
 * @ORM\Table(
 *  name="c_attendance_sheet_log",
 *  indexes={
 *      @ORM\Index(name="course", columns={"c_id"})
 *  }
 * )
 * @ORM\Entity
 */
class CAttendanceSheetLog
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
     * @ORM\Column(name="attendance_id", type="integer", nullable=false)
     */
    protected $attendanceId;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="lastedit_date", type="datetime", nullable=false)
     */
    protected $lasteditDate;

    /**
     * @var string
     *
     * @ORM\Column(name="lastedit_type", type="string", length=200, nullable=false)
     */
    protected $lasteditType;

    /**
     * @var int
     *
     * @ORM\Column(name="lastedit_user_id", type="integer", nullable=false)
     */
    protected $lasteditUserId;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="calendar_date_value", type="datetime", nullable=true)
     */
    protected $calendarDateValue;

    /**
     * Set attendanceId.
     *
     * @param int $attendanceId
     *
     * @return CAttendanceSheetLog
     */
    public function setAttendanceId($attendanceId)
    {
        $this->attendanceId = $attendanceId;

        return $this;
    }

    /**
     * Get attendanceId.
     *
     * @return int
     */
    public function getAttendanceId()
    {
        return $this->attendanceId;
    }

    /**
     * Set lasteditDate.
     *
     * @param \DateTime $lasteditDate
     *
     * @return CAttendanceSheetLog
     */
    public function setLasteditDate($lasteditDate)
    {
        $this->lasteditDate = $lasteditDate;

        return $this;
    }

    /**
     * Get lasteditDate.
     *
     * @return \DateTime
     */
    public function getLasteditDate()
    {
        return $this->lasteditDate;
    }

    /**
     * Set lasteditType.
     *
     * @param string $lasteditType
     *
     * @return CAttendanceSheetLog
     */
    public function setLasteditType($lasteditType)
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

    /**
     * Set lasteditUserId.
     *
     * @param int $lasteditUserId
     *
     * @return CAttendanceSheetLog
     */
    public function setLasteditUserId($lasteditUserId)
    {
        $this->lasteditUserId = $lasteditUserId;

        return $this;
    }

    /**
     * Get lasteditUserId.
     *
     * @return int
     */
    public function getLasteditUserId()
    {
        return $this->lasteditUserId;
    }

    /**
     * Set calendarDateValue.
     *
     * @param \DateTime $calendarDateValue
     *
     * @return CAttendanceSheetLog
     */
    public function setCalendarDateValue($calendarDateValue)
    {
        $this->calendarDateValue = $calendarDateValue;

        return $this;
    }

    /**
     * Get calendarDateValue.
     *
     * @return \DateTime
     */
    public function getCalendarDateValue()
    {
        return $this->calendarDateValue;
    }

    /**
     * Set id.
     *
     * @param int $id
     *
     * @return CAttendanceSheetLog
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
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

    /**
     * Set cId.
     *
     * @param int $cId
     *
     * @return CAttendanceSheetLog
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
}
