<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Chamilo\CoreBundle\Repository\ConferenceRecordingRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Conference Recording entity.
 */
#[ORM\Table(name: 'conference_recording')]
#[ORM\Entity(repositoryClass: ConferenceRecordingRepository::class)]
class ConferenceRecording
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    protected int $id;

    #[ORM\ManyToOne(targetEntity: ConferenceMeeting::class)]
    #[ORM\JoinColumn(name: 'meeting_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected ?ConferenceMeeting $meeting = null;

    #[ORM\Column(name: 'format_type', type: 'string', length: 50)]
    protected string $formatType = '';

    #[ORM\Column(name: 'resource_url', type: 'string', length: 255)]
    protected string $resourceUrl = '';

    public function __construct()
    {
        $this->formatType = '';
        $this->resourceUrl = '';
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

    public function getFormatType(): string
    {
        return $this->formatType;
    }

    public function setFormatType(string $formatType): self
    {
        $this->formatType = $formatType;

        return $this;
    }

    public function getResourceUrl(): string
    {
        return $this->resourceUrl;
    }

    public function setResourceUrl(string $resourceUrl): self
    {
        $this->resourceUrl = $resourceUrl;

        return $this;
    }
}
