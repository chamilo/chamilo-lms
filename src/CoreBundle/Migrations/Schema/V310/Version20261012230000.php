<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V310;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;
use JsonException;
use RuntimeException;

use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;

final class Version20261012230000 extends AbstractMigrationChamilo
{
    private const array CONTEXT_ROLES = [
        'ROLE_CURRENT_COURSE_TEACHER',
        'ROLE_CURRENT_COURSE_STUDENT',
        'ROLE_CURRENT_COURSE_GROUP_TEACHER',
        'ROLE_CURRENT_COURSE_GROUP_STUDENT',
        'ROLE_CURRENT_COURSE_SESSION_TEACHER',
        'ROLE_CURRENT_COURSE_SESSION_STUDENT',
    ];

    public function getDescription(): string
    {
        return 'Remove legacy FOS groups after materializing any group-derived roles on users';
    }

    /**
     * @throws JsonException
     */
    public function up(Schema $schema): void
    {
        $hasGroups = $schema->hasTable('fos_group');
        $hasMemberships = $schema->hasTable('fos_user_user_group');

        if (!$hasGroups && !$hasMemberships) {
            return;
        }

        if ($hasGroups) {
            $this->assertNoExternalGroupForeignKeys();
        }

        if ($hasMemberships) {
            $membershipCount = (int) $this->connection->fetchOne('SELECT COUNT(*) FROM fos_user_user_group');

            if ($membershipCount > 0) {
                if (!$hasGroups) {
                    throw new RuntimeException('Cannot migrate FOS group memberships because fos_group is missing.');
                }

                if (!$schema->hasTable('user')) {
                    throw new RuntimeException('Cannot migrate FOS group memberships because the user table is missing.');
                }

                $this->materializeGroupRoles($membershipCount);
            }

            $this->addSql('DROP TABLE fos_user_user_group');
        }

        if ($hasGroups) {
            $this->addSql('DROP TABLE fos_group');
        }
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException(
            'Legacy FOS groups and memberships cannot be reconstructed after their roles have been merged into user.roles.'
        );
    }

    /**
     * @throws JsonException
     */
    private function materializeGroupRoles(int $membershipCount): void
    {
        $rows = $this->connection->fetchAllAssociative(
            <<<'SQL'
                SELECT ug.user_id, u.roles AS user_roles, g.roles AS group_roles
                FROM fos_user_user_group ug
                INNER JOIN `user` u ON u.id = ug.user_id
                INNER JOIN fos_group g ON g.id = ug.group_id
                ORDER BY ug.user_id, ug.group_id
                SQL
        );

        if (\count($rows) !== $membershipCount) {
            throw new RuntimeException(\sprintf('Cannot safely migrate FOS groups: expected %d memberships but only %d could be joined to users and groups.', $membershipCount, \count($rows)));
        }

        /** @var array<int, array{roles: list<string>, seen: array<string, true>}> $rolesByUser */
        $rolesByUser = [];

        foreach ($rows as $row) {
            $userId = (int) $row['user_id'];

            if (!isset($rolesByUser[$userId])) {
                $userRoles = $this->decodeRoleList($row['user_roles'], \sprintf('user %d', $userId));
                $seen = [];

                foreach ($userRoles as $role) {
                    $normalizedRole = strtoupper(trim($role));
                    if ('' !== $normalizedRole) {
                        $seen[$normalizedRole] = true;
                    }
                }

                $rolesByUser[$userId] = [
                    'roles' => $userRoles,
                    'seen' => $seen,
                ];
            }

            $groupRoles = $this->decodeRoleList($row['group_roles'], \sprintf('FOS group assigned to user %d', $userId));

            foreach ($groupRoles as $role) {
                $normalizedRole = strtoupper(trim($role));

                if ('' === $normalizedRole) {
                    throw new RuntimeException(\sprintf('Cannot migrate an empty FOS group role assigned to user %d.', $userId));
                }

                if (\in_array($normalizedRole, self::CONTEXT_ROLES, true)) {
                    throw new RuntimeException(\sprintf('Cannot persist context role %s from a FOS group assigned to user %d.', $normalizedRole, $userId));
                }

                if ('ROLE_USER' === $normalizedRole || isset($rolesByUser[$userId]['seen'][$normalizedRole])) {
                    continue;
                }

                $rolesByUser[$userId]['roles'][] = $normalizedRole;
                $rolesByUser[$userId]['seen'][$normalizedRole] = true;
            }
        }

        foreach ($rolesByUser as $userId => $data) {
            $this->addSql(
                'UPDATE `user` SET roles = :roles WHERE id = :user_id',
                [
                    'roles' => json_encode($data['roles'], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                    'user_id' => $userId,
                ]
            );
        }
    }

    /**
     * @return list<string>
     *
     * @throws JsonException
     */
    private function decodeRoleList(mixed $encodedRoles, string $source): array
    {
        if (!\is_string($encodedRoles)) {
            throw new RuntimeException(\sprintf('Cannot migrate roles for %s because the stored value is not JSON text.', $source));
        }

        try {
            $roles = json_decode($encodedRoles, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException(\sprintf('Cannot migrate roles for %s because the stored JSON is invalid.', $source), 0, $exception);
        }

        if (!\is_array($roles)) {
            throw new RuntimeException(\sprintf('Cannot migrate roles for %s because the stored JSON value is not an array.', $source));
        }

        foreach ($roles as $role) {
            if (!\is_string($role)) {
                throw new RuntimeException(\sprintf('Cannot migrate roles for %s because the role list contains a non-string value.', $source));
            }
        }

        /** @var list<string> $roles */
        return array_values($roles);
    }

    private function assertNoExternalGroupForeignKeys(): void
    {
        $foreignKeys = $this->connection->fetchAllAssociative(
            <<<'SQL'
                SELECT TABLE_NAME, CONSTRAINT_NAME
                FROM information_schema.KEY_COLUMN_USAGE
                WHERE CONSTRAINT_SCHEMA = DATABASE()
                  AND REFERENCED_TABLE_SCHEMA = DATABASE()
                  AND REFERENCED_TABLE_NAME = 'fos_group'
                  AND TABLE_NAME <> 'fos_user_user_group'
                ORDER BY TABLE_NAME, CONSTRAINT_NAME
                SQL
        );

        if ([] === $foreignKeys) {
            return;
        }

        $references = array_map(
            static fn (array $foreignKey): string => \sprintf('%s.%s', $foreignKey['TABLE_NAME'], $foreignKey['CONSTRAINT_NAME']),
            $foreignKeys
        );

        throw new RuntimeException('Cannot remove fos_group because unexpected foreign keys still reference it: '.implode(', ', $references));
    }
}
