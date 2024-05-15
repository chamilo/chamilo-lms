<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Entity;

use Chamilo\CoreBundle\Repository\XApiResultRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: XApiResultRepository::class)]
class XApiResult
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $identifier = null;

    #[ORM\Column]
    private ?bool $hasScore = null;

    #[ORM\Column(nullable: true)]
    private ?float $scaled = null;

    #[ORM\Column(nullable: true)]
    private ?float $raw = null;

    #[ORM\Column(nullable: true)]
    private ?float $min = null;

    #[ORM\Column(nullable: true)]
    private ?float $max = null;

    #[ORM\Column(nullable: true)]
    private ?bool $success = null;

    #[ORM\Column(nullable: true)]
    private ?bool $completion = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $response = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $duration = null;

    #[ORM\OneToOne(cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(referencedColumnName: 'identifier')]
    private ?XApiExtensions $extensions = null;

    public function getIdentifier(): ?int
    {
        return $this->identifier;
    }

    public function hasScore(): ?bool
    {
        return $this->hasScore;
    }

    public function setHasScore(bool $hasScore): static
    {
        $this->hasScore = $hasScore;

        return $this;
    }

    public function getScaled(): ?float
    {
        return $this->scaled;
    }

    public function setScaled(?float $scaled): static
    {
        $this->scaled = $scaled;

        return $this;
    }

    public function getRaw(): ?float
    {
        return $this->raw;
    }

    public function setRaw(?float $raw): static
    {
        $this->raw = $raw;

        return $this;
    }

    public function getMin(): ?float
    {
        return $this->min;
    }

    public function setMin(?float $min): static
    {
        $this->min = $min;

        return $this;
    }

    public function getMax(): ?float
    {
        return $this->max;
    }

    public function setMax(?float $max): static
    {
        $this->max = $max;

        return $this;
    }

    public function isSuccess(): ?bool
    {
        return $this->success;
    }

    public function setSuccess(?bool $success): static
    {
        $this->success = $success;

        return $this;
    }

    public function isCompletion(): ?bool
    {
        return $this->completion;
    }

    public function setCompletion(?bool $completion): static
    {
        $this->completion = $completion;

        return $this;
    }

    public function getResponse(): ?string
    {
        return $this->response;
    }

    public function setResponse(?string $response): static
    {
        $this->response = $response;

        return $this;
    }

    public function getDuration(): ?string
    {
        return $this->duration;
    }

    public function setDuration(?string $duration): static
    {
        $this->duration = $duration;

        return $this;
    }

    public function getExtensions(): ?XApiExtensions
    {
        return $this->extensions;
    }

    public function setExtensions(?XApiExtensions $extensions): static
    {
        $this->extensions = $extensions;

        return $this;
    }
}
