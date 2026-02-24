<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Helpers;

use Chamilo\CoreBundle\Entity\ExtraField;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception as DbalException;
use Doctrine\DBAL\Schema\Column;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use RuntimeException;
use Throwable;

use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;

final class UserMergeHelper
{
    private const LOG_PREFIX = '[UserMergeHelper]';
    private bool $enableLogs = false;

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UserRepository $userRepository,
        private readonly LoggerInterface $logger
    ) {}

    /**
     * Merge $mergeUserId into $keepUserId.
     *
     * @param bool|null $enableLogs optional override for this call only (default: null = keep current flag)
     */
    public function mergeUsers(int $keepUserId, int $mergeUserId, ?bool $enableLogs = null): bool
    {
        $keepUserId = (int) $keepUserId;
        $mergeUserId = (int) $mergeUserId;

        // Per-call override (restored at the end to avoid side effects on shared service).
        $previousEnableLogs = $this->enableLogs;
        if (null !== $enableLogs) {
            $this->enableLogs = (bool) $enableLogs;
        }

        try {
            $this->log(LogLevel::ERROR, 'mergeUsers() called.', [
                'keepUserId' => $keepUserId,
                'mergeUserId' => $mergeUserId,
            ]);

            if ($keepUserId <= 0 || $mergeUserId <= 0 || $keepUserId === $mergeUserId) {
                $this->log(LogLevel::ERROR, 'Invalid merge parameters.', [
                    'keepUserId' => $keepUserId,
                    'mergeUserId' => $mergeUserId,
                ]);

                return false;
            }

            /** @var User|null $keepUser */
            $keepUser = $this->userRepository->find($keepUserId);

            /** @var User|null $mergeUser */
            $mergeUser = $this->userRepository->find($mergeUserId);

            if (!$keepUser || !$mergeUser) {
                $this->log(LogLevel::ERROR, 'User(s) not found.', [
                    'keepFound' => (bool) $keepUser,
                    'mergeFound' => (bool) $mergeUser,
                    'keepUserId' => $keepUserId,
                    'mergeUserId' => $mergeUserId,
                ]);

                return false;
            }

            $this->log(LogLevel::ERROR, 'Users loaded.', [
                'keepUserId' => (int) $keepUser->getId(),
                'mergeUserId' => (int) $mergeUser->getId(),
                'keepUsername' => (string) $keepUser->getUsername(),
                'mergeUsername' => (string) $mergeUser->getUsername(),
            ]);

            $conn = $this->em->getConnection();
            $conn->beginTransaction();
            $this->log(LogLevel::ERROR, 'Transaction started.');

            try {
                $this->log(LogLevel::ERROR, 'Step 1: mergeExtraFieldValues().');
                $this->mergeExtraFieldValues($conn, $mergeUserId, $keepUserId);

                $this->log(LogLevel::ERROR, 'Step 1: mergeExtraFieldTags().');
                $this->mergeExtraFieldTags($conn, $mergeUserId, $keepUserId);

                $this->log(LogLevel::ERROR, 'Step 2: reassignUserResourcesToTargetSQL().');
                $this->reassignUserResourcesToTargetSQL($conn, $mergeUser, $keepUser);

                $this->log(LogLevel::ERROR, 'Step 3: markUserAsMerged().');
                $this->markUserAsMerged($conn, $mergeUserId, $keepUserId);

                $conn->commit();

                $this->log(LogLevel::ERROR, 'Transaction committed. Merge completed successfully.', [
                    'keepUserId' => $keepUserId,
                    'mergeUserId' => $mergeUserId,
                ]);

                return true;
            } catch (Throwable $e) {
                $this->safeRollback($conn);

                $this->log(LogLevel::ERROR, 'Merge failed with exception.', [
                    'message' => $e->getMessage(),
                    'class' => $e::class,
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]);

                throw $e;
            }
        } finally {
            // Restore flag (important when the service is reused in the same request).
            $this->enableLogs = $previousEnableLogs;
        }
    }

    private function reassignUserResourcesToTargetSQL(Connection $conn, User $sourceUser, User $targetUser): void
    {
        $sourceId = (int) $sourceUser->getId();
        $targetId = (int) $targetUser->getId();

        $this->log(LogLevel::ERROR, 'Reassign step started.', [
            'sourceId' => $sourceId,
            'targetId' => $targetId,
        ]);

        $affected = $conn->executeStatement(
            'UPDATE resource_node SET creator_id = :targetId WHERE creator_id = :sourceId',
            ['targetId' => $targetId, 'sourceId' => $sourceId]
        );
        $this->log(LogLevel::ERROR, 'Updated resource_node.creator_id.', ['affected' => $affected]);

        $sourceNodeId = $sourceUser->getResourceNode()?->getId();
        $targetNodeId = $targetUser->getResourceNode()?->getId();

        $this->log(LogLevel::ERROR, 'Resource node ids.', [
            'sourceNodeId' => $sourceNodeId,
            'targetNodeId' => $targetNodeId,
        ]);

        if ($sourceNodeId && $targetNodeId && $sourceNodeId !== $targetNodeId) {
            $affected = $conn->executeStatement(
                'UPDATE resource_node SET parent_id = :targetNodeId WHERE parent_id = :sourceNodeId',
                ['targetNodeId' => $targetNodeId, 'sourceNodeId' => $sourceNodeId]
            );
            $this->log(LogLevel::ERROR, 'Updated resource_node.parent_id.', ['affected' => $affected]);
        }

        foreach ($this->getPivotMergeRules() as $rule) {
            $this->log(LogLevel::ERROR, 'Pivot merge rule start.', $rule);

            $this->pivotMerge(
                $conn,
                (string) $rule['table'],
                (string) $rule['userField'],
                $sourceId,
                $targetId,
                (array) ($rule['matchColumns'] ?? [])
            );

            $this->log(LogLevel::ERROR, 'Pivot merge rule done.', ['table' => (string) $rule['table']]);
        }

        foreach ($this->getUpdateRules() as $rule) {
            $table = (string) $rule['table'];
            $field = (string) $rule['field'];

            $affected = $conn->executeStatement(
                "UPDATE {$table} SET {$field} = :targetId WHERE {$field} = :sourceId",
                ['targetId' => $targetId, 'sourceId' => $sourceId]
            );

            $this->log(LogLevel::ERROR, 'Update rule executed.', [
                'table' => $table,
                'field' => $field,
                'affected' => $affected,
            ]);
        }

        foreach ($this->getDeleteRules() as $rule) {
            $table = (string) $rule['table'];
            $field = (string) $rule['field'];

            $affected = $conn->executeStatement(
                "DELETE FROM {$table} WHERE {$field} = :sourceId",
                ['sourceId' => $sourceId]
            );

            $this->log(LogLevel::ERROR, 'Delete rule executed.', [
                'table' => $table,
                'field' => $field,
                'affected' => $affected,
            ]);
        }

        $this->log(LogLevel::ERROR, 'Reassign step finished.');
    }

    private function pivotMerge(
        Connection $conn,
        string $table,
        string $userField,
        int $sourceId,
        int $targetId,
        array $matchColumns
    ): void {
        $this->log(LogLevel::ERROR, 'pivotMerge() start.', [
            'table' => $table,
            'userField' => $userField,
            'sourceId' => $sourceId,
            'targetId' => $targetId,
            'matchColumns' => $matchColumns,
        ]);

        $colsMeta = $this->listTableColumnsMeta($conn, $table);
        if (empty($colsMeta)) {
            throw new RuntimeException(self::LOG_PREFIX." Unable to list columns for table '{$table}'.");
        }

        $insertCols = $this->getInsertableColumns($colsMeta);

        if (!\in_array($userField, $insertCols, true)) {
            throw new RuntimeException(self::LOG_PREFIX." Column '{$userField}' not found in '{$table}'.");
        }

        $allColNames = array_keys($colsMeta);
        $matchColumns = array_values(array_filter($matchColumns, static fn ($c) => \is_string($c) && '' !== $c));

        foreach ($matchColumns as $mc) {
            if (!\in_array($mc, $allColNames, true)) {
                throw new RuntimeException(self::LOG_PREFIX." Match column '{$mc}' not found in '{$table}'.");
            }
        }

        if (empty($matchColumns)) {
            $matchColumns = array_values(array_filter(
                $insertCols,
                static fn (string $c): bool => $c !== $userField
            ));
        }

        $insertColsSql = implode(', ', $insertCols);

        $selectExpr = [];
        foreach ($insertCols as $c) {
            $selectExpr[] = ($c === $userField) ? (':targetId AS '.$userField) : ('s.'.$c);
        }
        $selectSql = implode(', ', $selectExpr);

        $pairs = [];
        foreach ($matchColumns as $mc) {
            $pairs[] = "t.{$mc} = s.{$mc}";
        }
        $matchSql = ' AND '.implode(' AND ', $pairs);

        $sql = "
            INSERT INTO {$table} ({$insertColsSql})
            SELECT {$selectSql}
            FROM {$table} s
            WHERE s.{$userField} = :sourceId
              AND NOT EXISTS (
                SELECT 1
                FROM {$table} t
                WHERE t.{$userField} = :targetId
                {$matchSql}
              )
        ";

        $inserted = $conn->executeStatement($sql, [
            'sourceId' => $sourceId,
            'targetId' => $targetId,
        ]);

        $deleted = $conn->executeStatement(
            "DELETE FROM {$table} WHERE {$userField} = :sourceId",
            ['sourceId' => $sourceId]
        );

        $this->log(LogLevel::ERROR, 'pivotMerge() done.', [
            'table' => $table,
            'inserted' => $inserted,
            'deleted' => $deleted,
        ]);
    }

    private function mergeExtraFieldValues(Connection $conn, int $sourceId, int $targetId): void
    {
        $this->log(LogLevel::ERROR, 'mergeExtraFieldValues() start.', [
            'sourceId' => $sourceId,
            'targetId' => $targetId,
        ]);

        $table = 'extra_field_values';
        $alias = 'v';

        $colsMeta = $this->listTableColumnsMeta($conn, $table);
        if (empty($colsMeta)) {
            throw new RuntimeException(self::LOG_PREFIX." Unable to list columns for table '{$table}'.");
        }

        $insertCols = $this->getInsertableColumns($colsMeta);

        $itemCol = $this->pickColumn($insertCols, ['item_id', 'user_id']);
        $fieldCol = $this->pickColumn($insertCols, ['field_id']);

        $insertColsSql = implode(', ', $insertCols);

        $selectExpr = [];
        foreach ($insertCols as $c) {
            $selectExpr[] = ($c === $itemCol) ? (':targetId AS '.$itemCol) : ($alias.'.'.$c);
        }
        $selectSql = implode(', ', $selectExpr);

        $sql = "
            INSERT INTO {$table} ({$insertColsSql})
            SELECT {$selectSql}
            FROM {$table} {$alias}
            INNER JOIN extra_field f ON f.id = {$alias}.{$fieldCol}
            WHERE f.item_type = :userType
              AND {$alias}.{$itemCol} = :sourceId
              AND NOT EXISTS (
                SELECT 1
                FROM {$table} v2
                WHERE v2.{$fieldCol} = {$alias}.{$fieldCol}
                  AND v2.{$itemCol} = :targetId
              )
        ";

        $inserted = $conn->executeStatement($sql, [
            'sourceId' => $sourceId,
            'targetId' => $targetId,
            'userType' => ExtraField::USER_FIELD_TYPE,
        ]);

        $deleted = $conn->executeStatement(
            "
            DELETE {$alias} FROM {$table} {$alias}
            INNER JOIN extra_field f ON f.id = {$alias}.{$fieldCol}
            WHERE f.item_type = :userType
              AND {$alias}.{$itemCol} = :sourceId
            ",
            [
                'sourceId' => $sourceId,
                'userType' => ExtraField::USER_FIELD_TYPE,
            ]
        );

        $this->log(LogLevel::ERROR, 'mergeExtraFieldValues() done.', [
            'inserted' => $inserted,
            'deleted' => $deleted,
            'itemCol' => $itemCol,
            'fieldCol' => $fieldCol,
        ]);
    }

    private function mergeExtraFieldTags(Connection $conn, int $sourceId, int $targetId): void
    {
        $this->log(LogLevel::ERROR, 'mergeExtraFieldTags() start.', [
            'sourceId' => $sourceId,
            'targetId' => $targetId,
        ]);

        $table = 'extra_field_rel_tag';
        $alias = 'r';

        $colsMeta = $this->listTableColumnsMeta($conn, $table);
        if (empty($colsMeta)) {
            $this->log(LogLevel::ERROR, 'extra_field_rel_tag columns not found. Skipping.');

            return;
        }

        $insertCols = $this->getInsertableColumns($colsMeta);

        $itemCol = $this->pickColumn($insertCols, ['item_id', 'user_id']);
        $fieldCol = $this->pickColumn($insertCols, ['field_id']);
        $tagCol = $this->pickColumn($insertCols, ['tag_id']);

        $insertColsSql = implode(', ', $insertCols);

        $selectExpr = [];
        foreach ($insertCols as $c) {
            $selectExpr[] = ($c === $itemCol) ? (':targetId AS '.$itemCol) : ($alias.'.'.$c);
        }
        $selectSql = implode(', ', $selectExpr);

        $sql = "
            INSERT INTO {$table} ({$insertColsSql})
            SELECT {$selectSql}
            FROM {$table} {$alias}
            INNER JOIN extra_field f ON f.id = {$alias}.{$fieldCol}
            WHERE f.item_type = :userType
              AND {$alias}.{$itemCol} = :sourceId
              AND NOT EXISTS (
                SELECT 1
                FROM {$table} r2
                WHERE r2.{$fieldCol} = {$alias}.{$fieldCol}
                  AND r2.{$tagCol} = {$alias}.{$tagCol}
                  AND r2.{$itemCol} = :targetId
              )
        ";

        $inserted = $conn->executeStatement($sql, [
            'sourceId' => $sourceId,
            'targetId' => $targetId,
            'userType' => ExtraField::USER_FIELD_TYPE,
        ]);

        $deleted = $conn->executeStatement(
            "
            DELETE {$alias} FROM {$table} {$alias}
            INNER JOIN extra_field f ON f.id = {$alias}.{$fieldCol}
            WHERE f.item_type = :userType
              AND {$alias}.{$itemCol} = :sourceId
            ",
            [
                'sourceId' => $sourceId,
                'userType' => ExtraField::USER_FIELD_TYPE,
            ]
        );

        $this->log(LogLevel::ERROR, 'mergeExtraFieldTags() done.', [
            'inserted' => $inserted,
            'deleted' => $deleted,
            'itemCol' => $itemCol,
            'fieldCol' => $fieldCol,
            'tagCol' => $tagCol,
        ]);
    }

    private function markUserAsMerged(Connection $conn, int $sourceId, int $targetId): void
    {
        $this->log(LogLevel::ERROR, 'markUserAsMerged() start.', [
            'sourceId' => $sourceId,
            'targetId' => $targetId,
        ]);

        $affected = $conn->executeStatement(
            "
            UPDATE user
            SET active = :softDeleted,
                username = LEFT(CONCAT('merged_', id, '_into_', :targetId), 50),
                email = LEFT(CONCAT('merged_', id, '_into_', :targetId, '@invalid.local'), 190)
            WHERE id = :sourceId
            ",
            [
                'softDeleted' => User::SOFT_DELETED,
                'sourceId' => $sourceId,
                'targetId' => $targetId,
            ]
        );

        $this->log(LogLevel::ERROR, 'markUserAsMerged() done.', [
            'affected' => $affected,
        ]);
    }

    private function getPivotMergeRules(): array
    {
        return [
            ['table' => 'access_url_rel_user', 'userField' => 'user_id', 'matchColumns' => ['access_url_id']],

            ['table' => 'course_rel_user', 'userField' => 'user_id', 'matchColumns' => ['c_id']],
            ['table' => 'course_rel_user_catalogue', 'userField' => 'user_id', 'matchColumns' => ['c_id']],
            ['table' => 'session_rel_user', 'userField' => 'user_id', 'matchColumns' => ['session_id', 'relation_type']],
            ['table' => 'session_rel_course_rel_user', 'userField' => 'user_id', 'matchColumns' => ['session_id', 'c_id']],

            ['table' => 'usergroup_rel_user', 'userField' => 'user_id', 'matchColumns' => ['usergroup_id']],

            ['table' => 'message_rel_user', 'userField' => 'user_id', 'matchColumns' => ['message_id', 'receiver_type']],
            ['table' => 'user_rel_user', 'userField' => 'user_id', 'matchColumns' => ['friend_user_id', 'relation_type']],

            ['table' => 'skill_rel_user', 'userField' => 'user_id', 'matchColumns' => ['skill_id']],
        ];
    }

    private function getUpdateRules(): array
    {
        return [
            ['table' => 'attempt_feedback', 'field' => 'user_id'],
            ['table' => 'chat', 'field' => 'to_user'],
            ['table' => 'chat_video', 'field' => 'to_user'],
            ['table' => 'course_request', 'field' => 'user_id'],
            ['table' => 'c_forum_post', 'field' => 'poster_id'],
            ['table' => 'c_forum_thread', 'field' => 'thread_poster_id'],
            ['table' => 'c_forum_thread_qualify', 'field' => 'user_id'],
            ['table' => 'c_forum_thread_qualify_log', 'field' => 'user_id'],
            ['table' => 'resource_comment', 'field' => 'author_id'],
            ['table' => 'page_category', 'field' => 'creator_id'],
            ['table' => 'sequence_value', 'field' => 'user_id'],
            ['table' => 'message', 'field' => 'user_sender_id'],
            ['table' => 'social_post', 'field' => 'sender_id'],
            ['table' => 'social_post', 'field' => 'user_receiver_id'],
            ['table' => 'social_post_feedback', 'field' => 'user_id'],

            ['table' => 'track_e_default', 'field' => 'default_user_id'],
            ['table' => 'track_e_access', 'field' => 'access_user_id'],
            ['table' => 'track_e_lastaccess', 'field' => 'access_user_id'],
            ['table' => 'track_e_course_access', 'field' => 'user_id'],
            ['table' => 'track_e_attempt', 'field' => 'user_id'],
            ['table' => 'track_e_login', 'field' => 'login_user_id'],
            ['table' => 'track_e_online', 'field' => 'login_user_id'],
            ['table' => 'track_e_uploads', 'field' => 'upload_user_id'],
            ['table' => 'track_e_downloads', 'field' => 'down_user_id'],
            ['table' => 'track_e_links', 'field' => 'links_user_id'],
            ['table' => 'track_e_exercises', 'field' => 'exe_user_id'],
            ['table' => 'track_e_hotpotatoes', 'field' => 'exe_user_id'],
            ['table' => 'track_e_hotspot', 'field' => 'hotspot_user_id'],

            ['table' => 'ticket_assigned_log', 'field' => 'user_id'],
            ['table' => 'ticket_assigned_log', 'field' => 'sys_insert_user_id'],
            ['table' => 'ticket_category', 'field' => 'sys_insert_user_id'],
            ['table' => 'ticket_category', 'field' => 'sys_lastedit_user_id'],
            ['table' => 'ticket_message', 'field' => 'sys_insert_user_id'],
            ['table' => 'ticket_message', 'field' => 'sys_lastedit_user_id'],
            ['table' => 'ticket_message_attachments', 'field' => 'sys_insert_user_id'],
            ['table' => 'ticket_message_attachments', 'field' => 'sys_lastedit_user_id'],
            ['table' => 'ticket_priority', 'field' => 'sys_insert_user_id'],
            ['table' => 'ticket_priority', 'field' => 'sys_lastedit_user_id'],
            ['table' => 'ticket_project', 'field' => 'sys_insert_user_id'],
            ['table' => 'ticket_project', 'field' => 'sys_lastedit_user_id'],
        ];
    }

    private function getDeleteRules(): array
    {
        return [
            ['table' => 'notification', 'field' => 'dest_user_id'],
            ['table' => 'message_tag', 'field' => 'user_id'],
        ];
    }

    /**
     * @return array<string, Column>
     */
    private function listTableColumnsMeta(Connection $conn, string $table): array
    {
        try {
            $sm = method_exists($conn, 'createSchemaManager')
                ? $conn->createSchemaManager()
                : $conn->getSchemaManager();

            return $sm->listTableColumns($table);
        } catch (DbalException $e) {
            $this->log(LogLevel::ERROR, 'Failed to list table columns (DBAL exception).', [
                'table' => $table,
                'message' => $e->getMessage(),
            ]);

            return [];
        } catch (Throwable $e) {
            $this->log(LogLevel::ERROR, 'Failed to list table columns (Throwable).', [
                'table' => $table,
                'message' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * @param array<string, Column> $colsMeta
     *
     * @return string[]
     */
    private function getInsertableColumns(array $colsMeta): array
    {
        $cols = [];
        foreach ($colsMeta as $name => $col) {
            if ($col->getAutoincrement()) {
                continue;
            }
            $cols[] = (string) $name;
        }

        return $cols;
    }

    /**
     * @param string[] $available
     * @param string[] $candidates
     */
    private function pickColumn(array $available, array $candidates): string
    {
        foreach ($candidates as $c) {
            if (\in_array($c, $available, true)) {
                return $c;
            }
        }

        throw new RuntimeException(self::LOG_PREFIX.' Required column not found. Available: '.implode(', ', $available));
    }

    private function safeRollback(Connection $conn): void
    {
        try {
            $conn->rollBack();
            $this->log(LogLevel::ERROR, 'Transaction rolled back.');
        } catch (Throwable $e) {
            $this->log(LogLevel::ERROR, 'Rollback failed.', [
                'message' => $e->getMessage(),
            ]);
        }
    }

    private function log(string $level, string $message, array $context = []): void
    {
        if (!$this->enableLogs) {
            return;
        }

        $line = self::LOG_PREFIX.' '.$message;

        try {
            $this->logger->log($level, $line, $context);
        } catch (Throwable) {
            // Ignore logger failures.
        }

        $suffix = '';
        if (!empty($context)) {
            $suffix = ' '.json_encode($context, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }
        error_log($line.$suffix);
    }
}
