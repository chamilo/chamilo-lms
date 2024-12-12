<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20241211183300 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Migration for creating the validation_token table';
    }

    public function up(Schema $schema): void
    {
        if (!$schema->hasTable('validation_token')) {
            $this->addSql("
                CREATE TABLE validation_token (
                    id INT AUTO_INCREMENT NOT NULL,
                    type INT NOT NULL,
                    resource_id BIGINT NOT NULL,
                    hash CHAR(64) NOT NULL,
                    created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime)',
                    INDEX idx_type_hash (type, hash),
                    PRIMARY KEY(id)
                ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB ROW_FORMAT = DYNAMIC
            ");
        }
    }

    public function down(Schema $schema): void
    {
        if ($schema->hasTable('validation_token')) {
            $this->addSql('DROP TABLE validation_token');
        }
    }
}
