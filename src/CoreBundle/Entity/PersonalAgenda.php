<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Chamilo\CoreBundle\Traits\UserTrait;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * PersonalAgenda.
 *
 * @ORM\Table(name="personal_agenda", indexes={
 *     @ORM\Index(name="idx_personal_agenda_user", columns={"user"}),
 *     @ORM\Index(name="idx_personal_agenda_parent", columns={"parent_event_id"})
 * })
 * @ORM\Entity
 */
class PersonalAgenda
{
    use UserTrait;

    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected int $id;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\User", inversedBy="personalAgendas")
     * @ORM\JoinColumn(name="user", referencedColumnName="id", onDelete="CASCADE")
     */
    protected User $user;

    /**
     * @Assert\NotBlank()
     *
     * @ORM\Column(name="title", type="text", nullable=true)
     */
    protected ?string $title;

    /**
     * @ORM\Column(name="text", type="text", nullable=true)
     */
    protected ?string $text;

    /**
     * @ORM\Column(name="date", type="datetime", nullable=true)
     */
    protected ?DateTime $date;

    /**
     * @ORM\Column(name="enddate", type="datetime", nullable=true)
     */
    protected ?DateTime $endDate;

    /**
     * @ORM\Column(name="parent_event_id", type="integer", nullable=true)
     */
    protected ?int $parentEventId;

    /**
     * @ORM\Column(name="all_day", type="integer", nullable=false)
     */
    protected int $allDay;

    /**
     * @ORM\Column(name="color", type="string", length=255, nullable=true)
     */
    protected ?string $color;

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title.
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    public function setText(string $text): self
    {
        $this->text = $text;

        return $this;
    }

    public function getText(): string
    {
        return $this->text;
    }

    /**
     * Set date.
     *
     * @param DateTime $date
     */
    public function setDate($date): self
    {
        $this->date = $date;

        return $this;
    }

    /**
     * Get date.
     *
     * @return DateTime
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * Set enddate.
     *
     * @param DateTime $value
     *
     * @return PersonalAgenda
     */
    public function setEndDate($value): self
    {
        $this->endDate = $value;

        return $this;
    }

    /**
     * @return DateTime
     */
    public function getEndDate()
    {
        return $this->endDate;
    }

    /**
     * Set parentEventId.
     *
     * @param int $parentEventId
     *
     * @return PersonalAgenda
     */
    public function setParentEventId($parentEventId): self
    {
        $this->parentEventId = $parentEventId;

        return $this;
    }

    /**
     * Get parentEventId.
     *
     * @return int
     */
    public function getParentEventId()
    {
        return $this->parentEventId;
    }

    /**
     * Set allDay.
     *
     * @param int $allDay
     *
     * @return PersonalAgenda
     */
    public function setAllDay($allDay): self
    {
        $this->allDay = $allDay;

        return $this;
    }

    /**
     * Get allDay.
     *
     * @return int
     */
    public function getAllDay()
    {
        return $this->allDay;
    }

    /**
     * @return string
     */
    public function getColor()
    {
        return $this->color;
    }

    /**
     * @param string $color
     *
     * @return PersonalAgenda
     */
    public function setColor($color): self
    {
        $this->color = $color;

        return $this;
    }
}
