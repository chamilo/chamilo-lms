<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20200101020000 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Add language_id foreign keys to resource_node and resource_file (nullable, for indexing and variants).';
    }

    public function up(Schema $schema): void
    {
        // resource_node.language_id
        $this->addSql('ALTER TABLE resource_node ADD language_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE resource_node ADD CONSTRAINT FK_8A5F48FF82F1BAF4 FOREIGN KEY (language_id) REFERENCES language (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_8A5F48FF82F1BAF4 ON resource_node (language_id)');

        // resource_file.language_id
        $this->addSql('ALTER TABLE resource_file ADD language_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE resource_file ADD CONSTRAINT FK_83BF96AA82F1BAF4 FOREIGN KEY (language_id) REFERENCES language (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_83BF96AA82F1BAF4 ON resource_file (language_id)');
    }

    public function down(Schema $schema): void
    {
        // Drop resource_node FK/index/column
        $this->addSql('ALTER TABLE resource_node DROP FOREIGN KEY FK_8A5F48FF82F1BAF4');
        $this->addSql('DROP INDEX IDX_8A5F48FF82F1BAF4 ON resource_node');
        $this->addSql('ALTER TABLE resource_node DROP language_id');

        // Drop resource_file FK/index/column
        $this->addSql('ALTER TABLE resource_file DROP FOREIGN KEY FK_83BF96AA82F1BAF4');
        $this->addSql('DROP INDEX IDX_83BF96AA82F1BAF4 ON resource_file');
        $this->addSql('ALTER TABLE resource_file DROP language_id');
    }
}
