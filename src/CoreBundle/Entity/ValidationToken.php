<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Chamilo\CoreBundle\Repository\ValidationTokenRepository;
use DateTime;
use Doctrine\ORM\Mapping as ORM;

use const PHP_INT_MAX;

/**
 * ValidationToken entity.
 */
#[ORM\Table(name: 'validation_token')]
#[ORM\Index(columns: ['type', 'hash'], name: 'idx_type_hash')]
#[ORM\Entity(repositoryClass: ValidationTokenRepository::class)]
class ValidationToken
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: 'integer')]
    protected ?int $id = null;

    #[ORM\Column(type: 'integer')]
    protected int $type;

    #[ORM\Column(type: 'bigint')]
    protected int $resourceId;

    #[ORM\Column(type: 'string', length: 64)]
    protected string $hash;

    #[ORM\Column(type: 'datetime')]
    protected DateTime $createdAt;

    /**
     * @param string|null $hash A 64-char SHA-256 hex hash. If null, a secure random hash is generated.
     */
    public function __construct(int $type, int $resourceId, ?string $hash = null)
    {
        $this->type = $type;
        $this->resourceId = $resourceId;

        // If a hash is provided (e.g., remember-me), use it. Otherwise, generate one.
        $this->hash = $hash ?? hash('sha256', uniqid((string) random_int(0, PHP_INT_MAX), true));
        $this->createdAt = $this->createdAt ?? new DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getType(): int
    {
        return $this->type;
    }

    public function setType(int $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getResourceId(): int
    {
        return $this->resourceId;
    }

    public function setResourceId(int $resourceId): self
    {
        $this->resourceId = $resourceId;

        return $this;
    }

    public function getHash(): string
    {
        return $this->hash;
    }

    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(DateTime $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Generates a validation link.
     */
    public static function generateLink(int $type, int $resourceId): string
    {
        $token = new self($type, $resourceId);

        return '/validate/'.$type.'/'.$token->getHash();
    }
}
