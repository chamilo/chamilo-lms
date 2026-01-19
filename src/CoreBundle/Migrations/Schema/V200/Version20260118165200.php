<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20260118165200 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Remove deprecated setting "document.tool_visible_by_default_at_creation" and keep only course.active_tools_on_create.';
    }

    public function up(Schema $schema): void
    {
        $schemaManager = $this->connection->createSchemaManager();

        // Remove from "settings" table (definition)
        if ($schemaManager->tablesExist('settings')) {
            $columns = $schemaManager->listTableColumns('settings');

            $hasVariable = isset($columns['variable']);
            $hasCategory = isset($columns['category']);

            if ($hasVariable && $hasCategory) {
                $this->addSql("DELETE FROM settings WHERE variable = 'tool_visible_by_default_at_creation' AND category = 'document'");
            } elseif ($hasVariable) {
                $this->addSql("DELETE FROM settings WHERE variable = 'tool_visible_by_default_at_creation'");
            }
        }

        // Remove from "settings_current" table (stored values), if it exists
        if ($schemaManager->tablesExist('settings_current')) {
            $columns = $schemaManager->listTableColumns('settings_current');

            $hasVariable = isset($columns['variable']);
            $hasCategory = isset($columns['category']);

            if ($hasVariable && $hasCategory) {
                $this->addSql("DELETE FROM settings_current WHERE variable = 'tool_visible_by_default_at_creation' AND category = 'document'");
            } elseif ($hasVariable) {
                $this->addSql("DELETE FROM settings_current WHERE variable = 'tool_visible_by_default_at_creation'");
            }
        }
    }

    public function down(Schema $schema): void
    {
        // Intentionally left empty (setting removed from codebase).
    }
}
