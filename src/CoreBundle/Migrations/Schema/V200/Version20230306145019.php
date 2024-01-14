<?php

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20230306145019 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Create table resource_format';
    }

    public function up(Schema $schema): void
    {
        if (!$schema->hasTable('resource_format')) {
            $this->addSql(
                "CREATE TABLE resource_format (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime)', updated_at DATETIME NOT NULL COMMENT '(DC2Type:datetime)', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB ROW_FORMAT = DYNAMIC;"
            );
            $this->addSql(
                'ALTER TABLE resource_node ADD resource_format_id INT DEFAULT NULL;'
            );
            $this->addSql(
                'ALTER TABLE resource_node ADD CONSTRAINT FK_8A5F48FF7EE0A59A FOREIGN KEY (resource_format_id) REFERENCES resource_format (id) ON DELETE SET NULL;'
            );
            $this->addSql(
                'CREATE INDEX IDX_8A5F48FF7EE0A59A ON resource_node (resource_format_id);'
            );
        }
    }

    public function down(Schema $schema): void
    {
        if ($schema->hasTable('resource_format')) {
            $this->addSql(
                'ALTER TABLE resource_node DROP FOREIGN KEY FK_8A5F48FF7EE0A59A;'
            );
            $this->addSql(
                'ALTER TABLE resource_node DROP INDEX IDX_8A5F48FF7EE0A59A;'
            );
            $this->addSql(
                'ALTER TABLE resource_node DROP resource_format_id;'
            );
            $this->addSql(
                'DROP TABLE resource_format;'
            );
        }
    }
}
