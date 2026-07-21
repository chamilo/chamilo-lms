<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V210;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;
use RuntimeException;

final class Version20260721183000 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Preserve platform administrator roles and remove the legacy admin table.';
    }

    public function up(Schema $schema): void
    {
        if (!$schema->hasTable('admin')) {
            return;
        }

        if (!$schema->hasTable('user')) {
            throw new RuntimeException('The user table is required before removing the legacy admin table.');
        }

        $adminUsers = $this->connection->fetchAllAssociative(
            <<<'SQL'
SELECT a.user_id, u.roles
FROM admin a
INNER JOIN user u ON u.id = a.user_id
WHERE a.user_id IS NOT NULL
ORDER BY a.id
SQL
        );

        foreach ($adminUsers as $adminUser) {
            $roles = $this->decodeRoles((string) $adminUser['roles']);
            if (\in_array('ROLE_ADMIN', $roles, true)) {
                continue;
            }

            $roles[] = 'ROLE_ADMIN';
            $this->addSql(
                'UPDATE user SET roles = ? WHERE id = ?',
                [\serialize(\array_values(\array_unique($roles))), (int) $adminUser['user_id']]
            );
        }

        $this->addSql('DROP TABLE admin');
    }

    public function down(Schema $schema): void
    {
        if ($schema->hasTable('admin')) {
            return;
        }

        $this->addSql(
            <<<'SQL'
CREATE TABLE admin (
    id INT AUTO_INCREMENT NOT NULL,
    user_id INT DEFAULT NULL,
    UNIQUE INDEX UNIQ_880E0D76A76ED395 (user_id),
    PRIMARY KEY(id)
) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
SQL
        );
        $this->addSql(
            'ALTER TABLE admin ADD CONSTRAINT FK_880E0D76A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE'
        );
        $this->addSql(
            <<<'SQL'
INSERT INTO admin (user_id)
SELECT id
FROM user
WHERE roles LIKE '%ROLE_ADMIN%'
   OR roles LIKE '%ROLE_GLOBAL_ADMIN%'
ORDER BY id
SQL
        );
    }

    /**
     * @return list<string>
     */
    private function decodeRoles(string $storedRoles): array
    {
        if ('' === $storedRoles) {
            return [];
        }

        $roles = \str_starts_with($storedRoles, 'a:')
            ? \unserialize($storedRoles, ['allowed_classes' => false])
            : \json_decode($storedRoles, true)
        ;

        if (!\is_array($roles)) {
            return [];
        }

        return \array_values(\array_unique(\array_filter(\array_map(
            static fn (mixed $role): string => \strtoupper(\trim((string) $role)),
            $roles,
        ))));
    }
}
