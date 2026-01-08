<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20260106183100 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Rename CKEditor-specific settings to generic editor settings.';
    }

    public function up(Schema $schema): void
    {
        $renames = [
            'full_ckeditor_toolbar_set' => 'full_editor_toolbar_set',
            'ck_editor_block_image_copy_paste' => 'editor_block_image_copy_paste',
            'exercise_max_ckeditors_in_page' => 'exercise_max_editors_in_page',
        ];

        $tables = ['settings', 'settings_current', 'settings_options'];

        foreach ($tables as $tableName) {
            if (!$schema->hasTable($tableName)) {
                continue;
            }

            $table = $schema->getTable($tableName);
            if (!$table->hasColumn('variable')) {
                continue;
            }

            foreach ($renames as $old => $new) {
                // If the "new" variable already exists, drop the "old" one to avoid duplicates.
                $this->addSql("
                    DELETE FROM $tableName
                    WHERE variable = '$old'
                      AND EXISTS (SELECT 1 FROM $tableName t2 WHERE t2.variable = '$new')
                ");

                // Otherwise rename in place.
                $this->addSql("
                    UPDATE $tableName
                    SET variable = '$new'
                    WHERE variable = '$old'
                ");
            }
        }
    }

    public function down(Schema $schema): void
    {
        // Intentionally left empty (non-reversible rename).
    }
}
