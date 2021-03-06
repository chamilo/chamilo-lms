<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use Chamilo\CoreBundle\Entity\AbstractResource;
use Chamilo\CoreBundle\Entity\ResourceInterface;
use Chamilo\CoreBundle\Entity\Room;
use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * CThematicAdvance.
 *
 * @ORM\Table(
 *     name="c_thematic_advance",
 *     indexes={
 *     }
 * )
 * @ORM\Entity
 */
class CThematicAdvance //extends AbstractResource implements ResourceInterface
{
    /**
     * @ORM\Column(name="iid", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected int $iid;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CourseBundle\Entity\CThematic")
     * @ORM\JoinColumn(name="thematic_id", referencedColumnName="iid")
     */
    protected CThematic $thematic;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CourseBundle\Entity\CAttendance")
     * @ORM\JoinColumn(name="attendance_id", referencedColumnName="iid")
     */
    protected CAttendance $attendance;

    /**
     * @ORM\Column(name="content", type="text", nullable=true)
     */
    protected ?string $content = null;

    /**
     * @ORM\Column(name="start_date", type="datetime", nullable=false)
     */
    protected DateTime $startDate;

    /**
     * @ORM\Column(name="duration", type="integer", nullable=false)
     */
    protected int $duration;

    /**
     * @ORM\Column(name="done_advance", type="boolean", nullable=false)
     */
    protected bool $doneAdvance;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\Room")
     * @ORM\JoinColumn(name="room_id", referencedColumnName="id")
     */
    protected ?Room $room = null;

    public function __construct()
    {
        $this->doneAdvance = false;
        $this->duration = 1;
    }

    public function __toString(): string
    {
        return (string) $this->getIid();
    }

    /**
     * Set content.
     *
     * @return CThematicAdvance
     */
    public function setContent(string $content)
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
     * @return CThematicAdvance
     */
    public function setStartDate(DateTime $startDate)
    {
        $this->startDate = $startDate;

        return $this;
    }

    /**
     * Get startDate.
     *
     * @return DateTime
     */
    public function getStartDate()
    {
        return $this->startDate;
    }

    /**
     * Set duration.
     *
     * @return CThematicAdvance
     */
    public function setDuration(int $duration)
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
     * @return CThematicAdvance
     */
    public function setDoneAdvance(bool $doneAdvance)
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
