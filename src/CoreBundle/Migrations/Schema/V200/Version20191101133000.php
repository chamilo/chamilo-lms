<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

class Version20191101133000 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Migrate career';
    }

    public function up(Schema $schema): void
    {
        if (!$schema->hasTable('user_career')) {
            $this->addSql('CREATE TABLE user_career (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, career_id INT NOT NULL, extra_data LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB ROW_FORMAT = DYNAMIC;');
        }
    }

    public function down(Schema $schema): void
    {
    }
}
