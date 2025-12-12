<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;
use Throwable;

final class Version20251202103000 extends AbstractMigrationChamilo
{
    private const TARGET_CHARSET = 'utf8mb4';
    private const TARGET_COLLATION = 'utf8mb4_unicode_ci';

    private const LEGACY_CHARSET = 'utf8mb3';
    private const LEGACY_COLLATION = 'utf8mb3_unicode_ci';

    public function getDescription(): string
    {
        return 'Convert remaining core tables to utf8mb4 / utf8mb4_unicode_ci';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(
            !\in_array($this->connection->getDatabasePlatform()->getName(), ['mysql', 'mariadb'], true),
            'This migration only supports MySQL/MariaDB.'
        );

        foreach ($this->getTablesToConvert() as $table) {
            $this->convertTable($table, self::TARGET_CHARSET, self::TARGET_COLLATION);
        }
    }

    public function down(Schema $schema): void
    {
        $this->abortIf(
            !\in_array($this->connection->getDatabasePlatform()->getName(), ['mysql', 'mariadb'], true),
            'This migration only supports MySQL/MariaDB.'
        );

        $tables = $this->getTablesToConvert();
        $offenders = $this->findUtf8mb3IncompatibleColumns($tables);

        if (!empty($offenders)) {
            $message = "Cannot downgrade to utf8mb3. Found 4-byte Unicode characters in:\n- ".
                implode("\n- ", $offenders).
                "\n\nRemove those characters first or restore a pre-migration backup.";

            if (method_exists($this, 'throwIrreversibleMigrationException')) {
                $this->throwIrreversibleMigrationException($message);
            }

            $this->abortIf(true, $message);
        }

        foreach ($tables as $table) {
            $this->convertTable($table, self::LEGACY_CHARSET, self::LEGACY_COLLATION);
        }
    }

    private function findUtf8mb3IncompatibleColumns(array $tables): array
    {
        $db = $this->connection->fetchOne('SELECT DATABASE()');

        $in = implode(',', array_fill(0, \count($tables), '?'));

        $cols = $this->connection->fetchAllAssociative(
            "SELECT TABLE_NAME, COLUMN_NAME
         FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = ?
           AND TABLE_NAME IN ($in)
           AND COLLATION_NAME LIKE 'utf8mb4%'
           AND DATA_TYPE IN ('char','varchar','tinytext','text','mediumtext','longtext')",
            array_merge([$db], $tables)
        );

        $bad = [];

        foreach ($cols as $col) {
            $t = $col['TABLE_NAME'];
            $c = $col['COLUMN_NAME'];

            // MySQL 8+ supports this Unicode range regexp.
            // If your server doesn't support it, this query will throw and we fallback.
            try {
                $hit = $this->connection->fetchOne(
                    "SELECT 1 FROM `$t` WHERE `$c` REGEXP '[\\x{10000}-\\x{10FFFF}]' LIMIT 1"
                );
            } catch (Throwable $e) {
                // Fallback: any UTF-8 4-byte sequence starts with lead bytes F0-F4.
                $hit = $this->connection->fetchOne(
                    "SELECT 1 FROM `$t` WHERE HEX(`$c`) REGEXP 'F[0-4]' LIMIT 1"
                );
            }

            if ($hit) {
                $bad[] = "$t.$c";
            }
        }

        return $bad;
    }

    /**
     * @return string[]
     */
    private function getTablesToConvert(): array
    {
        return [
            // Main
            'access_url',
            'agenda_reminder',
            'block',
            'branch_sync',
            'branch_transaction',
            'branch_transaction_status',
            'career',
            'chat',
            'chat_video',
            'course',
            'course_category',
            'course_rel_class',
            'course_request',
            'course_type',

            // Course tables (single DB schema in C2)
            'c_announcement',
            'c_announcement_attachment',
            'c_attendance',
            'c_attendance_sheet',
            'c_attendance_sheet_log',
            'c_blog',
            'c_blog_attachment',
            'c_blog_comment',
            'c_blog_post',
            'c_blog_rating',
            'c_blog_task',
            'c_calendar_event',
            'c_calendar_event_attachment',
            'c_calendar_event_repeat',
            'c_course_description',
            'c_course_setting',
            'c_document',
            'c_dropbox_category',
            'c_dropbox_feedback',
            'c_dropbox_file',
            'c_dropbox_post',
            'c_forum_attachment',
            'c_forum_category',
            'c_forum_forum',
            'c_forum_post',
            'c_forum_thread',
            'c_glossary',
            'c_group_category',
            'c_group_info',
            'c_group_rel_user',
            'c_link',
            'c_link_category',
            'c_lp',
            'c_lp_category',
            'c_lp_item',
            'c_lp_item_view',
            'c_lp_iv_interaction',
            'c_lp_iv_objective',
            'c_notebook',
            'c_quiz',
            'c_quiz_answer',
            'c_quiz_category',
            'c_quiz_question',
            'c_quiz_question_category',
            'c_quiz_question_option',
            'c_quiz_rel_question',
            'c_student_publication',
            'c_student_publication_comment',
            'c_survey',
            'c_survey_answer',
            'c_survey_invitation',
            'c_survey_question',
            'c_survey_question_option',
            'c_thematic',
            'c_thematic_advance',
            'c_thematic_plan',
            'c_tool',
            'c_tool_intro',
            'c_wiki',
            'c_wiki_conf',
            'c_wiki_discuss',
            'c_wiki_mailcue',

            // Extra fields / settings
            'extra_field',
            'extra_field_options',
            'extra_field_saved_search',
            'extra_field_values',
            'ext_log_entries',
            'fos_group',

            // Gradebook
            'gradebook_category',
            'gradebook_certificate',
            'gradebook_evaluation',
            'gradebook_link',
            'gradebook_linkeval_log',
            'gradebook_score_display',
            'grade_components',
            'grade_model',

            // Misc
            'language',
            'legal',
            'message_attachment',
            'portfolio',
            'portfolio_category',
            'promotion',
            'room',
            'scheduled_announcements',
            'search_engine_ref',

            // Sequence
            'sequence',
            'sequence_condition',
            'sequence_method',
            'sequence_row_entity',
            'sequence_rule',
            'sequence_type_entity',
            'sequence_variable',

            // Sessions / settings
            'session',
            'session_category',
            'settings',
            'settings_options',

            // Skills
            'skill',
            'skill_level',
            'skill_level_profile',
            'skill_profile',
            'skill_rel_gradebook',
            'skill_rel_item',
            'skill_rel_user',
            'skill_rel_user_comment',

            // Specific fields
            'specific_field',
            'specific_field_values',

            // Templates / announcements
            'system_template',
            'sys_announcement',
            'tag',
            'templates',

            // Tickets
            'ticket_category',
            'ticket_message',
            'ticket_message_attachments',
            'ticket_priority',
            'ticket_project',
            'ticket_status',
            'ticket_ticket',

            // Tool rights
            'tool',
            'tool_resource_right',

            // Tracking
            'track_e_access',
            'track_e_attempt',
            'track_e_course_access',
            'track_e_default',
            'track_e_downloads',
            'track_e_exercises',
            'track_e_hotpotatoes',
            'track_e_hotspot',
            'track_e_lastaccess',
            'track_e_login',
            'track_e_online',

            // Users
            'user',
            'usergroup',
            'user_api_key',
            'user_course_category',
            'user_friend_relation_type',
        ];
    }

    private function convertTable(string $table, string $charset, string $collation): void
    {
        if (!$this->tableExists($table)) {
            // Table might not exist depending on the edition / previous migrations.
            return;
        }

        // CONVERT changes all textual columns (CHAR/VARCHAR/TEXT/ENUM/SET) and the table default collation.
        $this->addSql(\sprintf(
            'ALTER TABLE `%s` CONVERT TO CHARACTER SET %s COLLATE %s',
            $table,
            $charset,
            $collation
        ));
    }

    private function tableExists(string $table): bool
    {
        try {
            return $this->connection->createSchemaManager()->tablesExist([$table]);
        } catch (Throwable $e) {
            // Keep the migration resilient if schema introspection fails.
            return false;
        }
    }
}
