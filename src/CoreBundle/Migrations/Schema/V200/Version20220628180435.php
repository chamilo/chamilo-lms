<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20220628180435 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Updates or creates the track_e_login_record table for tracking login attempts.';
    }

    public function up(Schema $schema): void
    {
        // Check if the old table exists
        if ($schema->hasTable('track_e_login_attempt')) {
            // Rename the old table if it exists
            $this->addSql('RENAME TABLE track_e_login_attempt TO track_e_login_record;');
            // Change primary key name from login_id to id
            $this->addSql('ALTER TABLE track_e_login_record CHANGE login_id id INT AUTO_INCREMENT NOT NULL;');
            // Modify the existing table to match the new structure
            $this->addSql('ALTER TABLE track_e_login_record CHANGE user_ip user_ip VARCHAR(45) NOT NULL;');
            $this->addSql('ALTER TABLE track_e_login_record ADD COLUMN success TINYINT(1) NOT NULL AFTER user_ip;');
        } else {
            // Create the new table if it doesn't exist
            $this->addSql('CREATE TABLE track_e_login_record (
                id INT AUTO_INCREMENT NOT NULL,
                username VARCHAR(100) NOT NULL,
                login_date DATETIME NOT NULL,
                user_ip VARCHAR(45) NOT NULL,
                success TINYINT(1) NOT NULL,
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB ROW_FORMAT = DYNAMIC;');
        }
    }

    public function down(Schema $schema): void
    {
        if ($schema->hasTable('track_e_login_record')) {
            $this->addSql('DROP TABLE track_e_login_record;');
        }
    }
}
