<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * Priority.
 */
#[ORM\Table(name: 'ticket_priority')]
#[ORM\Entity]
class TicketPriority
{
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    protected ?int $id = null;

    #[ORM\Column(name: 'title', type: 'string', length: 255, nullable: false)]
    protected string $title;

    #[ORM\Column(name: 'code', type: 'string', length: 255, nullable: false)]
    protected string $code;

    #[ORM\Column(name: 'description', type: 'text', nullable: true)]
    protected ?string $description = null;

    #[ORM\Column(name: 'color', type: 'string', nullable: false)]
    protected string $color;

    #[ORM\Column(name: 'urgency', type: 'string', nullable: false)]
    protected string $urgency;

    #[ORM\Column(name: 'sys_insert_user_id', type: 'integer')]
    protected int $insertUserId;

    #[ORM\Column(name: 'sys_insert_datetime', type: 'datetime')]
    protected DateTime $insertDateTime;

    #[ORM\Column(name: 'sys_lastedit_user_id', type: 'integer', nullable: true, unique: false)]
    protected ?int $lastEditUserId = null;

    #[ORM\Column(name: 'sys_lastedit_datetime', type: 'datetime', nullable: true, unique: false)]
    protected ?DateTime $lastEditDateTime = null;

    #[ORM\ManyToOne(targetEntity: AccessUrl::class)]
    #[ORM\JoinColumn(name: 'access_url_id', referencedColumnName: 'id', nullable: true)]
    protected ?AccessUrl $accessUrl = null;

    public function __construct()
    {
        $this->insertDateTime = new DateTime();
        $this->color = '';
        $this->urgency = '';
    }

    public function getId()
    {
        return $this->id;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getCode()
    {
        return $this->code;
    }

    public function setCode(int|string $code): self
    {
        $this->code = (string) $code;

        return $this;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getColor()
    {
        return $this->color;
    }

    public function setColor(string $color): self
    {
        $this->color = $color;

        return $this;
    }

    public function getUrgency()
    {
        return $this->urgency;
    }

    public function setUrgency(string $urgency): self
    {
        $this->urgency = $urgency;

        return $this;
    }

    public function getInsertUserId()
    {
        return $this->insertUserId;
    }

    public function setInsertUserId(int $insertUserId): self
    {
        $this->insertUserId = $insertUserId;

        return $this;
    }

    public function getInsertDateTime()
    {
        return $this->insertDateTime;
    }

    public function setInsertDateTime(DateTime $insertDateTime): self
    {
        $this->insertDateTime = $insertDateTime;

        return $this;
    }

    public function getLastEditUserId()
    {
        return $this->lastEditUserId;
    }

    public function setLastEditUserId(int $lastEditUserId): self
    {
        $this->lastEditUserId = $lastEditUserId;

        return $this;
    }

    public function getLastEditDateTime()
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
