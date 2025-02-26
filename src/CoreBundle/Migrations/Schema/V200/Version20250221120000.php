<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20250221120000 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Adds ON DELETE CASCADE to the skill_rel_user_id foreign key in skill_rel_user_comment table';
    }

    public function up(Schema $schema): void
    {
        $connection = $this->connection;

        $constraint = $connection->fetchOne('
        SELECT CONSTRAINT_NAME
        FROM information_schema.KEY_COLUMN_USAGE
        WHERE TABLE_NAME = "skill_rel_user_comment"
        AND COLUMN_NAME = "skill_rel_user_id"
        AND CONSTRAINT_SCHEMA = DATABASE()
        LIMIT 1
    ');

        if ($constraint) {
            $connection->executeStatement("ALTER TABLE skill_rel_user_comment DROP FOREIGN KEY `$constraint`");
        }

        $connection->executeStatement('
        ALTER TABLE skill_rel_user_comment
        ADD CONSTRAINT FK_7AE9F6B6484A9317
        FOREIGN KEY (skill_rel_user_id)
        REFERENCES skill_rel_user (id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
    ');
    }
}
