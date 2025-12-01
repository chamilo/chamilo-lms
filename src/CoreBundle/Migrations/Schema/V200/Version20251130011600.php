<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20251130011600 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Add resource_node_id to attempt_file/attempt_feedback and declare new resource types for them.';
    }

    public function up(Schema $schema): void
    {
        // --- attempt_file ---
        if ($schema->hasTable('attempt_file')) {
            $table = $schema->getTable('attempt_file');

            if (!$table->hasColumn('resource_node_id')) {
                // Add nullable FK column. We place it after asset_id to keep legacy column nearby.
                $this->addSql(
                    'ALTER TABLE attempt_file
                     ADD resource_node_id INT DEFAULT NULL AFTER asset_id'
                );

                // Index for FK performance.
                $this->addSql(
                    'CREATE INDEX IDX_ATTEMPT_FILE_RESOURCE_NODE
                     ON attempt_file (resource_node_id)'
                );

                // FK to resource_node.id.
                $this->addSql(
                    'ALTER TABLE attempt_file
                     ADD CONSTRAINT FK_ATTEMPT_FILE_RESOURCE_NODE
                     FOREIGN KEY (resource_node_id)
                     REFERENCES resource_node (id)
                     ON DELETE CASCADE'
                );
            }
        }

        // --- attempt_feedback ---
        if ($schema->hasTable('attempt_feedback')) {
            $table = $schema->getTable('attempt_feedback');

            if (!$table->hasColumn('resource_node_id')) {
                $this->addSql(
                    'ALTER TABLE attempt_feedback
                     ADD resource_node_id INT DEFAULT NULL AFTER asset_id'
                );

                $this->addSql(
                    'CREATE INDEX IDX_ATTEMPT_FEEDBACK_RESOURCE_NODE
                     ON attempt_feedback (resource_node_id)'
                );

                $this->addSql(
                    'ALTER TABLE attempt_feedback
                     ADD CONSTRAINT FK_ATTEMPT_FEEDBACK_RESOURCE_NODE
                     FOREIGN KEY (resource_node_id)
                     REFERENCES resource_node (id)
                     ON DELETE CASCADE'
                );
            }
        }

        $this->declareNewResourceTypes();
    }

    public function down(Schema $schema): void
    {
        // --- attempt_file ---
        if ($schema->hasTable('attempt_file')) {
            $table = $schema->getTable('attempt_file');

            if ($table->hasColumn('resource_node_id')) {
                // Drop FK if it exists.
                if ($table->hasForeignKey('FK_ATTEMPT_FILE_RESOURCE_NODE')) {
                    $this->addSql(
                        'ALTER TABLE attempt_file
                         DROP FOREIGN KEY FK_ATTEMPT_FILE_RESOURCE_NODE'
                    );
                }

                // Drop index if it exists.
                if ($table->hasIndex('IDX_ATTEMPT_FILE_RESOURCE_NODE')) {
                    $this->addSql(
                        'DROP INDEX IDX_ATTEMPT_FILE_RESOURCE_NODE
                         ON attempt_file'
                    );
                }

                // Drop the column.
                $this->addSql(
                    'ALTER TABLE attempt_file
                     DROP COLUMN resource_node_id'
                );
            }
        }

        // --- attempt_feedback ---
        if ($schema->hasTable('attempt_feedback')) {
            $table = $schema->getTable('attempt_feedback');

            if ($table->hasColumn('resource_node_id')) {
                if ($table->hasForeignKey('FK_ATTEMPT_FEEDBACK_RESOURCE_NODE')) {
                    $this->addSql(
                        'ALTER TABLE attempt_feedback
                         DROP FOREIGN KEY FK_ATTEMPT_FEEDBACK_RESOURCE_NODE'
                    );
                }

                if ($table->hasIndex('IDX_ATTEMPT_FEEDBACK_RESOURCE_NODE')) {
                    $this->addSql(
                        'DROP INDEX IDX_ATTEMPT_FEEDBACK_RESOURCE_NODE
                         ON attempt_feedback'
                    );
                }

                $this->addSql(
                    'ALTER TABLE attempt_feedback
                     DROP COLUMN resource_node_id'
                );
            }
        }

        $this->removeNewResourceTypes();
    }

    /**
     * Declare new resource types for attempt_file and attempt_feedback.
     *
     * They are linked to the "quiz" tool so that ResourceNode::getResourceType()->getTool()
     * is always initialized correctly.
     */
    private function declareNewResourceTypes(): void
    {
        // Create resource_type "attempt_file" if it does not exist yet.
        $this->addSql(
            "INSERT INTO resource_type (title, tool_id, created_at, updated_at)
         SELECT 'attempt_file', t.id, NOW(), NOW()
         FROM tool t
         WHERE t.name = 'quiz'
           AND NOT EXISTS (
               SELECT 1 FROM resource_type WHERE title = 'attempt_file'
           )"
        );

        // Create resource_type "attempt_feedback" if it does not exist yet.
        $this->addSql(
            "INSERT INTO resource_type (title, tool_id, created_at, updated_at)
         SELECT 'attempt_feedback', t.id, NOW(), NOW()
         FROM tool t
         WHERE t.name = 'quiz'
           AND NOT EXISTS (
               SELECT 1 FROM resource_type WHERE title = 'attempt_feedback'
           )"
        );

        // Safety net: if rows already exist but have tool_id = NULL, link them to quiz as well.
        $this->addSql(
            "UPDATE resource_type rt
         JOIN tool t ON t.name = 'quiz'
         SET rt.tool_id = t.id
         WHERE rt.title IN ('attempt_file', 'attempt_feedback')
           AND (rt.tool_id IS NULL OR rt.tool_id = 0)"
        );
    }

    /**
     * Remove the newly declared resource types (used in down()).
     */
    private function removeNewResourceTypes(): void
    {
        $this->addSql(
            "DELETE FROM resource_type
             WHERE title IN ('attempt_file', 'attempt_feedback')"
        );
    }
}
