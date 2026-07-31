<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20201210100012 extends AbstractMigrationChamilo
{
    private const string DOCTRINE_STRING_TYPE_COMMENT = '(DC2Type:string)';
    private const array RESOURCE_TITLE_TABLES = [
        'resource_node',
        'course',
        'illustration',
        'personal_file',
        'portfolio',
        'usergroup',
        'c_announcement',
        'c_attendance',
        'c_blog',
        'c_calendar_event',
        'c_chat_conversation',
        'c_course_description',
        'c_document',
        'c_dropbox_file',
        'c_forum_forum',
        'c_forum_category',
        'c_forum_post',
        'c_forum_thread',
        'c_glossary',
        'c_group_info',
        'c_group_category',
        'c_link',
        'c_link_category',
        'c_lp',
        'c_lp_category',
        'c_notebook',
        'c_quiz',
        'c_quiz_category',
        'c_quiz_question_category',
        'c_shortcut',
        'c_student_publication',
        'c_student_publication_correction',
        'c_survey',
        'c_thematic',
        'c_thematic_plan',
        'c_tool',
        'c_wiki',
    ];

    public function getDescription(): string
    {
        return 'Prepare resource title columns for legacy data migrations by converting them to LONGTEXT.';
    }

    public function isTransactional(): bool
    {
        return false;
    }

    public function up(Schema $schema): void
    {
        foreach (self::RESOURCE_TITLE_TABLES as $tableName) {
            $column = $this->connection->fetchAssociative(
                'SELECT DATA_TYPE AS data_type, IS_NULLABLE AS is_nullable, COLUMN_COMMENT AS column_comment
                 FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE()
                   AND TABLE_NAME = :table_name
                   AND COLUMN_NAME = :column_name',
                [
                    'table_name' => $tableName,
                    'column_name' => 'title',
                ]
            );

            if (false === $column) {
                $this->getLogger()->warning('Resource title migration skipped a missing title column.', [
                    'table' => $tableName,
                ]);

                continue;
            }

            $notNull = 'NO' === strtoupper((string) $column['is_nullable']);
            $requiresStringTypeComment = 'resource_node' === $tableName;
            $hasExpectedTypeComment = !$requiresStringTypeComment
                || self::DOCTRINE_STRING_TYPE_COMMENT === (string) $column['column_comment'];

            if ('longtext' === strtolower((string) $column['data_type']) && $hasExpectedTypeComment) {
                continue;
            }

            $nullability = $notNull ? 'NOT NULL' : 'DEFAULT NULL';
            $comment = $requiresStringTypeComment
                ? " COMMENT '".self::DOCTRINE_STRING_TYPE_COMMENT."'"
                : '';

            $this->addSql(
                \sprintf(
                    'ALTER TABLE %s CHANGE title title LONGTEXT %s%s',
                    $tableName,
                    $nullability,
                    $comment
                )
            );
        }
    }

    public function down(Schema $schema): void
    {
        // Intentionally left empty. Reducing LONGTEXT columns to their previous
        // lengths could truncate valid resource titles and is not data-safe.
    }
}
