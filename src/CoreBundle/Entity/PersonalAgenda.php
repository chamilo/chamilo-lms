<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Entity;

use Chamilo\CoreBundle\Traits\UserTrait;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * PersonalAgenda.
 */
#[ORM\Table(name: 'personal_agenda')]
#[ORM\Index(columns: ['user'], name: 'idx_personal_agenda_user')]
#[ORM\Index(columns: ['parent_event_id'], name: 'idx_personal_agenda_parent')]
#[ORM\Entity]
class PersonalAgenda
{
    use UserTrait;

    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    protected ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'personalAgendas')]
    #[ORM\JoinColumn(name: 'user', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected User $user;

    #[Assert\NotBlank]
    #[ORM\Column(name: 'title', type: 'text', nullable: true)]
    protected ?string $title = null;

    #[ORM\Column(name: 'text', type: 'text', nullable: true)]
    protected ?string $text = null;

    #[ORM\Column(name: 'date', type: 'datetime', nullable: true)]
    protected ?DateTime $date = null;

    #[ORM\Column(name: 'enddate', type: 'datetime', nullable: true)]
    protected ?DateTime $endDate = null;

    #[ORM\Column(name: 'parent_event_id', type: 'integer', nullable: true)]
    protected ?int $parentEventId = null;

    #[ORM\Column(name: 'all_day', type: 'integer', nullable: false)]
    protected int $allDay;

    #[ORM\Column(name: 'color', type: 'string', length: 20, nullable: true)]
    protected ?string $color = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getText(): string
    {
        return $this->text;
    }

    public function setText(string $text): self
    {
        $this->text = $text;

        return $this;
    }

    public function getDate(): ?DateTime
    {
        return $this->date;
    }

    public function setDate(DateTime $date): self
    {
        $this->date = $date;

        return $this;
    }

    public function getEndDate(): ?DateTime
    {
        return $this->endDate;
    }

    public function setEndDate(DateTime $value): self
    {
        $this->endDate = $value;

        return $this;
    }

    public function getParentEventId(): ?int
    {
        return $this->parentEventId;
    }

    public function setParentEventId(int $parentEventId): self
    {
        $this->parentEventId = $parentEventId;

        return $this;
    }

    public function getAllDay(): int
    {
        return $this->allDay;
    }

    public function setAllDay(int $allDay): self
    {
        $this->allDay = $allDay;

        return $this;
    }

    public function getColor(): ?string
    {
        return $this->color;
    }

    public function setColor(string $color): self
    {
        $this->color = $color;

        return $this;
    }
}
