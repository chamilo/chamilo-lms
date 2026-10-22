<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use DateTimeImmutable;
use DateTimeZone;
use Doctrine\ORM\Mapping as ORM;
use LogicException;

/**
 * Immutable signed snapshot of a certificate. Only its revocation and testnet
 * anchoring state can change. The original gradebook certificate can be deleted.
 */
#[ORM\Table(name: 'gradebook_certificate_credential')]
#[ORM\UniqueConstraint(name: 'uniq_gcc_token', columns: ['token'])]
#[ORM\UniqueConstraint(name: 'uniq_gcc_tx_hash', columns: ['tx_hash'])]
#[ORM\Index(name: 'idx_gcc_cert', columns: ['certificate_id'])]
#[ORM\Entity]
class GradebookCertificateCredential
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: GradebookCertificate::class)]
    #[ORM\JoinColumn(name: 'certificate_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?GradebookCertificate $certificate = null;

    #[ORM\Column(length: 64)]
    private string $token;

    #[ORM\Column(name: 'payload_json', type: 'text')]
    private string $payloadJson;

    #[ORM\Column(type: 'text')]
    private string $signature;

    #[ORM\Column(name: 'public_key_hex', length: 64)]
    private string $publicKeyHex;

    #[ORM\Column(name: 'anchor_hash', length: 64)]
    private string $anchorHash;

    #[ORM\Column(name: 'issued_at', type: 'datetime_immutable')]
    private DateTimeImmutable $issuedAt;

    #[ORM\Column(name: 'revoked_at', type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $revokedAt = null;

    #[ORM\Column(name: 'chain_id', type: 'integer', nullable: true)]
    private ?int $chainId = null;

    #[ORM\Column(name: 'tx_hash', length: 66, nullable: true)]
    private ?string $txHash = null;

    #[ORM\Column(name: 'anchored_at', type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $anchoredAt = null;

    public function __construct(GradebookCertificate $certificate, string $token, string $payloadJson, string $signature, string $publicKeyHex)
    {
        $this->certificate = $certificate;
        $this->token = $token;
        $this->payloadJson = $payloadJson;
        $this->signature = $signature;
        $this->publicKeyHex = $publicKeyHex;
        $this->anchorHash = hash('sha256', $payloadJson.'.'.$signature);
        $this->issuedAt = new DateTimeImmutable('now', new DateTimeZone('UTC'));
    }

    public function getId(): ?int
    {
        return $this->id;
    }
    public function getCertificate(): ?GradebookCertificate
    {
        return $this->certificate;
    }
    public function getToken(): string
    {
        return $this->token;
    }
    public function getPayloadJson(): string
    {
        return $this->payloadJson;
    }
    public function getSignature(): string
    {
        return $this->signature;
    }
    public function getPublicKeyHex(): string
    {
        return $this->publicKeyHex;
    }
    public function getAnchorHash(): string
    {
        return $this->anchorHash;
    }
    public function getIssuedAt(): DateTimeImmutable
    {
        return $this->issuedAt;
    }
    public function getRevokedAt(): ?DateTimeImmutable
    {
        return $this->revokedAt;
    }
    public function getChainId(): ?int
    {
        return $this->chainId;
    }
    public function getTxHash(): ?string
    {
        return $this->txHash;
    }
    public function getAnchoredAt(): ?DateTimeImmutable
    {
        return $this->anchoredAt;
    }

    public function revoke(): void
    {
        if (null === $this->revokedAt) {
            $this->revokedAt = new DateTimeImmutable('now', new DateTimeZone('UTC'));
        }
    }

    public function recordAnchor(string $txHash): void
    {
        if (null !== $this->txHash) {
            throw new LogicException('Credential is already anchored.');
        }
        $this->chainId = 11155111; // Sepolia testnet only
        $this->txHash = strtolower($txHash);
        $this->anchoredAt = new DateTimeImmutable('now', new DateTimeZone('UTC'));
    }
}
