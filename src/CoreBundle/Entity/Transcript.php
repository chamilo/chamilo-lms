<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Chamilo\CoreBundle\Repository\TranscriptRepository;
use DateTimeImmutable;
use DateTimeZone;
use Doctrine\ORM\Mapping as ORM;

use const JSON_THROW_ON_ERROR;

/**
 * Official academic transcript of a user: an immutable signed snapshot of the courses they
 * completed, as issued by someone who manages them. Only its revocation can change, so it keeps
 * attesting what was issued even after the gradebook changes.
 */
#[ORM\Table(name: 'transcript')]
#[ORM\UniqueConstraint(name: 'uniq_transcript_token', columns: ['token'])]
#[ORM\Index(name: 'idx_transcript_user', columns: ['user_id'])]
#[ORM\Entity(repositoryClass: TranscriptRepository::class)]
class Transcript
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'issued_by_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?User $issuedBy;

    #[ORM\ManyToOne(targetEntity: AccessUrl::class)]
    #[ORM\JoinColumn(name: 'access_url_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private AccessUrl $url;

    #[ORM\Column(length: 64)]
    private string $token;

    #[ORM\Column(name: 'payload_json', type: 'text')]
    private string $payloadJson;

    #[ORM\Column(type: 'text')]
    private string $signature;

    #[ORM\Column(name: 'public_key_hex', length: 64)]
    private string $publicKeyHex;

    #[ORM\Column(name: 'issued_at', type: 'datetime_immutable')]
    private DateTimeImmutable $issuedAt;

    #[ORM\Column(name: 'revoked_at', type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $revokedAt = null;

    public function __construct(
        User $user,
        ?User $issuedBy,
        AccessUrl $url,
        string $token,
        string $payloadJson,
        string $signature,
        string $publicKeyHex,
    ) {
        $this->user = $user;
        $this->issuedBy = $issuedBy;
        $this->url = $url;
        $this->token = $token;
        $this->payloadJson = $payloadJson;
        $this->signature = $signature;
        $this->publicKeyHex = $publicKeyHex;
        $this->issuedAt = new DateTimeImmutable('now', new DateTimeZone('UTC'));
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getIssuedBy(): ?User
    {
        return $this->issuedBy;
    }

    public function getUrl(): AccessUrl
    {
        return $this->url;
    }

    public function getToken(): string
    {
        return $this->token;
    }

    public function getPayloadJson(): string
    {
        return $this->payloadJson;
    }

    /**
     * @return array<string, mixed>
     */
    public function getPayload(): array
    {
        return (array) json_decode($this->payloadJson, true, 512, JSON_THROW_ON_ERROR);
    }

    public function getSignature(): string
    {
        return $this->signature;
    }

    public function getPublicKeyHex(): string
    {
        return $this->publicKeyHex;
    }

    public function getIssuedAt(): DateTimeImmutable
    {
        return $this->issuedAt;
    }

    public function getRevokedAt(): ?DateTimeImmutable
    {
        return $this->revokedAt;
    }

    public function isRevoked(): bool
    {
        return null !== $this->revokedAt;
    }

    public function revoke(): void
    {
        $this->revokedAt ??= new DateTimeImmutable('now', new DateTimeZone('UTC'));
    }
}
