<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CAttendance.
 *
 * @ORM\Table(
 *  name="c_attendance",
 *  indexes={
 *      @ORM\Index(name="course", columns={"c_id"}),
 *      @ORM\Index(name="session_id", columns={"session_id"}),
 *      @ORM\Index(name="active", columns={"active"})
 *  }
 * )
 * @ORM\Entity
 */
class CAttendance
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
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=true)
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="text", nullable=false)
     */
    protected $name;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="text", nullable=true)
     */
    protected $description;

    /**
     * @var bool
     *
     * @ORM\Column(name="active", type="boolean", nullable=false)
     */
    protected $active;

    /**
     * @var string
     *
     * @ORM\Column(name="attendance_qualify_title", type="string", length=255, nullable=true)
     */
    protected $attendanceQualifyTitle;

    /**
     * @var int
     *
     * @ORM\Column(name="attendance_qualify_max", type="integer", nullable=false)
     */
    protected $attendanceQualifyMax;

    /**
     * @var float
     *
     * @ORM\Column(name="attendance_weight", type="float", precision=6, scale=2, nullable=false)
     */
    protected $attendanceWeight;

    /**
     * @var int
     *
     * @ORM\Column(name="session_id", type="integer", nullable=false)
     */
    protected $sessionId;

    /**
     * @var int
     *
     * @ORM\Column(name="locked", type="integer", nullable=false)
     */
    protected $locked;

    /**
     * Set name.
     *
     * @param string $name
     *
     * @return CAttendance
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set description.
     *
     * @param string $description
     *
     * @return CAttendance
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description.
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set active.
     *
     * @param bool $active
     *
     * @return CAttendance
     */
    public function setActive($active)
    {
        $this->active = $active;

        return $this;
    }

    /**
     * Get active.
     *
     * @return bool
     */
    public function getActive()
    {
        return $this->active;
    }

    /**
     * Set attendanceQualifyTitle.
     *
     * @param string $attendanceQualifyTitle
     *
     * @return CAttendance
     */
    public function setAttendanceQualifyTitle($attendanceQualifyTitle)
    {
        $this->attendanceQualifyTitle = $attendanceQualifyTitle;

        return $this;
    }

    /**
     * Get attendanceQualifyTitle.
     *
     * @return string
     */
    public function getAttendanceQualifyTitle()
    {
        return $this->attendanceQualifyTitle;
    }

    /**
     * Set attendanceQualifyMax.
     *
     * @param int $attendanceQualifyMax
     *
     * @return CAttendance
     */
    public function setAttendanceQualifyMax($attendanceQualifyMax)
    {
        $this->attendanceQualifyMax = $attendanceQualifyMax;

        return $this;
    }

    /**
     * Get attendanceQualifyMax.
     *
     * @return int
     */
    public function getAttendanceQualifyMax()
    {
        return $this->attendanceQualifyMax;
    }

    /**
     * Set attendanceWeight.
     *
     * @param float $attendanceWeight
     *
     * @return CAttendance
     */
    public function setAttendanceWeight($attendanceWeight)
    {
        $this->attendanceWeight = $attendanceWeight;

        return $this;
    }

    /**
     * Get attendanceWeight.
     *
     * @return float
     */
    public function getAttendanceWeight()
    {
        return $this->attendanceWeight;
    }

    /**
     * Set sessionId.
     *
     * @param int $sessionId
     *
     * @return CAttendance
     */
    public function setSessionId($sessionId)
    {
        $this->sessionId = $sessionId;

        return $this;
    }

    /**
     * Get sessionId.
     *
     * @return int
     */
    public function getSessionId()
    {
        return $this->sessionId;
    }

    /**
     * Set locked.
     *
     * @param int $locked
     *
     * @return CAttendance
     */
    public function setLocked($locked)
    {
        $this->locked = $locked;

        return $this;
    }

    /**
     * Get locked.
     *
     * @return int
     */
    public function getLocked()
    {
        return $this->locked;
    }

    /**
     * Set id.
     *
     * @param int $id
     *
     * @return CAttendance
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
     * @return CAttendance
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
