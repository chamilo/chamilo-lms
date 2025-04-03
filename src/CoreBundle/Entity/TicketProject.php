<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * Project.
 */
#[ORM\Table(name: 'ticket_project')]
#[ORM\Entity]
class TicketProject
{
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    protected ?int $id = null;

    #[ORM\Column(name: 'title', type: 'string', length: 255, nullable: false)]
    protected string $title;

    #[ORM\Column(name: 'description', type: 'text', nullable: true)]
    protected ?string $description = null;

    #[ORM\Column(name: 'email', type: 'string', nullable: true)]
    protected ?string $email = null;

    #[ORM\Column(name: 'other_area', type: 'integer', nullable: true)]
    protected ?int $otherArea = null;

    #[ORM\Column(name: 'sys_insert_user_id', type: 'integer')]
    protected int $insertUserId;

    #[ORM\Column(name: 'sys_insert_datetime', type: 'datetime')]
    protected DateTime $insertDateTime;

    #[ORM\Column(name: 'sys_lastedit_user_id', type: 'integer', unique: false, nullable: true)]
    protected ?int $lastEditUserId = null;

    #[ORM\Column(name: 'sys_lastedit_datetime', type: 'datetime', unique: false, nullable: true)]
    protected ?DateTime $lastEditDateTime = null;

    #[ORM\ManyToOne(targetEntity: AccessUrl::class)]
    #[ORM\JoinColumn(name: 'access_url_id', referencedColumnName: 'id', nullable: true)]
    protected ?AccessUrl $accessUrl = null;

    public function __construct()
    {
        $this->insertDateTime = new DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getOtherArea(): int
    {
        return (int) $this->otherArea;
    }

    public function setOtherArea(?int $otherArea): static
    {
        $this->otherArea = $otherArea;

        return $this;
    }

    public function getInsertUserId(): int
    {
        return $this->insertUserId;
    }

    public function setInsertUserId(int $insertUserId): self
    {
        $this->insertUserId = $insertUserId;

        return $this;
    }

    public function getInsertDateTime(): DateTime
    {
        return $this->insertDateTime;
    }

    public function setInsertDateTime(DateTime $insertDateTime): self
    {
        $this->insertDateTime = $insertDateTime;

        return $this;
    }

    public function getLastEditUserId(): ?int
    {
        return $this->lastEditUserId;
    }

    public function setLastEditUserId(int $lastEditUserId): self
    {
        $this->lastEditUserId = $lastEditUserId;

        return $this;
    }

    public function getLastEditDateTime(): ?DateTime
    {
        return $this->lastEditDateTime;
    }

    public function setLastEditDateTime(DateTime $lastEditDateTime): self
    {
        $this->lastEditDateTime = $lastEditDateTime;

        return $this;
    }

    public function getAccessUrl(): ?AccessUrl
    {
        return $this->accessUrl;
    }

    public function setAccessUrl(?AccessUrl $accessUrl): self
    {
        $this->accessUrl = $accessUrl;

        return $this;
    }
}
