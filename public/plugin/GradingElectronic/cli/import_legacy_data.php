<?php

/* For licensing terms, see /license.txt */

/**
 * Import Ricky Rescue legacy GradingElectronic support data into Chamilo 2.
 *
 * This importer is intentionally kept inside the existing GradingElectronic
 * plugin because the imported tables are Ricky-specific migration support data.
 *
 * It never modifies the source Ricky 1 database, never deletes destination data
 * and writes only when --execute is explicitly provided.
 *
 * Usage examples:
 *   php public/plugin/GradingElectronic/cli/import_legacy_data.php --dry-run
 *   RICKY1_DB_PASSWORD='***' php public/plugin/GradingElectronic/cli/import_legacy_data.php --execute --limit=100
 *   RICKY1_DB_PASSWORD='***' php public/plugin/GradingElectronic/cli/import_legacy_data.php --execute
 */

if ('cli' !== PHP_SAPI) {
    fwrite(STDERR, "This script can only be executed from CLI.\n");
    exit(1);
}

ini_set('memory_limit', '1024M');
set_time_limit(0);

require_once dirname(__DIR__, 3).'/main/inc/global.inc.php';

final class RickyGradingElectronicLegacyImporter
{
    private const TASK_TRACK_PROGRESS = 'track-progress';
    private const TASK_LP_SCHEDULE = 'lp-schedule';
    private const TASK_FORUM_THREAD_COMMENT = 'forum-thread-comment';
    private const TASK_USER_SESSION_TRACKING = 'user-session-tracking';
    private const TASK_UNREGISTRATION_LOG = 'unregistration-log';
    private const TASK_LP_VIEW_COMPDATE = 'lp-view-compdate';

    private const TASKS = [
        self::TASK_TRACK_PROGRESS,
        self::TASK_LP_SCHEDULE,
        self::TASK_FORUM_THREAD_COMMENT,
        self::TASK_USER_SESSION_TRACKING,
        self::TASK_UNREGISTRATION_LOG,
        self::TASK_LP_VIEW_COMPDATE,
    ];

    private PDO $sourceConnection;
    private \Doctrine\DBAL\Connection $destinationConnection;
    private bool $execute;
    private int $batchSize;
    private ?int $limit;
    /** @var string[] */
    private array $tasks;
    private bool $skipCoverage;
    private bool $strictCoverage;
    /** @var array<string, string[]> */
    private array $destinationColumnCache = [];

    /**
     * @param string[] $tasks
     */
    public function __construct(
        PDO $sourceConnection,
        \Doctrine\DBAL\Connection $destinationConnection,
        bool $execute,
        int $batchSize,
        ?int $limit,
        array $tasks,
        bool $skipCoverage,
        bool $strictCoverage
    ) {
        $this->sourceConnection = $sourceConnection;
        $this->destinationConnection = $destinationConnection;
        $this->execute = $execute;
        $this->batchSize = $batchSize;
        $this->limit = $limit;
        $this->tasks = $tasks;
        $this->skipCoverage = $skipCoverage;
        $this->strictCoverage = $strictCoverage;
    }

    public static function main(array $argv): int
    {
        $options = getopt('', [
            'help',
            'dry-run',
            'execute',
            'source-host::',
            'source-port::',
            'source-db::',
            'source-user::',
            'source-password::',
            'source-password-env::',
            'batch-size::',
            'limit::',
            'only::',
            'skip-compdate',
            'skip-coverage',
            'strict-coverage',
        ]);

        if (false === $options) {
            self::error('Invalid CLI options.');

            return 1;
        }

        if (isset($options['help'])) {
            self::printHelp();

            return 0;
        }

        $execute = isset($options['execute']);

        if ($execute && isset($options['dry-run'])) {
            self::error('Use either --execute or --dry-run, not both.');

            return 1;
        }

        $batchSize = isset($options['batch-size']) ? (int) $options['batch-size'] : 1000;

        if ($batchSize < 1 || $batchSize > 5000) {
            self::error('--batch-size must be between 1 and 5000.');

            return 1;
        }

        $limit = null;

        if (isset($options['limit']) && '' !== (string) $options['limit']) {
            $limit = (int) $options['limit'];

            if ($limit < 1) {
                self::error('--limit must be greater than zero.');

                return 1;
            }
        }

        $tasks = self::parseTasks((string) ($options['only'] ?? 'all'));

        if (isset($options['skip-compdate'])) {
            $tasks = array_values(array_filter(
                $tasks,
                static fn (string $task): bool => self::TASK_LP_VIEW_COMPDATE !== $task
            ));
        }

        if (empty($tasks)) {
            self::error('No task selected.');

            return 1;
        }

        $sourcePassword = self::resolveSourcePassword($options);
        $source = self::createSourceConnection([
            'host' => (string) ($options['source-host'] ?? '127.0.0.1'),
            'port' => (int) ($options['source-port'] ?? 3311),
            'db' => (string) ($options['source-db'] ?? 'ricky1_local'),
            'user' => (string) ($options['source-user'] ?? 'ricky1_local'),
            'password' => $sourcePassword,
        ]);

        $destination = Database::getManager()->getConnection();

        $importer = new self(
            $source,
            $destination,
            $execute,
            $batchSize,
            $limit,
            $tasks,
            isset($options['skip-coverage']),
            isset($options['strict-coverage'])
        );

        return $importer->run();
    }

    private static function printHelp(): void
    {
        echo <<<'HELP'
Import Ricky legacy GradingElectronic support data into Chamilo 2.

Default mode is dry-run. Nothing is written unless --execute is provided.

Options:
  --execute                    Write data to destination DB.
  --dry-run                    Explicit dry-run mode.
  --source-host=HOST           Ricky 1 DB host. Default: 127.0.0.1
  --source-port=PORT           Ricky 1 DB port. Default: 3311
  --source-db=DB               Ricky 1 DB name. Default: ricky1_local
  --source-user=USER           Ricky 1 DB user. Default: ricky1_local
  --source-password=PASSWORD   Ricky 1 DB password. Avoid using this in shell history.
  --source-password-env=NAME   Env var holding the Ricky 1 DB password. Default: RICKY1_DB_PASSWORD
  --batch-size=N               Rows per batch. Default: 1000. Max: 5000
  --limit=N                    Limit rows per task, useful for smoke tests.
  --only=LIST                  Comma-separated task list or all.
  --skip-compdate              Skip c_lp_view.compdate update.
  --skip-coverage              Skip source/destination ID coverage warnings.
  --strict-coverage            Fail when referenced IDs are missing in Chamilo 2.

Tasks:
  track-progress
  lp-schedule
  forum-thread-comment
  user-session-tracking
  unregistration-log
  lp-view-compdate

Examples:
  php public/plugin/GradingElectronic/cli/import_legacy_data.php --dry-run
  RICKY1_DB_PASSWORD='***' php public/plugin/GradingElectronic/cli/import_legacy_data.php --execute --limit=100
  RICKY1_DB_PASSWORD='***' php public/plugin/GradingElectronic/cli/import_legacy_data.php --execute

HELP;
    }

    /**
     * @return string[]
     */
    private static function parseTasks(string $only): array
    {
        $only = trim($only);

        if ('' === $only || 'all' === $only) {
            return self::TASKS;
        }

        $requestedTasks = array_values(array_filter(array_map('trim', explode(',', $only))));
        $unknownTasks = array_diff($requestedTasks, self::TASKS);

        if (!empty($unknownTasks)) {
            throw new InvalidArgumentException('Unknown task(s): '.implode(', ', $unknownTasks));
        }

        return $requestedTasks;
    }

    private static function resolveSourcePassword(array $options): string
    {
        if (isset($options['source-password'])) {
            return (string) $options['source-password'];
        }

        $envName = (string) ($options['source-password-env'] ?? 'RICKY1_DB_PASSWORD');
        $envValue = getenv($envName);

        if (false === $envValue) {
            return '';
        }

        return (string) $envValue;
    }

    private static function createSourceConnection(array $config): PDO
    {
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=utf8',
            $config['host'],
            $config['port'],
            $config['db']
        );

        return new PDO(
            $dsn,
            $config['user'],
            $config['password'],
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8',
            ]
        );
    }

    private static function error(string $message): void
    {
        fwrite(STDERR, '[ERROR] '.$message."\n");
    }

    private function run(): int
    {
        $this->line('Ricky GradingElectronic legacy data import');
        $this->line('Mode: '.($this->execute ? 'EXECUTE' : 'DRY-RUN'));
        $this->line('Batch size: '.$this->batchSize);

        if (null !== $this->limit) {
            $this->line('Limit per task: '.$this->limit);
        }

        $this->line('Selected tasks: '.implode(', ', $this->tasks));
        $this->line('Coverage check: '.($this->skipCoverage ? 'skipped' : ($this->strictCoverage ? 'strict' : 'warning only')));
        $this->line('');

        $this->assertDestinationSchema();
        $this->assertSourceSchema();

        if (!$this->skipCoverage) {
            $this->runCoverageChecks();
            $this->line('');
        }

        foreach ($this->tasks as $task) {
            match ($task) {
                self::TASK_TRACK_PROGRESS => $this->importTrackProgress(),
                self::TASK_LP_SCHEDULE => $this->importLpSchedule(),
                self::TASK_FORUM_THREAD_COMMENT => $this->importForumThreadComments(),
                self::TASK_USER_SESSION_TRACKING => $this->importUserSessionTracking(),
                self::TASK_UNREGISTRATION_LOG => $this->importUnregistrationLog(),
                self::TASK_LP_VIEW_COMPDATE => $this->importLpViewCompletionDate(),
                default => throw new RuntimeException('Unsupported task: '.$task),
            };
        }

        $this->line('');
        $this->line('Done.');

        return 0;
    }

    private function assertDestinationSchema(): void
    {
        $expectedTables = [];

        if ($this->hasTask(self::TASK_TRACK_PROGRESS)) {
            $expectedTables['plugin_grading_electronic_lp_completion'] = ['id', 'course_id', 'user_id', 'lp_id', 'completion_status'];
        }

        if ($this->hasTask(self::TASK_LP_SCHEDULE)) {
            $expectedTables['plugin_grading_electronic_lp_schedule'] = ['id', 'course_id', 'lp_id', 'title', 'week_day'];
        }

        if ($this->hasTask(self::TASK_FORUM_THREAD_COMMENT)) {
            $expectedTables['plugin_grading_electronic_forum_thread_comment'] = ['id', 'sender_user_id', 'receiver_user_id', 'forum_id', 'thread_id', 'comment'];
        }

        if ($this->hasTask(self::TASK_USER_SESSION_TRACKING)) {
            $expectedTables['plugin_grading_electronic_user_session_tracking'] = ['id', 'user_id', 'session_time', 'is_active'];
        }

        if ($this->hasTask(self::TASK_UNREGISTRATION_LOG)) {
            $expectedTables['plugin_grading_electronic_unregistration_log'] = ['id', 'user_id', 'course_id', 'deleted_at_legacy', 'last_access_legacy'];
        }

        if ($this->hasTask(self::TASK_LP_VIEW_COMPDATE)) {
            $expectedTables['c_lp_view'] = ['iid', 'c_id', 'lp_id', 'user_id', 'session_id', 'compdate'];
        }

        foreach ($expectedTables as $table => $columns) {
            $this->assertDestinationTableColumns($table, $columns);
        }
    }

    private function assertSourceSchema(): void
    {
        $expectedTables = [];

        if ($this->hasTask(self::TASK_TRACK_PROGRESS)) {
            $expectedTables['track_progress'] = ['progressId', 'cId', 'userId', 'lpId', 'complete'];
        }

        if ($this->hasTask(self::TASK_LP_SCHEDULE)) {
            $expectedTables['paramedic'] = ['id', 'cId', 'lpId', 'title', 'weekofday'];
        }

        if ($this->hasTask(self::TASK_FORUM_THREAD_COMMENT)) {
            $expectedTables['messagecomment'] = ['commentId', 'senderId', 'receiverId', 'forum', 'threadId', 'comment'];
        }

        if ($this->hasTask(self::TASK_USER_SESSION_TRACKING)) {
            $expectedTables['tracking_user'] = ['trackingId', 'userId', 'sessionTime', 'isActive'];
        }

        if ($this->hasTask(self::TASK_UNREGISTRATION_LOG)) {
            $expectedTables['unregister_automatic'] = ['id', 'userId', 'cId', 'dateDeleted', 'lastaccess'];
        }

        if ($this->hasTask(self::TASK_LP_VIEW_COMPDATE)) {
            $expectedTables['c_lp_view'] = ['iid', 'c_id', 'lp_id', 'user_id', 'session_id', 'compdate'];
        }

        foreach ($expectedTables as $table => $columns) {
            $this->assertSourceTableColumns($table, $columns);
        }
    }

    /**
     * @param string[] $expectedColumns
     */
    private function assertDestinationTableColumns(string $table, array $expectedColumns): void
    {
        try {
            $columns = $this->destinationConnection->fetchFirstColumn('SHOW COLUMNS FROM '.$this->quoteIdentifier($table));
        } catch (Throwable $exception) {
            throw new RuntimeException(sprintf('Destination table %s is not available: %s', $table, $exception->getMessage()));
        }

        $missingColumns = array_diff($expectedColumns, $columns);

        if (!empty($missingColumns)) {
            throw new RuntimeException(sprintf(
                'Destination table %s is missing column(s): %s',
                $table,
                implode(', ', $missingColumns)
            ));
        }
    }

    /**
     * @param string[] $expectedColumns
     */
    private function assertSourceTableColumns(string $table, array $expectedColumns): void
    {
        try {
            $statement = $this->sourceConnection->query('SHOW COLUMNS FROM '.$this->quoteIdentifier($table));
        } catch (Throwable $exception) {
            throw new RuntimeException(sprintf('Source table %s is not available: %s', $table, $exception->getMessage()));
        }

        $rows = $statement->fetchAll(PDO::FETCH_ASSOC);
        $columns = array_column($rows, 'Field');
        $missingColumns = array_diff($expectedColumns, $columns);

        if (!empty($missingColumns)) {
            throw new RuntimeException(sprintf(
                'Source table %s is missing column(s): %s',
                $table,
                implode(', ', $missingColumns)
            ));
        }
    }

    private function runCoverageChecks(): void
    {
        $this->line('Running reference coverage checks...');

        $courseIdSql = $this->buildDestinationIdSql('course', ['id']);
        $userIdSql = $this->buildDestinationIdSql('user', ['id']);
        $lpIdSql = $this->buildDestinationIdSql('c_lp', ['iid', 'id']);
        $forumIdSql = $this->buildDestinationIdSql('c_forum_forum', ['iid', 'forum_id']);
        $threadIdSql = $this->buildDestinationIdSql('c_forum_thread', ['iid', 'thread_id']);

        if ($this->hasTask(self::TASK_TRACK_PROGRESS)) {
            $this->coverageCheck('track_progress.cId -> course.id', 'SELECT DISTINCT cId FROM track_progress', $courseIdSql);
            $this->coverageCheck('track_progress.userId -> user.id', 'SELECT DISTINCT userId FROM track_progress', $userIdSql);
            $this->coverageCheck('track_progress.lpId -> c_lp.iid', 'SELECT DISTINCT lpId FROM track_progress', $lpIdSql);
        }

        if ($this->hasTask(self::TASK_LP_SCHEDULE)) {
            $this->coverageCheck('paramedic.cId -> course.id', 'SELECT DISTINCT cId FROM paramedic', $courseIdSql);
            $this->coverageCheck('paramedic.lpId -> c_lp.iid', 'SELECT DISTINCT lpId FROM paramedic', $lpIdSql);
        }

        if ($this->hasTask(self::TASK_FORUM_THREAD_COMMENT)) {
            $this->coverageCheck('messagecomment.senderId -> user.id', 'SELECT DISTINCT senderId FROM messagecomment', $userIdSql);
            $this->coverageCheck('messagecomment.receiverId -> user.id', 'SELECT DISTINCT receiverId FROM messagecomment', $userIdSql);
            $this->coverageCheck('messagecomment.forum -> c_forum_forum.iid', 'SELECT DISTINCT forum FROM messagecomment', $forumIdSql);
            $this->coverageCheck('messagecomment.threadId -> c_forum_thread.iid', 'SELECT DISTINCT threadId FROM messagecomment', $threadIdSql);
        }

        if ($this->hasTask(self::TASK_USER_SESSION_TRACKING)) {
            $this->coverageCheck('tracking_user.userId -> user.id', 'SELECT DISTINCT userId FROM tracking_user', $userIdSql);
        }

        if ($this->hasTask(self::TASK_UNREGISTRATION_LOG)) {
            $this->coverageCheck('unregister_automatic.userId -> user.id', 'SELECT DISTINCT userId FROM unregister_automatic', $userIdSql);
            $this->coverageCheck('unregister_automatic.cId -> course.id', 'SELECT DISTINCT cId FROM unregister_automatic', $courseIdSql);
        }
    }


    /**
     * Builds a safe destination id query using only columns that actually exist
     * in the destination Chamilo 2 schema. This avoids assuming legacy column
     * names such as c_lp.id, c_forum_forum.forum_id or c_forum_thread.thread_id.
     *
     * @param string[] $candidateColumns
     */
    private function buildDestinationIdSql(string $table, array $candidateColumns): string
    {
        $existingColumns = $this->getDestinationColumns($table);
        $selects = [];

        foreach ($candidateColumns as $column) {
            if (!in_array($column, $existingColumns, true)) {
                continue;
            }

            $selects[] = sprintf(
                'SELECT %s FROM %s WHERE %s IS NOT NULL',
                $this->quoteIdentifier($column),
                $this->quoteIdentifier($table),
                $this->quoteIdentifier($column)
            );
        }

        if (empty($selects)) {
            throw new RuntimeException(sprintf(
                'Destination table %s does not contain any usable id column among: %s',
                $table,
                implode(', ', $candidateColumns)
            ));
        }

        return implode(' UNION ', $selects);
    }

    /**
     * @return string[]
     */
    private function getDestinationColumns(string $table): array
    {
        if (isset($this->destinationColumnCache[$table])) {
            return $this->destinationColumnCache[$table];
        }

        $rows = $this->destinationConnection->fetchAllAssociative('SHOW COLUMNS FROM '.$this->quoteIdentifier($table));

        return $this->destinationColumnCache[$table] = array_map('strval', array_column($rows, 'Field'));
    }

    private function coverageCheck(string $label, string $sourceSql, string $destinationSql): void
    {
        $sourceIds = $this->fetchSourceIdSet($sourceSql);
        $destinationIds = $this->fetchDestinationIdSet($destinationSql);
        $missing = array_values(array_diff(array_keys($sourceIds), array_keys($destinationIds)));
        $missingCount = count($missing);
        $sourceCount = count($sourceIds);
        $destinationCount = count($destinationIds);

        if (0 === $missingCount) {
            $this->line(sprintf('[coverage] OK %s source_distinct=%d destination_distinct=%d', $label, $sourceCount, $destinationCount));

            return;
        }

        $message = sprintf(
            '[coverage] WARNING %s source_distinct=%d destination_distinct=%d missing=%d first_missing=%s',
            $label,
            $sourceCount,
            $destinationCount,
            $missingCount,
            implode(',', array_slice($missing, 0, 10))
        );
        $this->line($message);

        if ($this->strictCoverage) {
            throw new RuntimeException('Strict coverage failed: '.$label);
        }
    }

    /**
     * @return array<int, true>
     */
    private function fetchSourceIdSet(string $sql): array
    {
        $statement = $this->sourceConnection->query($sql);
        $values = [];

        while (false !== ($value = $statement->fetchColumn())) {
            if (null === $value || '' === (string) $value) {
                continue;
            }

            $values[(int) $value] = true;
        }

        return $values;
    }

    /**
     * @return array<int, true>
     */
    private function fetchDestinationIdSet(string $sql): array
    {
        $values = [];

        foreach ($this->destinationConnection->fetchFirstColumn($sql) as $value) {
            if (null === $value || '' === (string) $value) {
                continue;
            }

            $values[(int) $value] = true;
        }

        return $values;
    }

    private function importTrackProgress(): void
    {
        $this->importPrimaryKeyTable(
            self::TASK_TRACK_PROGRESS,
            'track_progress',
            'progressId',
            'plugin_grading_electronic_lp_completion',
            ['id', 'course_id', 'user_id', 'lp_id', 'completion_status'],
            ['course_id', 'user_id', 'lp_id', 'completion_status'],
            'SELECT progressId, cId, userId, lpId, complete FROM track_progress WHERE progressId > :lastId ORDER BY progressId ASC LIMIT '.$this->batchSize,
            static fn (array $row): array => [
                (int) $row['progressId'],
                (int) $row['cId'],
                (int) $row['userId'],
                (int) $row['lpId'],
                (string) $row['complete'],
            ]
        );
    }

    private function importLpSchedule(): void
    {
        $this->importPrimaryKeyTable(
            self::TASK_LP_SCHEDULE,
            'paramedic',
            'id',
            'plugin_grading_electronic_lp_schedule',
            ['id', 'course_id', 'lp_id', 'title', 'week_day'],
            ['course_id', 'lp_id', 'title', 'week_day'],
            'SELECT id, cId, lpId, title, weekofday FROM paramedic WHERE id > :lastId ORDER BY id ASC LIMIT '.$this->batchSize,
            static fn (array $row): array => [
                (int) $row['id'],
                (int) $row['cId'],
                (int) $row['lpId'],
                null === $row['title'] ? null : (string) $row['title'],
                null === $row['weekofday'] ? null : (string) $row['weekofday'],
            ]
        );
    }

    private function importForumThreadComments(): void
    {
        $this->importPrimaryKeyTable(
            self::TASK_FORUM_THREAD_COMMENT,
            'messagecomment',
            'commentId',
            'plugin_grading_electronic_forum_thread_comment',
            ['id', 'sender_user_id', 'receiver_user_id', 'forum_id', 'thread_id', 'comment'],
            ['sender_user_id', 'receiver_user_id', 'forum_id', 'thread_id', 'comment'],
            'SELECT commentId, senderId, receiverId, forum, threadId, comment FROM messagecomment WHERE commentId > :lastId ORDER BY commentId ASC LIMIT '.$this->batchSize,
            static fn (array $row): array => [
                (int) $row['commentId'],
                (int) $row['senderId'],
                (int) $row['receiverId'],
                (int) $row['forum'],
                (int) $row['threadId'],
                (string) $row['comment'],
            ]
        );
    }

    private function importUserSessionTracking(): void
    {
        $this->importPrimaryKeyTable(
            self::TASK_USER_SESSION_TRACKING,
            'tracking_user',
            'trackingId',
            'plugin_grading_electronic_user_session_tracking',
            ['id', 'user_id', 'session_time', 'is_active'],
            ['user_id', 'session_time', 'is_active'],
            'SELECT trackingId, userId, sessionTime, isActive FROM tracking_user WHERE trackingId > :lastId ORDER BY trackingId ASC LIMIT '.$this->batchSize,
            static fn (array $row): array => [
                (int) $row['trackingId'],
                (int) $row['userId'],
                (string) $row['sessionTime'],
                (int) $row['isActive'],
            ]
        );
    }

    private function importUnregistrationLog(): void
    {
        $this->importPrimaryKeyTable(
            self::TASK_UNREGISTRATION_LOG,
            'unregister_automatic',
            'id',
            'plugin_grading_electronic_unregistration_log',
            ['id', 'user_id', 'course_id', 'deleted_at_legacy', 'last_access_legacy'],
            ['user_id', 'course_id', 'deleted_at_legacy', 'last_access_legacy'],
            'SELECT id, userId, cId, dateDeleted, lastaccess FROM unregister_automatic WHERE id > :lastId ORDER BY id ASC LIMIT '.$this->batchSize,
            static fn (array $row): array => [
                (int) $row['id'],
                (int) $row['userId'],
                (int) $row['cId'],
                (string) $row['dateDeleted'],
                (string) $row['lastaccess'],
            ]
        );
    }

    private function importPrimaryKeyTable(
        string $taskName,
        string $sourceTable,
        string $sourcePrimaryKey,
        string $destinationTable,
        array $destinationColumns,
        array $updateColumns,
        string $sourceSql,
        callable $mapRow
    ): void {
        $sourceCount = $this->sourceCount($sourceTable);
        $destinationCountBefore = $this->destinationCount($destinationTable);

        $this->line(sprintf(
            '[%s] source=%d destination_before=%d',
            $taskName,
            $sourceCount,
            $destinationCountBefore
        ));

        if (!$this->execute) {
            return;
        }

        $lastId = 0;
        $processed = 0;

        while (true) {
            if (null !== $this->limit && $processed >= $this->limit) {
                break;
            }

            $rows = $this->fetchSourceRows($sourceSql, ['lastId' => $lastId]);

            if (empty($rows)) {
                break;
            }

            $mappedRows = [];

            foreach ($rows as $row) {
                $lastId = max($lastId, (int) $row[$sourcePrimaryKey]);

                if (null !== $this->limit && $processed + count($mappedRows) >= $this->limit) {
                    continue;
                }

                $mappedRows[] = $mapRow($row);
            }

            if (!empty($mappedRows)) {
                $this->bulkUpsert(
                    $destinationTable,
                    $destinationColumns,
                    $updateColumns,
                    $mappedRows
                );
                $processed += count($mappedRows);
            }

            $this->line(sprintf('[%s] processed=%d last_id=%d', $taskName, $processed, $lastId));
        }

        $destinationCountAfter = $this->destinationCount($destinationTable);
        $this->line(sprintf(
            '[%s] completed processed=%d destination_after=%d',
            $taskName,
            $processed,
            $destinationCountAfter
        ));
    }

    private function importLpViewCompletionDate(): void
    {
        $sourceCount = $this->sourceCount('c_lp_view', 'compdate IS NOT NULL');
        $destinationCountBefore = $this->destinationCount('c_lp_view', 'compdate IS NOT NULL');

        $this->line(sprintf(
            '[%s] source_compdate=%d destination_compdate_before=%d',
            self::TASK_LP_VIEW_COMPDATE,
            $sourceCount,
            $destinationCountBefore
        ));

        if (!$this->execute) {
            return;
        }

        $lastId = 0;
        $processed = 0;
        $updated = 0;
        $notFound = 0;

        while (true) {
            if (null !== $this->limit && $processed >= $this->limit) {
                break;
            }

            $rows = $this->fetchSourceRows(
                'SELECT iid, c_id, lp_id, user_id, session_id, compdate
                 FROM c_lp_view
                 WHERE compdate IS NOT NULL AND iid > :lastId
                 ORDER BY iid ASC
                 LIMIT '.$this->batchSize,
                ['lastId' => $lastId]
            );

            if (empty($rows)) {
                break;
            }

            foreach ($rows as $row) {
                $lastId = max($lastId, (int) $row['iid']);

                if (null !== $this->limit && $processed >= $this->limit) {
                    continue;
                }

                $affected = $this->updateLpViewCompletionDate($row);
                $updated += $affected > 0 ? 1 : 0;
                $notFound += 0 === $affected ? 1 : 0;
                $processed++;
            }

            $this->line(sprintf(
                '[%s] processed=%d updated=%d not_found=%d last_iid=%d',
                self::TASK_LP_VIEW_COMPDATE,
                $processed,
                $updated,
                $notFound,
                $lastId
            ));
        }

        $destinationCountAfter = $this->destinationCount('c_lp_view', 'compdate IS NOT NULL');
        $this->line(sprintf(
            '[%s] completed processed=%d updated=%d not_found=%d destination_compdate_after=%d',
            self::TASK_LP_VIEW_COMPDATE,
            $processed,
            $updated,
            $notFound,
            $destinationCountAfter
        ));
    }

    private function updateLpViewCompletionDate(array $row): int
    {
        $params = [
            'compdate' => $row['compdate'],
            'iid' => (int) $row['iid'],
            'c_id' => (int) $row['c_id'],
            'lp_id' => (int) $row['lp_id'],
            'user_id' => (int) $row['user_id'],
            'session_id' => (int) $row['session_id'],
        ];

        $affected = $this->destinationConnection->executeStatement(
            'UPDATE c_lp_view
             SET compdate = :compdate
             WHERE iid = :iid
               AND c_id = :c_id
               AND lp_id = :lp_id
               AND user_id = :user_id
               AND session_id = :session_id',
            $params
        );

        if ($affected > 0) {
            return $affected;
        }

        return $this->destinationConnection->executeStatement(
            'UPDATE c_lp_view
             SET compdate = :compdate
             WHERE c_id = :c_id
               AND lp_id = :lp_id
               AND user_id = :user_id
               AND session_id = :session_id',
            [
                'compdate' => $row['compdate'],
                'c_id' => (int) $row['c_id'],
                'lp_id' => (int) $row['lp_id'],
                'user_id' => (int) $row['user_id'],
                'session_id' => (int) $row['session_id'],
            ]
        );
    }

    private function fetchSourceRows(string $sql, array $params = []): array
    {
        $statement = $this->sourceConnection->prepare($sql);

        foreach ($params as $name => $value) {
            $statement->bindValue(':'.$name, $value, PDO::PARAM_INT);
        }

        $statement->execute();

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    private function sourceCount(string $table, string $where = ''): int
    {
        $sql = 'SELECT COUNT(*) AS total FROM '.$this->quoteIdentifier($table);

        if ('' !== $where) {
            $sql .= ' WHERE '.$where;
        }

        return (int) $this->sourceConnection->query($sql)->fetchColumn();
    }

    private function destinationCount(string $table, string $where = ''): int
    {
        $sql = 'SELECT COUNT(*) FROM '.$this->quoteIdentifier($table);

        if ('' !== $where) {
            $sql .= ' WHERE '.$where;
        }

        return (int) $this->destinationConnection->fetchOne($sql);
    }

    private function bulkUpsert(string $table, array $columns, array $updateColumns, array $rows): void
    {
        $quotedColumns = array_map([$this, 'quoteIdentifier'], $columns);
        $rowPlaceholder = '('.implode(', ', array_fill(0, count($columns), '?')).')';
        $placeholders = implode(', ', array_fill(0, count($rows), $rowPlaceholder));
        $updates = implode(
            ', ',
            array_map(
                fn (string $column): string => $this->quoteIdentifier($column).' = VALUES('.$this->quoteIdentifier($column).')',
                $updateColumns
            )
        );

        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES %s ON DUPLICATE KEY UPDATE %s',
            $this->quoteIdentifier($table),
            implode(', ', $quotedColumns),
            $placeholders,
            $updates
        );

        $params = [];

        foreach ($rows as $row) {
            foreach ($row as $value) {
                $params[] = $value;
            }
        }

        $this->destinationConnection->executeStatement($sql, $params);
    }

    private function hasTask(string $task): bool
    {
        return in_array($task, $this->tasks, true);
    }

    private function quoteIdentifier(string $identifier): string
    {
        return '`'.str_replace('`', '``', $identifier).'`';
    }

    private function line(string $message): void
    {
        echo $message."\n";
    }
}

try {
    exit(RickyGradingElectronicLegacyImporter::main($argv));
} catch (Throwable $exception) {
    fwrite(STDERR, '[ERROR] '.$exception->getMessage()."\n");
    exit(1);
}
