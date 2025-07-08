<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\DataFixtures\PermissionFixtures;
use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20250706105000 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Creates role table, populates it, and migrates permission_rel_role from role_code to role_id.';
    }

    public function up(Schema $schema): void
    {
        // 1. Create table role
        $this->addSql("
            CREATE TABLE role (
                id INT UNSIGNED AUTO_INCREMENT NOT NULL,
                code VARCHAR(50) NOT NULL,
                constant_value INT NOT NULL,
                title VARCHAR(255) NOT NULL,
                description LONGTEXT DEFAULT NULL,
                system_role TINYINT(1) NOT NULL,
                created_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime)',
                created_by INT UNSIGNED DEFAULT NULL,
                updated_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime)',
                updated_by INT UNSIGNED DEFAULT NULL,
                UNIQUE INDEX UNIQ_57698A6A77153098 (code),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB ROW_FORMAT = DYNAMIC
        ");
        $this->write("Created table role.");

        // 2. Populate role table
        $roles = PermissionFixtures::getRoles();
        $values = [];
        $id = 1;

        foreach ($roles as $roleName => $shortCode) {
            $code = substr($roleName, 5);
            $constantValue = $this->getRoleConstantValue($code);
            $title = $this->getRoleTitle($code);
            $description = $this->getRoleDescription($code);

            $values[] = sprintf(
                "(%d, '%s', %d, '%s', '%s', %d, NOW())",
                $id,
                $code,
                $constantValue,
                addslashes($title),
                addslashes($description),
                1
            );

            $id++;
        }

        if (!empty($values)) {
            $this->addSql("
                INSERT INTO role
                    (id, code, constant_value, title, description, system_role, created_at)
                VALUES " . implode(", ", $values)
            );
            $this->write("Inserted role data from fixtures.");
        }

        // 3. Add nullable role_id
        $this->addSql("
            ALTER TABLE permission_rel_role
                ADD role_id INT UNSIGNED DEFAULT NULL
        ");
        $this->write("Added role_id column as nullable.");

        // 4. Migrate existing data
        $this->addSql("
            UPDATE permission_rel_role prr
            JOIN role r ON r.code = SUBSTRING(prr.role_code, 6)
            SET prr.role_id = r.id
        ");
        $this->write("Migrated data from role_code to role_id.");

        // 5. Drop old role_code
        $schemaManager = $this->connection->createSchemaManager();
        $columns = $schemaManager->listTableColumns('permission_rel_role');
        if (isset($columns['role_code'])) {
            $this->addSql("
                ALTER TABLE permission_rel_role DROP COLUMN role_code
            ");
            $this->write("Dropped column role_code.");
        }

        // 6. Set role_id NOT NULL
        $this->addSql("
            ALTER TABLE permission_rel_role
                MODIFY role_id INT UNSIGNED NOT NULL
        ");
        $this->write("Set role_id column to NOT NULL.");

        // 7. Add FK and index
        $this->addSql("
            ALTER TABLE permission_rel_role
                ADD CONSTRAINT FK_14B93D3DD60322AC FOREIGN KEY (role_id) REFERENCES role (id)
        ");
        $this->addSql("
            CREATE INDEX IDX_14B93D3DD60322AC ON permission_rel_role (role_id)
        ");
        $this->write("Added FK constraint and index for role_id.");
    }

    public function down(Schema $schema): void
    {
        // Drop FK and index if exists
        $schemaManager = $this->connection->createSchemaManager();
        if ($schemaManager->tablesExist(['permission_rel_role'])) {
            $foreignKeys = $schemaManager->listTableForeignKeys('permission_rel_role');
            foreach ($foreignKeys as $fk) {
                if ($fk->getForeignTableName() === 'role') {
                    $this->addSql(sprintf(
                        "ALTER TABLE permission_rel_role DROP FOREIGN KEY %s",
                        $fk->getName()
                    ));
                }
            }
            $this->addSql("DROP INDEX IF EXISTS IDX_14B93D3DD60322AC ON permission_rel_role");
            $columns = $schemaManager->listTableColumns('permission_rel_role');
            if (isset($columns['role_id'])) {
                $this->addSql("ALTER TABLE permission_rel_role DROP COLUMN role_id");
            }
        }

        $this->addSql("DROP TABLE IF EXISTS role");
        $this->write("Rolled back migration: dropped role table and role_id column.");
    }

    private function getRoleConstantValue(string $code): int
    {
        $map = [
            'INVITEE' => 1,
            'STUDENT' => 5,
            'TEACHER' => 10,
            'ADMIN' => 15,
            'SUPER_ADMIN' => 20,
            'GLOBAL_ADMIN' => 25,
            'HR' => 30,
            'QUESTION_MANAGER' => 35,
            'SESSION_MANAGER' => 40,
            'STUDENT_BOSS' => 45,
        ];

        return $map[$code] ?? 0;
    }

    private function getRoleTitle(string $code): string
    {
        $map = [
            'INVITEE' => 'Invitee',
            'STUDENT' => 'Student',
            'TEACHER' => 'Teacher',
            'ADMIN' => 'Administrator',
            'SUPER_ADMIN' => 'Super Administrator',
            'GLOBAL_ADMIN' => 'Global Administrator',
            'HR' => 'Human Resources',
            'QUESTION_MANAGER' => 'Question Manager',
            'SESSION_MANAGER' => 'Session Manager',
            'STUDENT_BOSS' => 'Student Boss',
        ];

        return $map[$code] ?? $code;
    }

    private function getRoleDescription(string $code): string
    {
        $map = [
            'INVITEE' => 'User invited with limited rights',
            'STUDENT' => 'Regular student user',
            'TEACHER' => 'User with teaching rights',
            'ADMIN' => 'Platform administrator',
            'SUPER_ADMIN' => 'Full platform administrator',
            'GLOBAL_ADMIN' => 'Global admin user',
            'HR' => 'HR manager',
            'QUESTION_MANAGER' => 'Manages question banks',
            'SESSION_MANAGER' => 'Manages sessions',
            'STUDENT_BOSS' => 'Special student role',
        ];

        return $map[$code] ?? '';
    }
}
