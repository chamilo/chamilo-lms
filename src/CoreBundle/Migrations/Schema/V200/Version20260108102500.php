<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20260108102500 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Remove temporary context roles (ROLE_CURRENT_*) persisted in user.roles';
    }

    public function up(Schema $schema): void
    {
        // Chamilo runs on MySQL/MariaDB typically. Keep it safe.
        $this->abortIf(
            'mysql' !== $this->connection->getDatabasePlatform()->getName(),
            'This migration is intended for MySQL/MariaDB.'
        );

        // Only touch rows that look suspicious to avoid scanning the whole table.
        $rows = $this->connection->fetchAllAssociative(
            'SELECT id, roles FROM user WHERE roles LIKE :pattern',
            ['pattern' => '%ROLE_CURRENT_%']
        );

        foreach ($rows as $row) {
            $userId = (int) $row['id'];
            $raw = $row['roles'];

            $roles = $this->decodeRoles($raw);
            if (empty($roles)) {
                continue;
            }

            $clean = $this->removeTemporaryContextRoles($roles);

            // If nothing changed, skip update
            if ($clean === $roles) {
                continue;
            }

            $encoded = serialize(array_values($clean));

            $this->connection->executeStatement(
                'UPDATE user SET roles = :roles WHERE id = :id',
                ['roles' => $encoded, 'id' => $userId]
            );
        }
    }

    public function down(Schema $schema): void
    {
        // Not reversible safely (we don't know which ROLE_CURRENT_* were present).
        // Intentionally left empty.
    }

    /**
     * @return string[]
     */
    private function decodeRoles(mixed $raw): array
    {
        if (null === $raw || '' === $raw) {
            return [];
        }

        // Usually stored as PHP serialized array (a:...{...})
        if (\is_string($raw)) {
            $decoded = @unserialize($raw, ['allowed_classes' => false]);
            if (\is_array($decoded)) {
                return $this->normalizeRoles($decoded);
            }

            // Fallback: maybe JSON in some setups
            $json = json_decode($raw, true);
            if (\is_array($json)) {
                return $this->normalizeRoles($json);
            }

            return [];
        }

        // In case DBAL returns an array (rare in raw SQL, but safe)
        if (\is_array($raw)) {
            return $this->normalizeRoles($raw);
        }

        return [];
    }

    /**
     * @param mixed[] $roles
     *
     * @return string[]
     */
    private function normalizeRoles(array $roles): array
    {
        $out = [];
        foreach ($roles as $role) {
            if (!\is_string($role)) {
                continue;
            }
            $role = trim($role);
            if ('' === $role) {
                continue;
            }
            $out[] = strtoupper($role);
        }

        // Keep order but remove duplicates
        return array_values(array_unique($out));
    }

    /**
     * Remove any temporary context roles from DB.
     *
     * @param string[] $roles
     *
     * @return string[]
     */
    private function removeTemporaryContextRoles(array $roles): array
    {
        $clean = [];

        foreach ($roles as $role) {
            // Anything starting with ROLE_CURRENT_ must never be persisted
            if (str_starts_with($role, 'ROLE_CURRENT_')) {
                continue;
            }

            $clean[] = $role;
        }

        return array_values(array_unique($clean));
    }
}
