<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Chamilo\CoreBundle\Repository\AiRequestsRepository;

#[ORM\Table(name: 'ai_requests')]
#[ORM\Entity(repositoryClass: AiRequestsRepository::class)]
class AiRequests
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[Assert\NotBlank]
    #[ORM\Column(type: 'integer')]
    private int $userId;

    #[Assert\NotBlank]
    #[ORM\Column(type: 'string', length: 255)]
    private string $toolName;

    #[Assert\NotBlank]
    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $requestedAt;

    #[Assert\NotBlank]
    #[ORM\Column(type: 'text')]
    private string $requestText;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $promptTokens = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $completionTokens = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $totalTokens = null;

    #[Assert\NotBlank]
    #[ORM\Column(type: 'string', length: 50)]
    private string $aiProvider;

    public function __construct()
    {
        $this->requestedAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function setUserId(int $userId): self
    {
        $this->userId = $userId;
        return $this;
    }

    public function getToolName(): string
    {
        return $this->toolName;
    }

    public function setToolName(string $toolName): self
    {
        $this->toolName = $toolName;
        return $this;
    }

    public function getRequestedAt(): \DateTimeInterface
    {
        return $this->requestedAt;
    }

    public function getRequestText(): string
    {
        return $this->requestText;
    }

    public function setRequestText(string $requestText): self
    {
        $this->requestText = $requestText;
        return $this;
    }

    public function getPromptTokens(): ?int
    {
        return $this->promptTokens;
    }

    public function setPromptTokens(?int $promptTokens): self
    {
        $this->promptTokens = $promptTokens;
        return $this;
    }

    public function getCompletionTokens(): ?int
    {
        return $this->completionTokens;
    }

    public function setCompletionTokens(?int $completionTokens): self
    {
        $this->completionTokens = $completionTokens;
        return $this;
    }

    public function getTotalTokens(): ?int
    {
        return $this->totalTokens;
    }

    public function setTotalTokens(?int $totalTokens): self
    {
        $this->totalTokens = $totalTokens;
        return $this;
    }

    public function getAiProvider(): string
    {
        return $this->aiProvider;
    }

    public function setAiProvider(string $aiProvider): self
    {
        $this->aiProvider = $aiProvider;
        return $this;
    }
}
