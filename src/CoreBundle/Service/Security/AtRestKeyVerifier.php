<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Service\Security;

use Doctrine\DBAL\Connection;
use Symfony\Component\Uid\Uuid;

/**
 * Check all supported encrypted user columns before a key is initialized or a batch is written.
 * Never disclose stored values or decrypted personal data in diagnostics.
 */
final readonly class AtRestKeyVerifier
{
    private const array FIELDS = ['biography', 'phone', 'address'];

    public function __construct(
        private Connection $connection,
        private AtRestCrypto $crypto,
    ) {}

    public function hasEncryptedUserData(): bool
    {
        foreach (self::FIELDS as $field) {
            if ((int) $this->connection->fetchOne(
                'SELECT COUNT(*) FROM user WHERE BINARY '.$field.' LIKE BINARY :prefix',
                ['prefix' => 'chamilo-encrypted:%']
            ) > 0) {
                return true;
            }
        }

        return false;
    }

    public function assertKeyMatchesStoredUserData(): void
    {
        // Also check that the key exists even if the database contains no ciphertext.
        $this->crypto->encrypt('key-check', 'chamilo:key-check');
        foreach (self::FIELDS as $field) {
            $sample = $this->connection->fetchAssociative(
                'SELECT uuid, '.$field.' AS encrypted_value FROM user'
                .' WHERE BINARY '.$field.' LIKE BINARY :prefix LIMIT 1',
                ['prefix' => 'chamilo-encrypted:%']
            );
            if (false === $sample) {
                continue;
            }

            $rawUuid = (string) $sample['uuid'];
            $uuid = 16 === \strlen($rawUuid)
                ? (string) Uuid::fromBinary($rawUuid)
                : (string) Uuid::fromString($rawUuid);

            $this->crypto->decrypt(
                (string) $sample['encrypted_value'],
                'user.'.$field.':'.$uuid,
            );
        }
    }
}
