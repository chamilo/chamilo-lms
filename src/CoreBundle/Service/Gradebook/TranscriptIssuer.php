<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Service\Gradebook;

use Chamilo\CoreBundle\Entity\AccessUrl;
use Chamilo\CoreBundle\Entity\Transcript;
use Chamilo\CoreBundle\Entity\User;
use DateTimeImmutable;
use DateTimeZone;
use Doctrine\ORM\EntityManagerInterface;

use const DATE_ATOM;
use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;

/**
 * Issues official transcripts: the transcript data at the time of issue, signed with the
 * institution issuer key, so that its verification page can attest it was not altered since.
 */
final readonly class TranscriptIssuer
{
    public const string FORMAT = 'chamilo-transcript-v1';

    public const string STATUS_VALID = 'valid';
    public const string STATUS_REVOKED = 'revoked';
    public const string STATUS_INVALID_SIGNATURE = 'invalid_signature';

    public function __construct(
        private TranscriptBuilder $builder,
        private IssuerSigningKey $issuerKey,
        private EntityManagerInterface $entityManager,
    ) {}

    public function issue(User $user, User $issuedBy, AccessUrl $url, string $issuerUrl): Transcript
    {
        $token = bin2hex(random_bytes(32));
        $payload = [
            'format' => self::FORMAT,
            'id' => $token,
            'issuer' => $issuerUrl,
            'publicKeyFingerprint' => hash('sha256', hex2bin($this->issuerKey->publicKeyHex())),
            'issuedAt' => (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format(DATE_ATOM),
            ...$this->builder->build($user, $url),
        ];
        $json = json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $signed = $this->issuerKey->sign($json);

        $transcript = new Transcript($user, $issuedBy, $url, $token, $json, $signed['signature'], $signed['publicKeyHex']);
        $this->entityManager->persist($transcript);
        $this->entityManager->flush();

        return $transcript;
    }

    public function revoke(Transcript $transcript): void
    {
        $transcript->revoke();
        $this->entityManager->flush();
    }

    public function getStatus(Transcript $transcript): string
    {
        if (!$this->issuerKey->verify($transcript->getPayloadJson(), $transcript->getSignature(), $transcript->getPublicKeyHex())) {
            return self::STATUS_INVALID_SIGNATURE;
        }

        return $transcript->isRevoked() ? self::STATUS_REVOKED : self::STATUS_VALID;
    }
}
