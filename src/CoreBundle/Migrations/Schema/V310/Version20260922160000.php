<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V310;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20260922160000 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Add generated source metadata and SCORM learning-path links to AI Toolbox versions.';
    }

    public function up(Schema $schema): void
    {
        if (!$schema->hasTable('c_toolbox_version')) {
            return;
        }

        $table = $schema->getTable('c_toolbox_version');

        if (!$table->hasColumn('provider')) {
            $this->addSql('ALTER TABLE c_toolbox_version ADD provider VARCHAR(64) DEFAULT NULL');
        }
        if (!$table->hasColumn('change_summary')) {
            $this->addSql('ALTER TABLE c_toolbox_version ADD change_summary LONGTEXT DEFAULT NULL');
        }
        if (!$table->hasColumn('html_content')) {
            $this->addSql('ALTER TABLE c_toolbox_version ADD html_content LONGTEXT DEFAULT NULL');
        }
        if (!$table->hasColumn('css_content')) {
            $this->addSql('ALTER TABLE c_toolbox_version ADD css_content LONGTEXT DEFAULT NULL');
        }
        if (!$table->hasColumn('javascript_content')) {
            $this->addSql('ALTER TABLE c_toolbox_version ADD javascript_content LONGTEXT DEFAULT NULL');
        }
        if (!$table->hasColumn('learning_path_id')) {
            $this->addSql('ALTER TABLE c_toolbox_version ADD learning_path_id INT DEFAULT NULL');
            $this->addSql('CREATE INDEX IDX_C_TOOLBOX_VERSION_LP ON c_toolbox_version (learning_path_id)');
            $this->addSql('ALTER TABLE c_toolbox_version ADD CONSTRAINT FK_C_TOOLBOX_VERSION_LP FOREIGN KEY (learning_path_id) REFERENCES c_lp (iid) ON DELETE SET NULL');
        }
    }

    public function down(Schema $schema): void
    {
        if (!$schema->hasTable('c_toolbox_version')) {
            return;
        }

        $table = $schema->getTable('c_toolbox_version');

        if ($table->hasForeignKey('FK_C_TOOLBOX_VERSION_LP')) {
            $this->addSql('ALTER TABLE c_toolbox_version DROP FOREIGN KEY FK_C_TOOLBOX_VERSION_LP');
        }
        if ($table->hasIndex('IDX_C_TOOLBOX_VERSION_LP')) {
            $this->addSql('DROP INDEX IDX_C_TOOLBOX_VERSION_LP ON c_toolbox_version');
        }

        foreach (['learning_path_id', 'javascript_content', 'css_content', 'html_content', 'change_summary', 'provider'] as $column) {
            if ($table->hasColumn($column)) {
                $this->addSql('ALTER TABLE c_toolbox_version DROP COLUMN '.$column);
            }
        }
    }
}
