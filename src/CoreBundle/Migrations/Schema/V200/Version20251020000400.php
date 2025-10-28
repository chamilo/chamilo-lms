<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20251020000400 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Add resource_node_id, FK and UNIQUE index to gradebook_certificate';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('SET FOREIGN_KEY_CHECKS=0;');
        $this->addSql('SET UNIQUE_CHECKS=0;');

        if ($schema->hasTable('gradebook_certificate') && $schema->hasTable('resource_node')) {
            $table = $schema->getTable('gradebook_certificate');

            if (!$table->hasColumn('resource_node_id')) {
                $this->addSql('ALTER TABLE gradebook_certificate ADD resource_node_id INT DEFAULT NULL;');
            }

            $this->addSql('ALTER TABLE gradebook_certificate ADD CONSTRAINT FK_650669D1BAD783F FOREIGN KEY (resource_node_id) REFERENCES resource_node (id) ON DELETE CASCADE;');

            if (!$table->hasIndex('UNIQ_650669D1BAD783F')) {
                $this->addSql('CREATE UNIQUE INDEX UNIQ_650669D1BAD783F ON gradebook_certificate (resource_node_id);');
            }
        } else {
            error_log('Table gradebook_certificate or resource_node not found. Skipping changes.');
        }

        $this->addSql('SET UNIQUE_CHECKS=1;');
        $this->addSql('SET FOREIGN_KEY_CHECKS=1;');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('SET FOREIGN_KEY_CHECKS=0;');
        $this->addSql('SET UNIQUE_CHECKS=0;');

        if ($schema->hasTable('gradebook_certificate')) {
            $table = $schema->getTable('gradebook_certificate');

            $this->addSql('ALTER TABLE gradebook_certificate DROP FOREIGN KEY FK_650669D1BAD783F;');

            if ($table->hasIndex('UNIQ_650669D1BAD783F')) {
                $this->addSql('DROP INDEX UNIQ_650669D1BAD783F ON gradebook_certificate;');
            }

            if ($table->hasColumn('resource_node_id')) {
                $this->addSql('ALTER TABLE gradebook_certificate DROP COLUMN resource_node_id;');
            }
        }

        $this->addSql('SET UNIQUE_CHECKS=1;');
        $this->addSql('SET FOREIGN_KEY_CHECKS=1;');
    }
}
