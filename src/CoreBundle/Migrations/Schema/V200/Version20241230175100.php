<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20241230175100 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Fix cascading delete for c_lp_category_rel_user table foreign key dynamically';
    }

    public function up(Schema $schema): void
    {
        // Find the foreign key name dynamically
        $foreignKeyName = $this->getForeignKeyName('c_lp_category_rel_user', 'user_id');

        if ($foreignKeyName) {
            $this->addSql("ALTER TABLE c_lp_category_rel_user DROP FOREIGN KEY `$foreignKeyName`");
        }

        // Add the updated foreign key
        $this->addSql('
            ALTER TABLE c_lp_category_rel_user
            ADD CONSTRAINT FK_83D35829A76ED395
            FOREIGN KEY (user_id)
            REFERENCES user(id)
            ON DELETE SET NULL
            ON UPDATE CASCADE
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE c_lp_category_rel_user DROP FOREIGN KEY FK_83D35829A76ED395');

        $this->addSql('
            ALTER TABLE c_lp_category_rel_user
            ADD CONSTRAINT c_lp_category_rel_user_ibfk_1
            FOREIGN KEY (user_id)
            REFERENCES user(id)
            ON DELETE SET NULL
        ');
    }

    private function getForeignKeyName(string $tableName, string $columnName): ?string
    {
        $query = "
            SELECT CONSTRAINT_NAME
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_NAME = :tableName
            AND COLUMN_NAME = :columnName
            AND TABLE_SCHEMA = DATABASE()
        ";

        $result = $this->connection->fetchOne($query, [
            'tableName' => $tableName,
            'columnName' => $columnName,
        ]);

        return $result ?: null;
    }
}
