<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use ApiPlatform\Doctrine\Orm\Filter\NumericFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use Chamilo\CoreBundle\Repository\PushSubscriptionRepository;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    operations: [
        new Get(security: "is_granted('ROLE_USER')"),
        new Post(security: "is_granted('ROLE_USER')"),
        new GetCollection(security: "is_granted('ROLE_USER')"),
        new Delete(security: "is_granted('ROLE_USER')"),
    ],
    normalizationContext: ['groups' => ['pushsubscription:read']],
    denormalizationContext: ['groups' => ['pushsubscription:write']],
    paginationClientEnabled: false
)]
#[ApiFilter(SearchFilter::class, properties: [
    'endpoint' => 'exact',
])]
#[ApiFilter(NumericFilter::class, properties: [
    'user.id',
])]
#[ORM\Table(name: 'push_subscription')]
#[ORM\Index(columns: ['user_id'], name: 'idx_push_subscription_user')]
#[ORM\Entity(repositoryClass: PushSubscriptionRepository::class)]
class PushSubscription
{
    #[Groups(['pushsubscription:read'])]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[Groups(['pushsubscription:read', 'pushsubscription:write'])]
    #[Assert\NotBlank]
    #[ORM\Column(name: 'endpoint', type: 'text')]
    private string $endpoint;

    #[Groups(['pushsubscription:read', 'pushsubscription:write'])]
    #[Assert\NotBlank]
    #[ORM\Column(name: 'public_key', type: 'text')]
    private string $publicKey;

    #[Groups(['pushsubscription:read', 'pushsubscription:write'])]
    #[Assert\NotBlank]
    #[ORM\Column(name: 'auth_token', type: 'text')]
    private string $authToken;

    #[Groups(['pushsubscription:read', 'pushsubscription:write'])]
    #[ORM\Column(name: 'content_encoding', type: 'string', length: 20, nullable: true, options: ['default' => 'aesgcm'])]
    private ?string $contentEncoding = 'aesgcm';

    #[Groups(['pushsubscription:read', 'pushsubscription:write'])]
    #[ORM\Column(name: 'user_agent', type: 'text', nullable: true)]
    private ?string $userAgent = null;

    #[Groups(['pushsubscription:read'])]
    #[ORM\Column(name: 'created_at', type: 'datetime')]
    private DateTime $createdAt;

    #[Groups(['pushsubscription:read'])]
    #[ORM\Column(name: 'updated_at', type: 'datetime')]
    private DateTime $updatedAt;

    #[Groups(['pushsubscription:read', 'pushsubscription:write'])]
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private ?User $user = null;

    public function __construct()
    {
        $this->createdAt = new DateTime();
        $this->updatedAt = new DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEndpoint(): string
    {
        return $this->endpoint;
    }

    public function setEndpoint(string $endpoint): self
    {
        $this->endpoint = $endpoint;

        return $this;
    }

    public function getPublicKey(): string
    {
        return $this->publicKey;
    }

    public function setPublicKey(string $publicKey): self
    {
        $this->publicKey = $publicKey;

        return $this;
    }

    public function getAuthToken(): string
    {
        return $this->authToken;
    }

    public function setAuthToken(string $authToken): self
    {
        $this->authToken = $authToken;

        return $this;
    }

    public function getContentEncoding(): ?string
    {
        return $this->contentEncoding;
    }

    public function setContentEncoding(?string $contentEncoding): self
    {
        $this->contentEncoding = $contentEncoding;

        return $this;
    }

    public function getUserAgent(): ?string
    {
        return $this->userAgent;
    }

    public function setUserAgent(?string $userAgent): self
    {
        $this->userAgent = $userAgent;

        return $this;
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

    public function getUpdatedAt(): DateTime
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(DateTime $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }
}
