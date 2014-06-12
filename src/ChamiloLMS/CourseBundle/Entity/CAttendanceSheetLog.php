<?php

namespace ChamiloLMS\CourseBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CAttendanceSheetLog
 *
 * @ORM\Table(name="c_attendance_sheet_log", uniqueConstraints={@ORM\UniqueConstraint(name="c_id", columns={"c_id", "id"})})
 * @ORM\Entity
 */
class CAttendanceSheetLog
{
    /**
     * @var integer
     *
     * @ORM\Column(name="iid", type="integer", precision=0, scale=0, nullable=false, unique=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $iid;

    /**
     * @var integer
     *
     * @ORM\Column(name="c_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $cId;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $id;

    /**
     * @var integer
     *
     * @ORM\Column(name="attendance_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $attendanceId;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="lastedit_date", type="datetime", precision=0, scale=0, nullable=false, unique=false)
     */
    private $lasteditDate;

    /**
     * @var string
     *
     * @ORM\Column(name="lastedit_type", type="string", length=200, precision=0, scale=0, nullable=false, unique=false)
     */
    private $lasteditType;

    /**
     * @var integer
     *
     * @ORM\Column(name="lastedit_user_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $lasteditUserId;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="calendar_date_value", type="datetime", precision=0, scale=0, nullable=true, unique=false)
     */
    private $calendarDateValue;


    /**
     * Get iid
     *
     * @return integer 
     */
    public function getIid()
    {
        return $this->iid;
    }

    /**
     * Set cId
     *
     * @param integer $cId
     * @return CAttendanceSheetLog
     */
    public function setCId($cId)
    {
        $this->cId = $cId;

        return $this;
    }

    /**
     * Get cId
     *
     * @return integer 
     */
    public function getCId()
    {
        return $this->cId;
    }

    /**
     * Set id
     *
     * @param integer $id
     * @return CAttendanceSheetLog
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set attendanceId
     *
     * @param integer $attendanceId
     * @return CAttendanceSheetLog
     */
    public function setAttendanceId($attendanceId)
    {
        $this->attendanceId = $attendanceId;

        return $this;
    }

    /**
     * Get attendanceId
     *
     * @return integer 
     */
    public function getAttendanceId()
    {
        return $this->attendanceId;
    }

    /**
     * Set lasteditDate
     *
     * @param \DateTime $lasteditDate
     * @return CAttendanceSheetLog
     */
    public function setLasteditDate($lasteditDate)
    {
        $this->lasteditDate = $lasteditDate;

        return $this;
    }

    /**
     * Get lasteditDate
     *
     * @return \DateTime 
     */
    public function getLasteditDate()
    {
        return $this->lasteditDate;
    }

    /**
     * Set lasteditType
     *
     * @param string $lasteditType
     * @return CAttendanceSheetLog
     */
    public function setLasteditType($lasteditType)
    {
        $this->lasteditType = $lasteditType;

        return $this;
    }

    /**
     * Get lasteditType
     *
     * @return string 
     */
    public function getLasteditType()
    {
        return $this->lasteditType;
    }

    /**
     * Set lasteditUserId
     *
     * @param integer $lasteditUserId
     * @return CAttendanceSheetLog
     */
    public function setLasteditUserId($lasteditUserId)
    {
        $this->lasteditUserId = $lasteditUserId;

        return $this;
    }

    /**
     * Get lasteditUserId
     *
     * @return integer 
     */
    public function getLasteditUserId()
    {
        return $this->lasteditUserId;
    }

    /**
     * Set calendarDateValue
     *
     * @param \DateTime $calendarDateValue
     * @return CAttendanceSheetLog
     */
    public function setCalendarDateValue($calendarDateValue)
    {
        $this->calendarDateValue = $calendarDateValue;

        return $this;
    }

    /**
     * Get calendarDateValue
     *
     * @return \DateTime 
     */
    public function getCalendarDateValue()
    {
        return $this->calendarDateValue;
    }
}
