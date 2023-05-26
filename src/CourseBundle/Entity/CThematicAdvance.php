<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use Chamilo\CoreBundle\Entity\AbstractResource;
use Chamilo\CoreBundle\Entity\ResourceInterface;
use Chamilo\CoreBundle\Entity\Room;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Stringable;

#[ORM\Table(name: 'c_thematic_advance')]
#[ORM\Entity]
class CThematicAdvance implements Stringable //extends AbstractResource implements ResourceInterface
{
    #[ORM\Column(name: 'iid', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    protected int $iid;

    #[ORM\ManyToOne(targetEntity: CThematic::class, inversedBy: 'advances')]
    #[ORM\JoinColumn(name: 'thematic_id', referencedColumnName: 'iid')]
    protected CThematic $thematic;

    #[ORM\ManyToOne(targetEntity: CAttendance::class)]
    #[ORM\JoinColumn(name: 'attendance_id', referencedColumnName: 'iid', onDelete: 'CASCADE')]
    protected CAttendance $attendance;

    #[ORM\Column(name: 'content', type: 'text', nullable: true)]
    protected ?string $content = null;

    #[ORM\Column(name: 'start_date', type: 'datetime', nullable: false)]
    protected DateTime $startDate;

    #[ORM\Column(name: 'duration', type: 'integer', nullable: false)]
    protected int $duration;

    #[ORM\Column(name: 'done_advance', type: 'boolean', nullable: false)]
    protected bool $doneAdvance;

    #[ORM\ManyToOne(targetEntity: Room::class)]
    #[ORM\JoinColumn(name: 'room_id', referencedColumnName: 'id')]
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

    public function setContent(string $content): self
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

    public function setStartDate(DateTime $startDate): self
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

    public function setDuration(int $duration): self
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

    public function setDoneAdvance(bool $doneAdvance): self
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

    public function getRoom(): ?Room
    {
        return $this->room;
    }

    public function setRoom(Room $room): self
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
