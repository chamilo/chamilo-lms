<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V310;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Index;
use Doctrine\DBAL\Schema\Schema;
use RuntimeException;

final class Version20261012190000 extends AbstractMigrationChamilo
{
    private const string RESOURCE_NODE_PARENT_TITLE_INDEX = 'idx_rn_parent_title';
    private const int RESOURCE_NODE_TITLE_INDEX_PREFIX_LENGTH = 255;

    private const TITLE_COLUMNS = [
        ['table' => 'asset', 'length' => 255, 'nullable' => false],
        ['table' => 'azure_sync_state', 'length' => 255, 'nullable' => false],
        ['table' => 'block', 'length' => 255, 'nullable' => true],
        ['table' => 'branch_sync', 'length' => 250, 'nullable' => false],
        ['table' => 'branch_transaction_status', 'length' => 255, 'nullable' => false],
        ['table' => 'career', 'length' => 255, 'nullable' => false],
        ['table' => 'color_theme', 'length' => 255, 'nullable' => false],
        ['table' => 'conference_meeting', 'length' => 255, 'nullable' => false],
        ['table' => 'course_request', 'length' => 250, 'nullable' => false],
        ['table' => 'course_type', 'length' => 50, 'nullable' => false],
        ['table' => 'c_blog_comment', 'length' => 250, 'nullable' => false],
        ['table' => 'c_blog_post', 'length' => 250, 'nullable' => false],
        ['table' => 'c_blog_task', 'length' => 250, 'nullable' => false],
        ['table' => 'c_course_setting', 'length' => 255, 'nullable' => false],
        ['table' => 'c_peer_assessment_criteria', 'length' => 255, 'nullable' => true],
        ['table' => 'c_quiz_question_option', 'length' => 255, 'nullable' => false],
        ['table' => 'c_survey_template', 'length' => 255, 'nullable' => false],
        ['table' => 'c_toolbox', 'length' => 255, 'nullable' => false],
        ['table' => 'c_toolbox_version', 'length' => 255, 'nullable' => false],
        ['table' => 'c_wiki_category', 'length' => 255, 'nullable' => false],
        ['table' => 'grade_components', 'length' => 255, 'nullable' => false],
        ['table' => 'grade_model', 'length' => 255, 'nullable' => false],
        ['table' => 'lti_external_tool', 'length' => 255, 'nullable' => false],
        ['table' => 'mail_template', 'length' => 255, 'nullable' => false],
        ['table' => 'message', 'length' => 255, 'nullable' => false],
        ['table' => 'notification', 'length' => 255, 'nullable' => true],
        ['table' => 'notification_event', 'length' => 255, 'nullable' => false],
        ['table' => 'page', 'length' => 255, 'nullable' => false],
        ['table' => 'page_category', 'length' => 255, 'nullable' => false],
        ['table' => 'page_layout_template', 'length' => 255, 'nullable' => true],
        ['table' => 'permission', 'length' => 255, 'nullable' => false],
        ['table' => 'promotion', 'length' => 255, 'nullable' => false],
        ['table' => 'resource_file', 'length' => 255, 'nullable' => false],
        ['table' => 'resource_node', 'length' => 255, 'nullable' => false],
        ['table' => 'resource_tag', 'length' => 255, 'nullable' => false],
        ['table' => 'role', 'length' => 255, 'nullable' => false],
        ['table' => 'room', 'length' => 255, 'nullable' => false],
        ['table' => 'search_engine_field', 'length' => 200, 'nullable' => false],
        ['table' => 'sequence', 'length' => 255, 'nullable' => false],
        ['table' => 'sequence_row_entity', 'length' => 255, 'nullable' => false],
        ['table' => 'sequence_type_entity', 'length' => 255, 'nullable' => false],
        ['table' => 'sequence_variable', 'length' => 255, 'nullable' => true],
        ['table' => 'session_category', 'length' => 100, 'nullable' => false],
        ['table' => 'skill', 'length' => 255, 'nullable' => false],
        ['table' => 'skill_level', 'length' => 255, 'nullable' => false],
        ['table' => 'skill_level_profile', 'length' => 255, 'nullable' => false],
        ['table' => 'skill_profile', 'length' => 255, 'nullable' => false],
        ['table' => 'system_template', 'length' => 250, 'nullable' => false],
        ['table' => 'sys_announcement', 'length' => 250, 'nullable' => false],
        ['table' => 'templates', 'length' => 100, 'nullable' => false],
        ['table' => 'ticket_category', 'length' => 255, 'nullable' => false],
        ['table' => 'ticket_priority', 'length' => 255, 'nullable' => false],
        ['table' => 'ticket_project', 'length' => 255, 'nullable' => false],
        ['table' => 'ticket_status', 'length' => 255, 'nullable' => false],
        ['table' => 'track_e_hotpotatoes', 'length' => 255, 'nullable' => false],
        ['table' => 'user_friend_relation_type', 'length' => 20, 'nullable' => false],
        ['table' => 'xapi_tool_launch', 'length' => 255, 'nullable' => false],
    ];

    public function getDescription(): string
    {
        return 'Convert non-indexed Doctrine title columns to LONGTEXT';
    }

    public function up(Schema $schema): void
    {
        foreach (self::TITLE_COLUMNS as $definition) {
            $this->convertToLongText(
                $schema,
                $definition['table'],
                $definition['nullable']
            );
        }
    }

    public function down(Schema $schema): void
    {
        foreach (self::TITLE_COLUMNS as $definition) {
            $this->restoreVarchar(
                $schema,
                $definition['table'],
                $definition['length'],
                $definition['nullable']
            );
        }
    }

    private function convertToLongText(Schema $schema, string $tableName, bool $nullable): void
    {
        if (!$schema->hasTable($tableName)) {
            return;
        }

        $table = $schema->getTable($tableName);
        if (!$table->hasColumn('title')) {
            return;
        }

        $preserveResourceNodeIndex = $this->inspectTitleIndexes($tableName, $table->getIndexes());
        if ($preserveResourceNodeIndex) {
            $this->addSql(\sprintf(
                'DROP INDEX `%s` ON `resource_node`',
                self::RESOURCE_NODE_PARENT_TITLE_INDEX
            ));
        }

        $columnDefinition = 'resource_node' === $tableName
            ? "LONGTEXT NOT NULL COMMENT '(DC2Type:string)'"
            : 'LONGTEXT '.($nullable ? 'DEFAULT NULL' : 'NOT NULL');

        $this->addSql(\sprintf('ALTER TABLE `%s` MODIFY title %s', $tableName, $columnDefinition));

        if ($preserveResourceNodeIndex) {
            $this->addSql(\sprintf(
                'CREATE INDEX `%s` ON `resource_node` (parent_id, title(%d))',
                self::RESOURCE_NODE_PARENT_TITLE_INDEX,
                self::RESOURCE_NODE_TITLE_INDEX_PREFIX_LENGTH
            ));
        }
    }

    private function restoreVarchar(Schema $schema, string $tableName, int $maxLength, bool $nullable): void
    {
        if (!$schema->hasTable($tableName)) {
            return;
        }

        $table = $schema->getTable($tableName);
        if (!$table->hasColumn('title')) {
            return;
        }

        $preserveResourceNodeIndex = $this->inspectTitleIndexes($tableName, $table->getIndexes());
        if ($preserveResourceNodeIndex) {
            $this->addSql(\sprintf(
                'DROP INDEX `%s` ON `resource_node`',
                self::RESOURCE_NODE_PARENT_TITLE_INDEX
            ));
        }

        $this->assertMaxLength($tableName, $maxLength);

        $this->addSql(
            \sprintf(
                'ALTER TABLE `%s` MODIFY title VARCHAR(%d) %s',
                $tableName,
                $maxLength,
                $nullable ? 'DEFAULT NULL' : 'NOT NULL'
            )
        );

        if ($preserveResourceNodeIndex) {
            $this->addSql(\sprintf(
                'CREATE INDEX `%s` ON `resource_node` (parent_id, title)',
                self::RESOURCE_NODE_PARENT_TITLE_INDEX
            ));
        }
    }

    /**
     * Returns true only for the documented optional resource_node(parent_id, title)
     * optimization index. Any other title index remains a hard stop because silently
     * dropping an unknown production index would be unsafe.
     *
     * @param array<string, Index> $indexes
     */
    private function inspectTitleIndexes(string $tableName, array $indexes): bool
    {
        $preserveResourceNodeIndex = false;

        foreach ($indexes as $index) {
            $columns = array_map('strtolower', $index->getColumns());
            if (!\in_array('title', $columns, true)) {
                continue;
            }

            $isKnownResourceNodeIndex = 'resource_node' === $tableName
                && self::RESOURCE_NODE_PARENT_TITLE_INDEX === strtolower($index->getName())
                && ['parent_id', 'title'] === $columns;

            if ($isKnownResourceNodeIndex) {
                $preserveResourceNodeIndex = true;

                continue;
            }

            throw new RuntimeException(\sprintf('Cannot convert %s.title while index %s still references it.', $tableName, $index->getName()));
        }

        return $preserveResourceNodeIndex;
    }

    private function assertMaxLength(string $tableName, int $maxLength): void
    {
        $actualMaxLength = (int) $this->connection->fetchOne(
            \sprintf('SELECT COALESCE(MAX(CHAR_LENGTH(title)), 0) FROM `%s`', $tableName)
        );

        if ($actualMaxLength > $maxLength) {
            throw new RuntimeException(\sprintf('Cannot revert %s.title to VARCHAR(%d): existing data reaches %d characters.', $tableName, $maxLength, $actualMaxLength));
        }
    }
}
