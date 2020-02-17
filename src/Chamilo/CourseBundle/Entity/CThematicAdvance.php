<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use Chamilo\CoreBundle\Entity\Room;
use Doctrine\ORM\Mapping as ORM;

/**
 * CThematicAdvance.
 *
 * @ORM\Table(
 *  name="c_thematic_advance",
 *  indexes={
 *      @ORM\Index(name="course", columns={"c_id"}),
 *      @ORM\Index(name="thematic_id", columns={"thematic_id"})
 *  }
 * )
 * @ORM\Entity
 */
class CThematicAdvance
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
     * @var int
     *
     * @ORM\Column(name="thematic_id", type="integer", nullable=false)
     */
    protected $thematicId;

    /**
     * @var int
     *
     * @ORM\Column(name="attendance_id", type="integer", nullable=false)
     */
    protected $attendanceId;

    /**
     * @var string
     *
     * @ORM\Column(name="content", type="text", nullable=true)
     */
    protected $content;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="start_date", type="datetime", nullable=false)
     */
    protected $startDate;

    /**
     * @var int
     *
     * @ORM\Column(name="duration", type="integer", nullable=false)
     */
    protected $duration;

    /**
     * @var bool
     *
     * @ORM\Column(name="done_advance", type="boolean", nullable=false)
     */
    protected $doneAdvance;

    /**
     * @var Room
     *
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\Room")
     * @ORM\JoinColumn(name="room_id", referencedColumnName="id")
     */
    protected $room;

    /**
     * Set thematicId.
     *
     * @param int $thematicId
     *
     * @return CThematicAdvance
     */
    public function setThematicId($thematicId)
    {
        $this->thematicId = $thematicId;

        return $this;
    }

    /**
     * Get thematicId.
     *
     * @return int
     */
    public function getThematicId()
    {
        return $this->thematicId;
    }

    /**
     * Set attendanceId.
     *
     * @param int $attendanceId
     *
     * @return CThematicAdvance
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
     * Set content.
     *
     * @param string $content
     *
     * @return CThematicAdvance
     */
    public function setContent($content)
    {
        $this->content = $content;

        return $this;
    }

    /**
     * Get content.
     *
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Set startDate.
     *
     * @param \DateTime $startDate
     *
     * @return CThematicAdvance
     */
    public function setStartDate($startDate)
    {
        $this->startDate = $startDate;

        return $this;
    }

    /**
     * Get startDate.
     *
     * @return \DateTime
     */
    public function getStartDate()
    {
        return $this->startDate;
    }

    /**
     * Set duration.
     *
     * @param int $duration
     *
     * @return CThematicAdvance
     */
    public function setDuration($duration)
    {
        $this->duration = $duration;

        return $this;
    }

    /**
     * Get duration.
     *
     * @return int
     */
    public function getDuration()
    {
        return $this->duration;
    }

    /**
     * Set doneAdvance.
     *
     * @param bool $doneAdvance
     *
     * @return CThematicAdvance
     */
    public function setDoneAdvance($doneAdvance)
    {
        $this->doneAdvance = $doneAdvance;

        return $this;
    }

    /**
     * Get doneAdvance.
     *
     * @return bool
     */
    public function getDoneAdvance()
    {
        return $this->doneAdvance;
    }

    /**
     * Set id.
     *
     * @param int $id
     *
     * @return CThematicAdvance
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
     * @return CThematicAdvance
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
     * @return Room
     */
    public function getRoom()
    {
        return $this->room;
    }

    /**
     * @return $this
     */
    public function setRoom(Room $room)
    {
        $this->room = $room;

        return $this;
    }

    /**
     * @return int
     */
    public function getIid()
    {
        return $this->iid;
    }

    /**
     * @param int $iid
     *
     * @return CThematicAdvance
     */
    public function setIid($iid)
    {
        $this->iid = $iid;

        return $this;
    }
}
