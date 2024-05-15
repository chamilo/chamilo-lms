<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Entity;

use Chamilo\CoreBundle\Repository\XApiAttachmentRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: XApiAttachmentRepository::class)]
class XApiAttachment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $identifier = null;

    #[ORM\Column(length: 255)]
    private ?string $usageType = null;

    #[ORM\Column]
    private ?int $contentType = null;

    #[ORM\Column]
    private ?int $length = null;

    #[ORM\Column(length: 255)]
    private ?string $sha2 = null;

    #[ORM\Column]
    private array $display = [];

    #[ORM\Column]
    private ?bool $hasDescription = null;

    #[ORM\Column(nullable: true)]
    private ?array $description = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $fileUrl = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $content = null;

    #[ORM\ManyToOne(inversedBy: 'attachments')]
    private ?XApiStatement $statement = null;

    public function getIdentifier(): ?int
    {
        return $this->identifier;
    }

    public function getUsageType(): ?string
    {
        return $this->usageType;
    }

    public function setUsageType(string $usageType): static
    {
        $this->usageType = $usageType;

        return $this;
    }

    public function getLength(): ?int
    {
        return $this->length;
    }

    public function setLength(int $length): static
    {
        $this->length = $length;

        return $this;
    }

    public function getContentType(): ?int
    {
        return $this->contentType;
    }

    public function setContentType(int $contentType): static
    {
        $this->contentType = $contentType;

        return $this;
    }

    public function getSha2(): ?string
    {
        return $this->sha2;
    }

    public function setSha2(string $sha2): static
    {
        $this->sha2 = $sha2;

        return $this;
    }

    public function getDisplay(): array
    {
        return $this->display;
    }

    public function setDisplay(array $display): static
    {
        $this->display = $display;

        return $this;
    }

    public function hasDescription(): ?bool
    {
        return $this->hasDescription;
    }

    public function setHasDescription(bool $hasDescription): static
    {
        $this->hasDescription = $hasDescription;

        return $this;
    }

    public function getDescription(): ?array
    {
        return $this->description;
    }

    public function setDescription(?array $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getFileUrl(): ?string
    {
        return $this->fileUrl;
    }

    public function setFileUrl(?string $fileUrl): static
    {
        $this->fileUrl = $fileUrl;

        return $this;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(?string $content): static
    {
        $this->content = $content;

        return $this;
    }

    public function getStatement(): ?XApiStatement
    {
        return $this->statement;
    }

    public function setStatement(?XApiStatement $statement): static
    {
        $this->statement = $statement;

        return $this;
    }
}
