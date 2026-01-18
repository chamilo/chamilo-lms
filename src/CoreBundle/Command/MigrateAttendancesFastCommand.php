<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Command;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception as DbalException;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Uid\Uuid;
use Throwable;

#[AsCommand(
    name: 'chamilo:migration:migrate-attendances-fast',
    description: 'Fast SQL migration for attendances (resource_node/resource_link) not migrated during Doctrine migrations.',
)]
final class MigrateAttendancesFastCommand extends Command
{
    public const RESOURCE_TYPE_ID_ATTENDANCE = 10;
    public const RESOURCE_TYPE_GROUP_ATTENDANCE = 10;

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly Connection $connection,
        private readonly SluggerInterface $slugger,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(
            'drop-c-item-property',
            null,
            InputOption::VALUE_NONE,
            'Drop legacy table c_item_property after successful migration (only if no pending attendances remain).'
        );

        // Alias for convenience (same behavior as --drop-c-item-property)
        $this->addOption(
            'drop-c-item-properties',
            null,
            InputOption::VALUE_NONE,
            'Alias of --drop-c-item-property (drops legacy table c_item_property after successful migration, only if no pending attendances remain).'
        );

        $this->addOption(
            'drop-c-id-session-id-from-c-attendance',
            null,
            InputOption::VALUE_NONE,
            'Drop legacy columns c_attendance.c_id and c_attendance.session_id after successful migration (only if no pending attendances remain).'
        );

        $this->addOption(
            'dry-run',
            null,
            InputOption::VALUE_NONE,
            'Preview mode: prints the computed resource_node.path and rolls back the transaction (no data persisted).'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Fast attendances migration');

        $dryRun = (bool) $input->getOption('dry-run');

        // Accept both flags as the same action (alias)
        $dropItemProperty = (bool) $input->getOption('drop-c-item-property')
            || (bool) $input->getOption('drop-c-item-properties');

        $dropAttendanceLegacyColumns = (bool) $input->getOption('drop-c-id-session-id-from-c-attendance');

        if ($dryRun && $dropItemProperty) {
            $io->note('Dry-run enabled: ignoring --drop-c-item-property (no schema changes will be applied).');
            $dropItemProperty = false;
        }

        if ($dryRun && $dropAttendanceLegacyColumns) {
            $io->note('Dry-run enabled: ignoring --drop-c-id-session-id-from-c-attendance (no schema changes will be applied).');
            $dropAttendanceLegacyColumns = false;
        }

        $fallbackAdminId = $this->getFallbackAdminId();
        $uuidIsBinary = $this->detectUuidIsBinary();

        $hasItemProperty = $this->tableExists('c_item_property');

        // We rely on c_attendance.c_id to map attendances to courses (c_item_property.c_id is ignored).
        $hasAttendanceCId = $this->tableHasColumn('c_attendance', 'c_id');
        $hasAttendanceLegacyId = $this->tableHasColumn('c_attendance', 'id');
        $hasAttendanceSessionId = $this->tableHasColumn('c_attendance', 'session_id');

        $hasAttendanceTitle = $this->tableHasColumn('c_attendance', 'title');
        $hasAttendanceName = $this->tableHasColumn('c_attendance', 'name');

        if (!$hasAttendanceCId) {
            $io->error('Cannot map attendances to courses: c_attendance.c_id is missing. This command expects c_id to be available in c_attendance.');

            return Command::FAILURE;
        }

        if (!$hasAttendanceSessionId) {
            $io->note('c_attendance.session_id is not available. Session context will be stored as NULL in resource_link.');
        }

        // Respect the same env-flag used during Doctrine migrations (only migrate gradebook-linked attendances).
        $skipAttendances = (bool) getenv(DoctrineMigrationsMigrateCommandDecorator::SKIP_ATTENDANCES_FLAG);
        $gradebookIds = [];

        if ($skipAttendances) {
            $io->note('SKIP_ATTENDANCES flag detected: only gradebook-linked attendances will be migrated.');

            // gradebook_link.type=7 (attendance). Some datasets may link to attendance.iid or attendance.id.
            $join = 'a.iid = gl.ref_id';
            if ($hasAttendanceLegacyId) {
                $join = '(a.iid = gl.ref_id OR a.id = gl.ref_id)';
            }

            $ids = $this->connection->fetchFirstColumn(
                "SELECT DISTINCT a.iid
                 FROM gradebook_link gl
                 INNER JOIN c_attendance a ON {$join}
                 WHERE gl.type = 7"
            );

            $ids = array_map('intval', $ids);
            $gradebookIds = array_fill_keys($ids, true);
        }

        $courseIds = $this->getCourseIdsToProcess();

        if (0 === \count($courseIds)) {
            $io->success('No attendances to migrate (nothing pending).');

            if ($dropItemProperty) {
                $this->maybeDropItemProperty($io);
            }

            if ($dropAttendanceLegacyColumns) {
                $this->maybeDropAttendanceLegacyColumns($io);
            }

            return Command::SUCCESS;
        }

        if ($dryRun) {
            $io->warning('Dry-run enabled: all changes will be rolled back. Only paths will be printed.');
        }

        $processedCourses = 0;
        $processedAttendances = 0;

        foreach ($courseIds as $courseId) {
            $courseId = (int) $courseId;

            $courseRow = $this->connection->fetchAssociative(
                'SELECT id, resource_node_id FROM course WHERE id = :id',
                ['id' => $courseId]
            );

            if (!$courseRow) {
                $io->warning("Course {$courseId} not found - skipping.");

                continue;
            }

            $courseResourceNodeId = isset($courseRow['resource_node_id']) ? (int) $courseRow['resource_node_id'] : 0;
            if ($courseResourceNodeId <= 0) {
                $io->warning("Course {$courseId} has no resource_node_id - skipping.");

                continue;
            }

            $courseNode = $this->connection->fetchAssociative(
                'SELECT id, path, level FROM resource_node WHERE id = :id',
                ['id' => $courseResourceNodeId]
            );

            if (!$courseNode) {
                $io->warning("Course {$courseId} resource_node {$courseResourceNodeId} not found - skipping.");

                continue;
            }

            $coursePath = rtrim((string) ($courseNode['path'] ?? ''), '/');

            $attendanceRows = $this->fetchPendingAttendancesForCourse(
                courseId: $courseId,
                hasAttendanceTitle: $hasAttendanceTitle,
                hasAttendanceName: $hasAttendanceName,
                hasAttendanceSessionId: $hasAttendanceSessionId,
                hasAttendanceLegacyId: $hasAttendanceLegacyId
            );

            if (0 === \count($attendanceRows)) {
                continue;
            }

            $io->section("Course {$courseId}: processing ".\count($attendanceRows).' attendances');

            $displayOrder = 0;
            $this->connection->beginTransaction();

            try {
                foreach ($attendanceRows as $row) {
                    $attendanceId = (int) $row['iid'];

                    if ($skipAttendances && !isset($gradebookIds[$attendanceId])) {
                        continue;
                    }

                    $attendanceTitle = $this->pickAttendanceTitle($row, $attendanceId);

                    $attendanceLegacyId = null;
                    if ($hasAttendanceLegacyId && isset($row['legacy_id']) && null !== $row['legacy_id']) {
                        $legacy = (int) $row['legacy_id'];
                        $attendanceLegacyId = $legacy > 0 ? $legacy : null;
                    }

                    // IMPORTANT:
                    // - We ignore c_item_property.session_id because it can be incoherent.
                    // - We store session context using c_attendance.session_id (when available).
                    $attendanceSessionId = null;
                    if ($hasAttendanceSessionId && isset($row['attendance_session_id']) && null !== $row['attendance_session_id']) {
                        $tmp = (int) $row['attendance_session_id'];
                        $attendanceSessionId = $tmp > 0 ? $tmp : null;
                    }

                    // Metadata from c_item_property:
                    // - Trust only tool + ref (and optionally legacy ref=a.id).
                    // - Do NOT filter by c_id to avoid relying on incoherent mappings.
                    $ip = [];
                    if ($hasItemProperty) {
                        $sql = "SELECT insert_date, lastedit_date, lastedit_user_id, visibility, start_visible, end_visible, to_group_id, to_user_id
                                FROM c_item_property
                                WHERE tool = 'attendance' AND ref = :iid
                                ORDER BY insert_date ASC
                                LIMIT 1";

                        $params = ['iid' => $attendanceId];

                        if (null !== $attendanceLegacyId) {
                            $sql = "SELECT insert_date, lastedit_date, lastedit_user_id, visibility, start_visible, end_visible, to_group_id, to_user_id
                                    FROM c_item_property
                                    WHERE tool = 'attendance'
                                      AND (ref = :iid OR ref = :legacyId)
                                    ORDER BY CASE WHEN ref = :iid THEN 1 ELSE 0 END DESC, insert_date ASC
                                    LIMIT 1";
                            $params['legacyId'] = $attendanceLegacyId;
                        }

                        $ip = $this->connection->fetchAssociative($sql, $params) ?: [];
                    }

                    $insertDate = $ip['insert_date'] ?? $this->nowUtc();
                    $lastEditDate = $ip['lastedit_date'] ?? $insertDate;
                    $lastEditUserId = isset($ip['lastedit_user_id']) ? (int) $ip['lastedit_user_id'] : null;

                    $visibility = isset($ip['visibility']) ? (int) $ip['visibility'] : 1;
                    $startVisible = $ip['start_visible'] ?? null;
                    $endVisible = $ip['end_visible'] ?? null;

                    $toGroupId = isset($ip['to_group_id']) ? (int) $ip['to_group_id'] : null;
                    $toUserId = isset($ip['to_user_id']) ? (int) $ip['to_user_id'] : null;

                    $creatorId = $lastEditUserId ?: $fallbackAdminId;

                    $uuid = Uuid::v4();
                    $uuidValue = $uuidIsBinary ? $uuid->toBinary() : $uuid->toRfc4122();

                    // Keep the slug stable and unique by appending the iid.
                    $slug = (string) $this->slugger->slug($attendanceTitle.'-'.$attendanceId)->lower();

                    $resourceNodeId = $this->insertResourceNode(
                        title: $attendanceTitle,
                        slug: $slug,
                        level: 3,
                        createdAt: $insertDate,
                        updatedAt: $lastEditDate,
                        uuid: $uuidValue,
                        uuidIsBinary: $uuidIsBinary,
                        resourceTypeId: self::RESOURCE_TYPE_ID_ATTENDANCE,
                        creatorId: $creatorId,
                        parentId: $courseResourceNodeId
                    );

                    $this->connection->insert('resource_link', [
                        'visibility' => $visibility,
                        'start_visibility_at' => $startVisible,
                        'end_visibility_at' => $endVisible,
                        'display_order' => $displayOrder,
                        'resource_type_group' => self::RESOURCE_TYPE_GROUP_ATTENDANCE,
                        'deleted_at' => null,
                        'created_at' => $insertDate,
                        'updated_at' => $lastEditDate,
                        'resource_node_id' => $resourceNodeId,
                        'parent_id' => null,
                        'c_id' => $courseId,
                        'session_id' => $attendanceSessionId,
                        'usergroup_id' => null,
                        'group_id' => $toGroupId,
                        'user_id' => $toUserId,
                    ]);

                    // resource_node.path format:
                    // <parentPath>/<title>-<attendanceIid>-<nodeId>/
                    $segmentTitle = trim(str_replace(['/', '\\'], '-', $attendanceTitle));
                    $segmentTitle = preg_replace('/\s+/', ' ', $segmentTitle) ?: $segmentTitle;

                    $newPath = $coursePath.'/'.$segmentTitle.'-'.$attendanceId.'-'.$resourceNodeId.'/';

                    if ($dryRun) {
                        $io->writeln(\sprintf('  - Attendance %d -> path: %s', $attendanceId, $newPath));
                    }

                    $this->connection->update('resource_node', ['path' => $newPath], ['id' => $resourceNodeId]);

                    // Mark attendance as migrated by storing the new resource_node_id.
                    $this->connection->update('c_attendance', ['resource_node_id' => $resourceNodeId], ['iid' => $attendanceId]);

                    $displayOrder++;
                    $processedAttendances++;
                }

                if ($dryRun) {
                    $this->connection->rollBack();
                    $io->note("Dry-run: rolled back changes for course {$courseId}.");
                } else {
                    $this->connection->commit();
                }

                $processedCourses++;
            } catch (Throwable $e) {
                try {
                    $this->connection->rollBack();
                } catch (Throwable) {
                    // Ignore rollback failures.
                }

                $io->error("Course {$courseId}: transaction failed - ".$e->getMessage());

                return Command::FAILURE;
            }
        }

        if ($dryRun) {
            $io->success("Dry-run finished. Courses processed: {$processedCourses}. Attendances simulated: {$processedAttendances}.");

            return Command::SUCCESS;
        }

        $io->success("Done. Courses processed: {$processedCourses}. Attendances migrated: {$processedAttendances}.");

        if ($dropItemProperty) {
            $this->maybeDropItemProperty($io);
        } else {
            if ($this->tableExists('c_item_property')) {
                $io->note('c_item_property still exists. You can drop it later or rerun this command with --drop-c-item-property once you confirm no pending attendances remain.');
            }
        }

        if ($dropAttendanceLegacyColumns) {
            $this->maybeDropAttendanceLegacyColumns($io);
        } else {
            if ($this->tableHasColumn('c_attendance', 'c_id') || $this->tableHasColumn('c_attendance', 'session_id')) {
                $io->note('c_attendance legacy columns still exist. You can drop them later or rerun this command with --drop-c-id-session-id-from-c-attendance once you confirm no pending attendances remain.');
            }
        }

        return Command::SUCCESS;
    }

    private function getCourseIdsToProcess(): array
    {
        // We rely on c_attendance.c_id to identify the course ownership.
        return $this->connection->fetchFirstColumn(
            'SELECT DISTINCT c_id
             FROM c_attendance
             WHERE resource_node_id IS NULL
               AND c_id IS NOT NULL
             ORDER BY c_id'
        );
    }

    private function fetchPendingAttendancesForCourse(
        int $courseId,
        bool $hasAttendanceTitle,
        bool $hasAttendanceName,
        bool $hasAttendanceSessionId,
        bool $hasAttendanceLegacyId
    ): array {
        $selectTitle = $hasAttendanceTitle ? 'a.title' : 'NULL AS title';
        $selectName = $hasAttendanceName ? 'a.name' : 'NULL AS name';
        $selectSession = $hasAttendanceSessionId ? 'a.session_id AS attendance_session_id' : 'NULL AS attendance_session_id';
        $selectLegacyId = $hasAttendanceLegacyId ? 'a.id AS legacy_id' : 'NULL AS legacy_id';

        return $this->connection->fetchAllAssociative(
            "SELECT a.iid, {$selectTitle}, {$selectName}, {$selectSession}, {$selectLegacyId}
             FROM c_attendance a
             WHERE a.c_id = :cid
               AND a.resource_node_id IS NULL
             ORDER BY a.iid",
            ['cid' => $courseId]
        );
    }

    private function pickAttendanceTitle(array $row, int $attendanceId): string
    {
        $title = isset($row['title']) ? (string) $row['title'] : '';
        if ('' !== trim($title)) {
            return $title;
        }

        $name = isset($row['name']) ? (string) $row['name'] : '';
        if ('' !== trim($name)) {
            return $name;
        }

        return 'Attendance '.$attendanceId;
    }

    /**
     * Drops legacy table c_item_property.
     * Only runs if no pending attendances remain.
     */
    private function maybeDropItemProperty(SymfonyStyle $io): void
    {
        if (!$this->tableExists('c_item_property')) {
            $io->note('Legacy table "c_item_property" does not exist - nothing to drop.');

            return;
        }

        $pending = (int) $this->connection->fetchOne('SELECT COUNT(*) FROM c_attendance WHERE resource_node_id IS NULL');
        if ($pending > 0) {
            $io->warning("Not dropping legacy table \"c_item_property\": {$pending} attendances are still pending (resource_node_id IS NULL).");

            return;
        }

        $io->section('Dropping legacy table "c_item_property"...');

        try {
            $sm = $this->connection->createSchemaManager();
            $sm->dropTable('c_item_property');

            $io->success('Legacy table "c_item_property" dropped.');
        } catch (DbalException $e) {
            $io->error('Failed to drop legacy table "c_item_property": '.$e->getMessage());
        }
    }

    /**
     * Drops legacy columns from c_attendance.
     * Only runs if no pending attendances remain.
     */
    private function maybeDropAttendanceLegacyColumns(SymfonyStyle $io): void
    {
        if (!$this->tableExists('c_attendance')) {
            $io->note('Table "c_attendance" does not exist - nothing to drop.');

            return;
        }

        $pending = (int) $this->connection->fetchOne('SELECT COUNT(*) FROM c_attendance WHERE resource_node_id IS NULL');
        if ($pending > 0) {
            $io->warning("Not dropping legacy columns from c_attendance: {$pending} attendances are still pending (resource_node_id IS NULL).");

            return;
        }

        $sm = $this->connection->createSchemaManager();
        $table = $sm->introspectTable('c_attendance');

        $columnsToDrop = ['c_id', 'session_id'];
        $dropList = [];

        foreach ($columnsToDrop as $col) {
            if ($table->hasColumn($col)) {
                $dropList[] = $col;
            }
        }

        if (0 === \count($dropList)) {
            $io->note('c_attendance does not have legacy columns c_id/session_id - nothing to drop.');

            return;
        }

        $io->section('Dropping legacy columns from c_attendance...');

        try {
            foreach ($dropList as $col) {
                // Keep it explicit to avoid relying on non-portable "IF EXISTS" syntax.
                $this->connection->executeStatement("ALTER TABLE c_attendance DROP COLUMN {$col}");
                $io->writeln(" - Dropped column c_attendance.{$col}");
            }

            $io->success('Legacy columns dropped from c_attendance.');
        } catch (DbalException $e) {
            $io->error('Failed to drop legacy columns from c_attendance: '.$e->getMessage());
        } catch (Throwable $e) {
            $io->error('Failed to drop legacy columns from c_attendance: '.$e->getMessage());
        }
    }

    private function getFallbackAdminId(): int
    {
        $id = $this->connection->fetchOne(
            'SELECT id FROM user WHERE roles LIKE :role LIMIT 1',
            ['role' => '%ROLE_ADMIN%']
        );

        return $id ? (int) $id : 1;
    }

    private function detectUuidIsBinary(): bool
    {
        try {
            $sm = $this->connection->createSchemaManager();
            $table = $sm->introspectTable('resource_node');
            if (!$table->hasColumn('uuid')) {
                return false;
            }

            $col = $table->getColumn('uuid');
            $type = $col->getType()->getName();
            $len = $col->getLength();

            return \in_array($type, ['binary', 'varbinary'], true) || 16 === $len;
        } catch (Throwable) {
            return false;
        }
    }

    private function tableExists(string $tableName): bool
    {
        try {
            $sm = $this->connection->createSchemaManager();

            return \in_array($tableName, $sm->listTableNames(), true);
        } catch (Throwable) {
            return false;
        }
    }

    private function tableHasColumn(string $tableName, string $columnName): bool
    {
        try {
            $sm = $this->connection->createSchemaManager();
            $table = $sm->introspectTable($tableName);

            return $table->hasColumn($columnName);
        } catch (Throwable) {
            return false;
        }
    }

    private function nowUtc(): string
    {
        return gmdate('Y-m-d H:i:s');
    }

    private function insertResourceNode(
        string $title,
        string $slug,
        int $level,
        ?string $createdAt,
        ?string $updatedAt,
        string $uuid,
        bool $uuidIsBinary,
        int $resourceTypeId,
        int $creatorId,
        int $parentId
    ): int {
        $data = [
            'title' => $title,
            'slug' => $slug,
            'level' => $level,
            'path' => null,
            'created_at' => $createdAt,
            'updated_at' => $updatedAt,
            'public' => 0,
            'uuid' => $uuid,
            'resource_type_id' => $resourceTypeId,
            'resource_format_id' => null,
            'language_id' => null,
            'creator_id' => $creatorId,
            'parent_id' => $parentId,
        ];

        $types = [];
        if ($uuidIsBinary) {
            $types['uuid'] = ParameterType::BINARY;
        }

        if ($this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform) {
            $sql = 'INSERT INTO resource_node (title, slug, level, path, created_at, updated_at, public, uuid, resource_type_id, resource_format_id, language_id, creator_id, parent_id)
                    VALUES (:title, :slug, :level, :path, :created_at, :updated_at, :public, :uuid, :resource_type_id, :resource_format_id, :language_id, :creator_id, :parent_id)
                    RETURNING id';

            return (int) $this->connection->fetchOne($sql, $data, $types);
        }

        $this->connection->insert('resource_node', $data, $types);

        return (int) $this->connection->lastInsertId();
    }
}
