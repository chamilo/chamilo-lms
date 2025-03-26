<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\Zoom;

use Chamilo\CoreBundle\Entity\User;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Stringable;

#[ORM\Entity]
#[ORM\Table(name: 'plugin_zoom_meeting_activity')]
#[ORM\HasLifecycleCallbacks]
class MeetingActivity implements Stringable
{
    #[ORM\Column(type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    protected ?int $id;

    #[ORM\ManyToOne(targetEntity: Meeting::class, inversedBy: 'activities')]
    #[ORM\JoinColumn(name: "meeting_id")]
    protected ?Meeting $meeting;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id')]
    protected ?User $user;

    #[ORM\Column(name: 'name', type: 'string', length: 255)]
    protected string $name;

    #[ORM\Column(name: 'type', type: 'string', length: 255)]
    protected string $type;

    #[ORM\Column(name: 'event', type: 'text', nullable: true)]
    protected ?string $event;

    #[ORM\Column(name: 'created_at', type: 'datetime')]
    protected DateTime $createdAt;

    public function __construct()
    {
        $this->createdAt = new DateTime();
    }

    public function __toString()
    {
        return sprintf('Activity %d', $this->id);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMeeting(): ?Meeting
    {
        return $this->meeting;
    }

    public function setMeeting(Meeting $meeting): static
    {
        $this->meeting = $meeting;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    public function getEvent(): ?string
    {
        return $this->event;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getEventDecoded()
    {
        if (!empty($this->event)) {
            return json_decode($this->event);
        }

        return '';
    }

    public function setEvent(string $event): static
    {
        $this->event = $event;

        return $this;
    }
}
