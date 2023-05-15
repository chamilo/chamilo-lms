<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Class PortfolioAttachment.
 *
 * @package Chamilo\CoreBundle\Entity
 *
 */

#[ORM\Table(name: "portfolio_attachment")]
#[ORM\Entity(repositoryClass: "Chamilo\CoreBundle\Entity\Repository\PortfolioAttachmentRepository")]
class PortfolioAttachment
{
    public const TYPE_ITEM = 1;
    public const TYPE_COMMENT = 2;

    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    private int $id;

    #[ORM\Column(type: 'string', length: 255)]
    private string $path;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $comment;

    #[ORM\Column(type: 'integer')]
    private int $size;

    #[ORM\Column(type: 'string', length: 255)]
    private string $filename;

    #[ORM\Column(name: 'origin_id', type: 'integer')]
    private int $origin;

    #[ORM\Column(name: 'origin_type', type: 'integer')]
    private int $originType;

    public function getId(): int
    {
        return $this->id;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function setPath(string $path): PortfolioAttachment
    {
        $this->path = $path;

        return $this;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(?string $comment): PortfolioAttachment
    {
        $this->comment = $comment;

        return $this;
    }

    public function getSize(): int
    {
        return $this->size;
    }

    public function setSize(int $size): PortfolioAttachment
    {
        $this->size = $size;

        return $this;
    }

    public function getFilename(): string
    {
        return $this->filename;
    }

    public function setFilename(string $filename): PortfolioAttachment
    {
        $this->filename = $filename;

        return $this;
    }

    public function getOrigin(): int
    {
        return $this->origin;
    }

    public function setOrigin(int $origin): PortfolioAttachment
    {
        $this->origin = $origin;

        return $this;
    }

    public function getOriginType(): int
    {
        return $this->originType;
    }

    public function setOriginType(int $originType): PortfolioAttachment
    {
        $this->originType = $originType;

        return $this;
    }
}
