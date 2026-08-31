<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V300;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20260831140000 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Grant ROLE_GLOBAL_ADMIN to every administrator registered on access_url_id = 1.';
    }

    public function up(Schema $schema): void
    {
        $admins = $this->connection->fetchAllAssociative(
            <<<'SQL'
SELECT DISTINCT u.id, u.roles
FROM user u
INNER JOIN access_url_rel_user aru ON aru.user_id = u.id
WHERE aru.access_url_id = 1
  AND (u.roles LIKE :roleAdmin OR u.roles LIKE :roleGlobalAdmin)
SQL,
            [
                'roleAdmin' => '%ROLE_ADMIN%',
                'roleGlobalAdmin' => '%ROLE_GLOBAL_ADMIN%',
            ]
        );

        foreach ($admins as $adminUser) {
            $roles = $this->decodeRoles((string) $adminUser['roles']);
            if (\in_array('ROLE_GLOBAL_ADMIN', $roles, true)) {
                continue;
            }

            $roles[] = 'ROLE_GLOBAL_ADMIN';
            $this->addSql(
                'UPDATE user SET roles = ? WHERE id = ?',
                [json_encode(array_values(array_unique($roles))), (int) $adminUser['id']]
            );
        }
    }

    public function down(Schema $schema): void
    {
        // Intentionally a no-op: this is a one-way data grant, not meant to be
        // reversed. There is no reliable way to distinguish, after the fact,
        // a ROLE_GLOBAL_ADMIN this migration added from one an admin already
        // had or was granted independently afterwards.
    }

    /**
     * @return list<string>
     */
    private function decodeRoles(string $storedRoles): array
    {
        if ('' === $storedRoles) {
            return [];
        }

        $roles = str_starts_with($storedRoles, 'a:')
            ? unserialize($storedRoles, ['allowed_classes' => false])
            : json_decode($storedRoles, true);

        if (!\is_array($roles)) {
            return [];
        }

        return array_values(array_unique(array_filter(array_map(
            static fn (mixed $role): string => strtoupper(trim((string) $role)),
            $roles,
        ))));
    }
}
