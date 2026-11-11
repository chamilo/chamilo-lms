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
    private const string LOG_PREFIX = '[UserMergeHelper]';

    /**
     * Administrative roles an account may not lose through a merge, with the roles each one implies
     * (mirrors role_hierarchy in config/packages/security.yaml).
     */
    private const array ADMIN_ROLES = [
        'ROLE_GLOBAL_ADMIN' => ['ROLE_ADMIN', 'ROLE_SESSION_MANAGER'],
        'ROLE_ADMIN' => ['ROLE_SESSION_MANAGER'],
        'ROLE_SESSION_MANAGER' => [],
    ];

    /**
     * Roles that let an account teach (ROLE_HR implies ROLE_TEACHER, the administrators imply it too).
     * A teacher is never merged into an account without any of them, i.e. into a student.
     */
    private const array TEACHING_ROLES = ['ROLE_TEACHER', 'ROLE_HR', 'ROLE_ADMIN', 'ROLE_GLOBAL_ADMIN'];

    /**
     * Columns that reference a user and are deliberately NOT moved to the surviving account. A test fails when a
     * column that references user is neither moved by a rule nor listed here, so a new table forces a decision.
     *  - credentials and sessions (moving them would hand the identity of one person to another account):
     *    user_auth_source, oauth_*, reset_password_request, push_subscription, mobile_push_installation,
     *    user_api_key;
     *  - private conversations, preferences and templates of the merged account: ai_tutor_conversation,
     *    support_bot_conversation, block, templates, extra_field_saved_search;
     *  - subscriptions to notification events: notification_event_rel_user.
     * They stay on the soft-deleted account and are deleted with it on a hard delete.
     */
    private const array LEFT_ON_MERGED_ACCOUNT = [
        'user_auth_source.user_id',
        'oauth_access_token.user_id',
        'oauth_authorization_code.user_id',
        'oauth_client_consent.user_id',
        'oauth_refresh_token.user_id',
        'reset_password_request.user_id',
        'push_subscription.user_id',
        'mobile_push_installation.user_id',
        'user_api_key.user_id',
        'ai_tutor_conversation.user_id',
        'support_bot_conversation.user_id',
        'block.user_id',
        'templates.user_id',
        'extra_field_saved_search.user_id',
        'notification_event_rel_user.user_id',
    ];
    private bool $enableLogs = false;

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UserRepository $userRepository,
        private readonly LoggerInterface $logger,
        private readonly AccessUrlScopeHelper $accessUrlScope,
    ) {}

    /**
     * Merge $mergeUserId into $keepUserId.
     *
     * @param bool|null $enableLogs optional override for this call only (default: null = keep current flag)
     * @param User|null $actor      the administrator who asked for the merge: when given, both accounts must be inside
     *                              the actor's scope (see AccessUrlScopeHelper::canEditUser()). Every web caller must
     *                              pass it; only CLI/maintenance code may omit it.
     */
    public function mergeUsers(int $keepUserId, int $mergeUserId, ?bool $enableLogs = null, bool $hardDeleteMergedUser = false, ?User $actor = null): bool
    {
        $keepUserId = (int) $keepUserId;
        $mergeUserId = (int) $mergeUserId;

        $previousEnableLogs = $this->enableLogs;
        if (null !== $enableLogs) {
            $this->enableLogs = (bool) $enableLogs;
        }

        try {
            $this->log(LogLevel::INFO, 'mergeUsers() called.', [
                'keepUserId' => $keepUserId,
                'mergeUserId' => $mergeUserId,
                'hardDeleteMergedUser' => $hardDeleteMergedUser,
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

            $refusal = $this->getRefusal($keepUser, $mergeUser, $actor);
            if (null !== $refusal) {
                $this->log(LogLevel::ERROR, 'Refused: '.$refusal, [
                    'keepUserId' => $keepUserId,
                    'mergeUserId' => $mergeUserId,
                ]);

                return false;
            }

            $conn = $this->em->getConnection();
            $conn->beginTransaction();

            try {
                $this->mergeExtraFieldValues($conn, $mergeUserId, $keepUserId);
                $this->mergeExtraFieldTags($conn, $mergeUserId, $keepUserId);
                $this->reassignUserResourcesToTargetSQL($conn, $mergeUser, $keepUser);
                $this->markUserAsMerged($conn, $mergeUserId, $keepUserId);

                $conn->commit();

                $this->log(LogLevel::INFO, 'Merge committed.', [
                    'keepUserId' => $keepUserId,
                    'mergeUserId' => $mergeUserId,
                ]);
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

            if ($hardDeleteMergedUser) {
                $this->hardDeleteUserAfterMerge($mergeUserId);
            }

            return true;
        } finally {
            $this->enableLogs = $previousEnableLogs;
        }
    }

    /**
     * Merge many users into $keepUserId in a single DB transaction.
     *
     * @param int[]     $mergeUserIds
     * @param bool|null $enableLogs   optional override for this call only (default: null = keep current flag)
     *
     * @return int Number of merged accounts
     */
    public function mergeUsersBatch(int $keepUserId, array $mergeUserIds, ?bool $enableLogs = null, bool $hardDeleteMergedUsers = false, ?User $actor = null): int
    {
        $keepUserId = (int) $keepUserId;

        $mergeUserIds = array_values(array_unique(array_map('intval', $mergeUserIds)));
        $mergeUserIds = array_values(array_filter(
            $mergeUserIds,
            static fn (int $id): bool => $id > 0 && $id !== $keepUserId
        ));

        if ($keepUserId <= 0 || empty($mergeUserIds)) {
            return 0;
        }

        $previousEnableLogs = $this->enableLogs;
        if (null !== $enableLogs) {
            $this->enableLogs = (bool) $enableLogs;
        }

        try {
            /** @var User|null $keepUser */
            $keepUser = $this->userRepository->find($keepUserId);
            if (!$keepUser) {
                $this->log(LogLevel::ERROR, 'Batch merge: keep user not found.', ['keepUserId' => $keepUserId]);

                return 0;
            }

            $this->log(LogLevel::INFO, 'Batch merge started.', [
                'keepUserId' => $keepUserId,
                'mergeUserIds' => $mergeUserIds,
                'hardDeleteMergedUsers' => $hardDeleteMergedUsers,
            ]);

            $conn = $this->em->getConnection();
            $conn->beginTransaction();

            $mergedCount = 0;

            try {
                foreach ($mergeUserIds as $mergeUserId) {
                    /** @var User|null $mergeUser */
                    $mergeUser = $this->userRepository->find($mergeUserId);
                    if (!$mergeUser) {
                        throw new RuntimeException(self::LOG_PREFIX." Batch merge: user #{$mergeUserId} not found.");
                    }

                    $refusal = $this->getRefusal($keepUser, $mergeUser, $actor);
                    if (null !== $refusal) {
                        throw new UserMergeRefusedException(self::LOG_PREFIX." Batch merge: user #{$mergeUserId} refused: ".$refusal);
                    }

                    $this->mergeExtraFieldValues($conn, $mergeUserId, $keepUserId);
                    $this->mergeExtraFieldTags($conn, $mergeUserId, $keepUserId);
                    $this->reassignUserResourcesToTargetSQL($conn, $mergeUser, $keepUser);
                    $this->markUserAsMerged($conn, $mergeUserId, $keepUserId);

                    $mergedCount++;
                }

                $conn->commit();

                $this->log(LogLevel::INFO, 'Batch merge committed.', [
                    'keepUserId' => $keepUserId,
                    'mergedCount' => $mergedCount,
                ]);
            } catch (Throwable $e) {
                $this->safeRollback($conn);

                $this->log(LogLevel::ERROR, 'Batch merge failed with exception.', [
                    'message' => $e->getMessage(),
                    'class' => $e::class,
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]);

                throw $e;
            }

            if ($hardDeleteMergedUsers && $mergedCount > 0) {
                foreach ($mergeUserIds as $mergeUserId) {
                    $this->hardDeleteUserAfterMerge((int) $mergeUserId);
                }
            }

            return $mergedCount;
        } finally {
            $this->enableLogs = $previousEnableLogs;
        }
    }

    /**
     * Why this merge is forbidden, or null when it is allowed. Messages are shown to the administrator.
     */
    private function getRefusal(User $keepUser, User $mergeUser, ?User $actor): ?string
    {
        if (null !== $actor) {
            if (!$actor->hasRole('ROLE_ADMIN') && !$actor->hasRole('ROLE_GLOBAL_ADMIN')) {
                return 'only an administrator may merge accounts.';
            }
            if ((int) $actor->getId() === (int) $mergeUser->getId()) {
                return 'you cannot merge your own account into another one.';
            }
            if (!$this->accessUrlScope->canEditUser($actor, $keepUser) || !$this->accessUrlScope->canEditUser($actor, $mergeUser)) {
                return 'both accounts must be inside the access URLs you manage.';
            }
        }

        if (!$this->canAbsorbRoles($keepUser, $mergeUser)) {
            return 'the account to merge holds administrative roles the target lacks.';
        }

        if ($this->isTeacher($mergeUser) && !$this->isTeacher($keepUser)) {
            return 'a teacher account cannot be merged into a student account (the target has no teacher role).';
        }

        return null;
    }

    private function isTeacher(User $user): bool
    {
        return [] !== array_intersect(self::TEACHING_ROLES, $user->getRoles());
    }

    /**
     * A merge soft-deletes the source and never transfers its roles, so an administrative source may only be
     * merged into a target that already holds each of its administrative roles (directly or by hierarchy).
     */
    private function canAbsorbRoles(User $target, User $source): bool
    {
        $granted = [];
        foreach ($target->getRoles() as $role) {
            $granted[$role] = true;
            foreach (self::ADMIN_ROLES[$role] ?? [] as $implied) {
                $granted[$implied] = true;
            }
        }

        foreach ($source->getRoles() as $role) {
            if (isset(self::ADMIN_ROLES[$role]) && !isset($granted[$role])) {
                return false;
            }
        }

        return true;
    }

    private function hardDeleteUserAfterMerge(int $userId): bool
    {
        $userId = (int) $userId;
        if ($userId <= 0) {
            return false;
        }

        /** @var User|null $user */
        $user = $this->userRepository->find($userId);
        if (!$user) {
            $this->log(LogLevel::INFO, 'Hard delete skipped: user not found.', ['userId' => $userId]);

            return true;
        }

        try {
            $this->log(LogLevel::INFO, 'Hard delete started after merge.', ['userId' => $userId]);
            $this->userRepository->deleteUser($user, true);
            $this->log(LogLevel::INFO, 'Hard delete completed after merge.', ['userId' => $userId]);

            return true;
        } catch (Throwable $e) {
            $this->log(LogLevel::ERROR, 'Hard delete failed after merge.', [
                'userId' => $userId,
                'message' => $e->getMessage(),
                'class' => $e::class,
            ]);

            return false;
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

        $this->repointFriendRelations($conn, $sourceId, $targetId);
        foreach ($this->getMoveKeepingTargetRules() as $rule) {
            $this->moveRowsKeepingTarget(
                $conn,
                $rule['table'],
                $rule['userField'],
                $rule['matchColumns'],
                $sourceId,
                $targetId,
                $rule['deleteConflicts']
            );
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

        $this->mergeSurveyAnswers($conn, $sourceId, $targetId);

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

    /**
     * Rows of user_rel_user where the merged account is on the friend side move to the target, unless the same
     * relation already exists; rows between the two accounts are dropped (they would become self-relations).
     * The rows where the merged account is on the user side are handled by the pivot rule.
     */
    private function repointFriendRelations(Connection $conn, int $sourceId, int $targetId): void
    {
        $conn->executeStatement(
            'DELETE FROM user_rel_user
             WHERE (user_id = :sourceId AND friend_user_id = :targetId)
                OR (user_id = :targetId AND friend_user_id = :sourceId)',
            ['sourceId' => $sourceId, 'targetId' => $targetId]
        );

        $conn->executeStatement(
            'DELETE s FROM user_rel_user s
             INNER JOIN user_rel_user t
                ON t.user_id = s.user_id AND t.relation_type = s.relation_type AND t.friend_user_id = :targetId
             WHERE s.friend_user_id = :sourceId',
            ['sourceId' => $sourceId, 'targetId' => $targetId]
        );

        $conn->executeStatement(
            'UPDATE user_rel_user SET friend_user_id = :targetId WHERE friend_user_id = :sourceId',
            ['sourceId' => $sourceId, 'targetId' => $targetId]
        );
    }

    /**
     * Moves rows to the target keeping their ids (children point to them): a source row moves unless the target
     * already has a row with the same $matchColumns (NULL-safe), in which case the target's row wins.
     * Conflicting source rows are deleted when $deleteConflicts, otherwise left on the merged account.
     *
     * @param string[] $matchColumns
     */
    private function moveRowsKeepingTarget(
        Connection $conn,
        string $table,
        string $userField,
        array $matchColumns,
        int $sourceId,
        int $targetId,
        bool $deleteConflicts
    ): void {
        $pairs = ["t.{$userField} = :targetId"];
        foreach ($matchColumns as $mc) {
            $pairs[] = "t.{$mc} <=> s.{$mc}";
        }

        $conn->executeStatement(
            "UPDATE {$table} s
             LEFT JOIN {$table} t ON ".implode(' AND ', $pairs)."
             SET s.{$userField} = :targetId
             WHERE s.{$userField} = :sourceId AND t.{$userField} IS NULL",
            ['sourceId' => $sourceId, 'targetId' => $targetId]
        );

        if ($deleteConflicts) {
            $conn->executeStatement(
                "DELETE FROM {$table} WHERE {$userField} = :sourceId",
                ['sourceId' => $sourceId]
            );
        }
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

            ['table' => 'usergroup_rel_user', 'userField' => 'user_id', 'matchColumns' => ['usergroup_id', 'relation_type']],

            ['table' => 'message_rel_user', 'userField' => 'user_id', 'matchColumns' => ['message_id', 'receiver_type']],
            ['table' => 'user_rel_user', 'userField' => 'user_id', 'matchColumns' => ['friend_user_id', 'relation_type']],

            ['table' => 'skill_rel_user', 'userField' => 'user_id', 'matchColumns' => ['skill_id']],
            ['table' => 'skill_rel_item_rel_user', 'userField' => 'user_id', 'matchColumns' => ['skill_rel_item_id']],
            ['table' => 'user_rel_tag', 'userField' => 'user_id', 'matchColumns' => ['tag_id']],

            ['table' => 'c_group_rel_user', 'userField' => 'user_id', 'matchColumns' => ['group_id']],
            ['table' => 'c_group_rel_tutor', 'userField' => 'user_id', 'matchColumns' => ['group_id']],
            ['table' => 'c_autogroup_user_invitation', 'userField' => 'user_id', 'matchColumns' => ['group_id']],
            ['table' => 'c_blog_rel_user', 'userField' => 'user_id', 'matchColumns' => ['blog_id']],
            ['table' => 'c_blog_task_rel_user', 'userField' => 'user_id', 'matchColumns' => ['task_id', 'blog_id', 'target_date']],
            ['table' => 'c_lp_rel_user', 'userField' => 'user_id', 'matchColumns' => ['lp_id', 'session_id']],
            ['table' => 'c_lp_user_access', 'userField' => 'user_id', 'matchColumns' => ['lp_id']],
            ['table' => 'c_lp_category_rel_user', 'userField' => 'user_id', 'matchColumns' => ['category_id']],
            ['table' => 'c_student_publication_rel_user', 'userField' => 'user_id', 'matchColumns' => ['work_id']],
            ['table' => 'c_student_publication_follow', 'userField' => 'user_id', 'matchColumns' => ['publication_id']],
            ['table' => 'ticket_rel_user', 'userField' => 'user_id', 'matchColumns' => ['ticket_id']],
            ['table' => 'ticket_category_rel_user', 'userField' => 'user_id', 'matchColumns' => ['category_id']],
        ];
    }

    /**
     * Tables whose rows have children (or ids referenced elsewhere) and so keep their id when moved.
     *
     * @return list<array{table: string, userField: string, matchColumns: string[], deleteConflicts: bool}>
     */
    private function getMoveKeepingTargetRules(): array
    {
        return [
            ['table' => 'gradebook_result', 'userField' => 'user_id', 'matchColumns' => ['evaluation_id'], 'deleteConflicts' => true],
            ['table' => 'gradebook_certificate', 'userField' => 'user_id', 'matchColumns' => ['cat_id'], 'deleteConflicts' => false],
            ['table' => 'c_lp_view', 'userField' => 'user_id', 'matchColumns' => ['lp_id', 'c_id', 'session_id'], 'deleteConflicts' => false],
            ['table' => 'c_survey_invitation', 'userField' => 'user_id', 'matchColumns' => ['survey_id', 'c_id', 'session_id'], 'deleteConflicts' => false],
            ['table' => 'c_attendance_result', 'userField' => 'user_id', 'matchColumns' => ['attendance_id'], 'deleteConflicts' => true],
            ['table' => 'c_attendance_sheet', 'userField' => 'user_id', 'matchColumns' => ['attendance_calendar_id'], 'deleteConflicts' => true],
            // resource_right rows hang from the link: the link keeps its id and a duplicate stays behind.
            ['table' => 'resource_link', 'userField' => 'user_id', 'matchColumns' => ['resource_node_id', 'c_id', 'session_id', 'group_id', 'usergroup_id'], 'deleteConflicts' => false],
            // One vote per learner and course: the survivor's vote wins.
            ['table' => 'user_rel_course_vote', 'userField' => 'user_id', 'matchColumns' => ['c_id', 'session_id', 'url_id'], 'deleteConflicts' => true],
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

            ['table' => 'track_e_access_complete', 'field' => 'user_id'],
            ['table' => 'gradebook_score_log', 'field' => 'user_id'],
            ['table' => 'gradebook_comment', 'field' => 'user_id'],
            ['table' => 'gradebook_result_log', 'field' => 'user_id'],
            ['table' => 'gradebook_category', 'field' => 'user_id'],
            ['table' => 'c_blog_post', 'field' => 'author_id'],
            ['table' => 'c_blog_comment', 'field' => 'author_id'],
            ['table' => 'c_blog_task', 'field' => 'author_id'],
            ['table' => 'c_blog_rating', 'field' => 'user_id'],
            ['table' => 'c_forum_thread_feedback', 'field' => 'user_id'],
            ['table' => 'c_forum_thread_feedback', 'field' => 'author_id'],
            ['table' => 'c_forum_thread_qualify', 'field' => 'qualify_user_id'],
            ['table' => 'c_lp_rel_user', 'field' => 'creator_id'],
            ['table' => 'c_notebook', 'field' => 'user_id'],
            ['table' => 'c_quiz_destination_result', 'field' => 'user_id'],
            ['table' => 'c_student_publication', 'field' => 'user_id'],
            ['table' => 'c_student_publication_comment', 'field' => 'user_id'],
            ['table' => 'ticket_ticket', 'field' => 'assigned_last_user'],
            ['table' => 'user_course_category', 'field' => 'user_id'],
            ['table' => 'portfolio_category', 'field' => 'user_id'],
            ['table' => 'track_e_exercise_confirmation', 'field' => 'user_id'],
            ['table' => 'course_invitation', 'field' => 'registered_user_id'],
            ['table' => 'course_invitation', 'field' => 'invited_user_id'],
            ['table' => 'course_invitation', 'field' => 'created_by'],
            ['table' => 'page', 'field' => 'creator_id'],

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

            // Authorship and ownership columns added by the second audit: no unique key involves them, so the rows
            // simply follow the learner (an attribution that pointed to the merged account now points to the survivor).
            ['table' => 'resource_tag', 'field' => 'author_id'],
            ['table' => 'resource_user_tag', 'field' => 'user_id'],
            ['table' => 'c_survey_template', 'field' => 'created_by_id'],
            ['table' => 'c_toolbox_version', 'field' => 'created_by_id'],
            ['table' => 'mail_template', 'field' => 'author_id'],
            ['table' => 'page_layout', 'field' => 'created_by'],
            ['table' => 'page_layout', 'field' => 'updated_by'],
            ['table' => 'support_bot_tree', 'field' => 'created_by_id'],
            ['table' => 'transcript', 'field' => 'user_id'],
            ['table' => 'transcript', 'field' => 'issued_by_id'],
            ['table' => 'user_career', 'field' => 'user_id'],
            ['table' => 'justification_document_rel_users', 'field' => 'user_id'],
            ['table' => 'gradebook_certificate_expiry_notification', 'field' => 'sent_by_user_id'],
            ['table' => 'gradebook_linkeval_log', 'field' => 'user_id_log'],
            ['table' => 'c_attendance_sheet_log', 'field' => 'lastedit_user_id'],
            ['table' => 'skill_rel_user_comment', 'field' => 'feedback_giver_id'],
            ['table' => 'skill_rel_user', 'field' => 'last_status_update_user_id'],
            ['table' => 'conference_activity', 'field' => 'participant_id'],
            ['table' => 'conference_meeting', 'field' => 'user_id'],
            ['table' => 'xapi_internal_log', 'field' => 'user_id'],
            ['table' => 'automatic_unregistration_log', 'field' => 'user_id'],
            ['table' => 'user_activity_status_archive', 'field' => 'user_id'],
            ['table' => 'third_party_data_exchange_user', 'field' => 'user_id'],
            ['table' => 'c_peer_assessment_log', 'field' => 'user_id'],
            ['table' => 'c_peer_autogroup_rel_student_publication', 'field' => 'user_id'],
            ['table' => 'c_attendance_result_comment', 'field' => 'user_id'],
            ['table' => 'c_attendance_result_comment', 'field' => 'author_user_id'],
            ['table' => 'c_dropbox_category', 'field' => 'user_id'],
            ['table' => 'c_dropbox_feedback', 'field' => 'author_user_id'],
            ['table' => 'c_dropbox_file', 'field' => 'uploader_id'],
            ['table' => 'c_dropbox_person', 'field' => 'user_id'],
            ['table' => 'c_dropbox_post', 'field' => 'dest_user_id'],
            ['table' => 'c_forum_mailcue', 'field' => 'user_id'],
            ['table' => 'c_forum_notification', 'field' => 'user_id'],
            ['table' => 'c_wiki', 'field' => 'user_id'],
            ['table' => 'c_wiki_mailcue', 'field' => 'user_id'],
            ['table' => 'c_chat_connected', 'field' => 'user_id'],
            ['table' => 'exercise_attempt_recycle_bin', 'field' => 'user_id'],
            ['table' => 'learning_path_tracking_recycle_bin', 'field' => 'user_id'],
            ['table' => 'hotpotatoes_attempt', 'field' => 'user_id'],
            ['table' => 'hotpotatoes_attempt_review', 'field' => 'reviewed_by_user_id'],
            ['table' => 'track_e_attempt_qualify', 'field' => 'author'],
            ['table' => 'usergroup', 'field' => 'author_id'],
            ['table' => 'user', 'field' => 'creator_id'],
            ['table' => 'social_post_attachments', 'field' => 'sys_insert_user_id'],
            ['table' => 'social_post_attachments', 'field' => 'sys_lastedit_user_id'],
            ['table' => 'ticket_ticket', 'field' => 'sys_insert_user_id'],
            ['table' => 'ticket_ticket', 'field' => 'sys_lastedit_user_id'],
            ['table' => 'ai_requests', 'field' => 'user_id'],
            ['table' => 'welfare_report', 'field' => 'target_user_id'],
        ];
    }

    /**
     * Columns handled by dedicated code instead of a rule list ('table.column').
     *
     * @return string[]
     */
    private function getSpecialRules(): array
    {
        return [
            'resource_node.creator_id',
            'user_rel_user.friend_user_id',
        ];
    }

    /**
     * c_survey_answer.user is a string: the user id for a named survey, a hash for an anonymous one (never equal to
     * a plain integer, so anonymous respondents are never touched). Answers follow the learner only for the surveys
     * the survivor did not answer himself, otherwise both sets would be counted in the report.
     */
    private function mergeSurveyAnswers(Connection $conn, int $sourceId, int $targetId): void
    {
        $affected = $conn->executeStatement(
            'UPDATE c_survey_answer a
             SET a.user = :targetId
             WHERE a.user = :sourceId
               AND NOT EXISTS (
                   SELECT 1 FROM (SELECT survey_id FROM c_survey_answer WHERE user = :targetId) t
                   WHERE t.survey_id <=> a.survey_id
               )',
            ['targetId' => (string) $targetId, 'sourceId' => (string) $sourceId]
        );
        $this->log(LogLevel::ERROR, 'Merged c_survey_answer.user.', ['affected' => $affected]);
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
            $sm = $conn->createSchemaManager();

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
