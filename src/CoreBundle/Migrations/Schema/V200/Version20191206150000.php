<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

/**
 * Extra fields.
 */
class Version20191206150000 extends AbstractMigrationChamilo
{
    public function up(Schema $schema): void
    {
        $this->getEntityManager();

        $table = $schema->getTable('extra_field');
        if (false === $table->hasColumn('helper_text')) {
            $this->addSql('ALTER TABLE extra_field ADD helper_text text DEFAULT NULL AFTER display_text');
        }
        $this->addSql('ALTER TABLE extra_field_values CHANGE value value LONGTEXT DEFAULT NULL;');
        if (false === $table->hasColumn('description')) {
            $this->addSql('ALTER TABLE extra_field ADD description LONGTEXT DEFAULT NULL');
        }

        $table = $schema->getTable('extra_field_values');
        if (!$table->hasIndex('idx_efv_item')) {
            $this->addSql('CREATE INDEX idx_efv_item ON extra_field_values (item_id)');
        }
    }

    public function down(Schema $schema): void
    {
    }
}
