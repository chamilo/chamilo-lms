<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Chamilo\CoreBundle\Repository\ConferenceActivityRepository;
use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * Conference Activity entity.
 */
#[ORM\Table(name: 'conference_activity')]
#[ORM\Entity(repositoryClass: ConferenceActivityRepository::class)]
class ConferenceActivity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    protected int $id;

    #[ORM\ManyToOne(targetEntity: ConferenceMeeting::class)]
    #[ORM\JoinColumn(name: 'meeting_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected ?ConferenceMeeting $meeting = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'participant_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected ?User $participant = null;

    #[ORM\Column(name: 'in_at', type: 'datetime', nullable: true)]
    protected ?DateTime $inAt = null;

    #[ORM\Column(name: 'out_at', type: 'datetime', nullable: true)]
    protected ?DateTime $outAt = null;

    #[ORM\Column(name: 'close', type: 'boolean')]
    protected bool $close = false;

    #[ORM\Column(name: 'type', type: 'string', length: 50)]
    protected string $type = '';

    #[ORM\Column(name: 'event', type: 'string', length: 255)]
    protected string $event = '';

    #[ORM\Column(name: 'activity_data', type: 'text', nullable: true)]
    protected ?string $activityData = null;

    #[ORM\Column(name: 'signature_file', type: 'string', length: 255, nullable: true)]
    protected ?string $signatureFile = null;

    #[ORM\Column(name: 'signed_at', type: 'datetime', nullable: true)]
    protected ?DateTime $signedAt = null;

    public function __construct()
    {
        $this->close = false;
        $this->type = '';
        $this->event = '';
        $this->activityData = null;
        $this->signatureFile = null;
        $this->inAt = new DateTime();
        $this->outAt = null;
        $this->signedAt = null;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getMeeting(): ?ConferenceMeeting
    {
        return $this->meeting;
    }

    public function setMeeting(?ConferenceMeeting $meeting): self
    {
        $this->meeting = $meeting;

        return $this;
    }

    public function getParticipant(): ?User
    {
        return $this->participant;
    }

    public function setParticipant(?User $participant): self
    {
        $this->participant = $participant;

        return $this;
    }

    public function getInAt(): ?DateTime
    {
        return $this->inAt;
    }

    public function setInAt(?DateTime $inAt): self
    {
        $this->inAt = $inAt;

        return $this;
    }

    public function getOutAt(): ?DateTime
    {
        return $this->outAt;
    }

    public function setOutAt(?DateTime $outAt): self
    {
        $this->outAt = $outAt;

        return $this;
    }

    public function isClose(): bool
    {
        return $this->close;
    }

    public function setClose(bool $close): self
    {
        $this->close = $close;

        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getEvent(): string
    {
        return $this->event;
    }

    public function setEvent(string $event): self
    {
        $this->event = $event;

        return $this;
    }

    public function getActivityData(): ?string
    {
        return $this->activityData;
    }

    public function setActivityData(?string $activityData): self
    {
        $this->activityData = $activityData;

        return $this;
    }

    public function getSignatureFile(): ?string
    {
        return $this->signatureFile;
    }

    public function setSignatureFile(?string $signatureFile): self
    {
        $this->signatureFile = $signatureFile;

        return $this;
    }

    public function getSignedAt(): ?DateTime
    {
        return $this->signedAt;
    }

    public function setSignedAt(?DateTime $signedAt): self
    {
        $this->signedAt = $signedAt;

        return $this;
    }
}
