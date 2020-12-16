<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use Chamilo\CoreBundle\Entity\AbstractResource;
use Chamilo\CoreBundle\Entity\ResourceInterface;
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
class CThematicAdvance //extends AbstractResource implements ResourceInterface
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
     * @var CThematic
     *
     * @ORM\ManyToOne(targetEntity="Chamilo\CourseBundle\Entity\CThematic")
     * @ORM\JoinColumn(name="thematic_id", referencedColumnName="iid")
     */
    protected $thematic;

    /**
     * @var CAttendance
     *
     * @ORM\ManyToOne(targetEntity="Chamilo\CourseBundle\Entity\CAttendance")
     * @ORM\JoinColumn(name="attendance_id", referencedColumnName="iid")
     */
    protected $attendance;

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

    public function __construct()
    {
        $this->doneAdvance = 0;
        $this->duration = 1;
    }

    public function __toString(): string
    {
        return (string) $this->getIid();
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

    public function getThematic(): CThematic
    {
        return $this->thematic;
    }

    public function setThematic(CThematic $thematic): self
    {
        $this->thematic = $thematic;

        return $this;
    }

    public function getAttendance(): ?CAttendance
    {
        return $this->attendance;
    }

    public function setAttendance(CAttendance $attendance): self
    {
        $this->attendance = $attendance;

        return $this;
    }

    /**
     * @return int
     */
    public function getIid()
    {
        return $this->iid;
    }

    /*
    public function getResourceIdentifier(): int
    {
        return $this->getIid();
    }

    public function getResourceName(): string
    {
        return (string) $this->getContent();
    }

    public function setResourceName(string $name): self
    {
        return $this->setContent($name);
    }*/
}
