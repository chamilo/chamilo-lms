<?php

declare(strict_types=1);

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

        $table = $schema->getTable('extra_field_option_rel_field_option');
        if (!$table->hasForeignKey('FK_8E04DF6B42C79BE5')) {
            $this->addSql('ALTER TABLE extra_field_option_rel_field_option ADD CONSTRAINT FK_8E04DF6B42C79BE5 FOREIGN KEY (field_option_id) REFERENCES extra_field_options (id);');
            $this->addSql('CREATE INDEX IDX_8E04DF6B42C79BE5 ON extra_field_option_rel_field_option (field_option_id)');
        }
        if (!$table->hasForeignKey('FK_8E04DF6BCFAFCECC')) {
            $this->addSql('ALTER TABLE extra_field_option_rel_field_option ADD CONSTRAINT FK_8E04DF6BCFAFCECC FOREIGN KEY (related_field_option_id) REFERENCES extra_field_options (id);');
            $this->addSql('CREATE INDEX IDX_8E04DF6BCFAFCECC ON extra_field_option_rel_field_option (related_field_option_id);');
        }
        if (!$table->hasForeignKey('FK_8E04DF6B443707B0')) {
            $this->addSql('ALTER TABLE extra_field_option_rel_field_option ADD CONSTRAINT FK_8E04DF6B443707B0 FOREIGN KEY (field_id) REFERENCES extra_field (id);');
            $this->addSql('CREATE INDEX IDX_8E04DF6B443707B0 ON extra_field_option_rel_field_option (field_id);');
        }
    }
}
