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

#[AsCommand(
    name: 'chamilo:migration:migrate-attendances-fast',
    description: 'Fast SQL migration for attendances (resource_node/resource_link) not migrated during Doctrine migrations.',
)]
final class MigrateAttendancesFastCommand extends Command
{
    private const RESOURCE_TYPE_ID_ATTENDANCE = 10;
    private const RESOURCE_TYPE_GROUP_ATTENDANCE = 10;

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
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Fast attendances migration');

        // Accept both flags as the same action (alias)
        $dropItemProperty = (bool) $input->getOption('drop-c-item-property')
            || (bool) $input->getOption('drop-c-item-properties');

        $fallbackAdminId = $this->getFallbackAdminId();
        $uuidIsBinary = $this->detectUuidIsBinary();

        $hasItemProperty = $this->tableExists('c_item_property');
        $hasAttendanceCId = $this->tableHasColumn('c_attendance', 'c_id');
        $hasAttendanceTitle = $this->tableHasColumn('c_attendance', 'title');
        $hasAttendanceName = $this->tableHasColumn('c_attendance', 'name');

        if (!$hasItemProperty && !$hasAttendanceCId) {
            $io->error('Cannot determine attendance->course mapping: c_item_property does not exist and c_attendance.c_id does not exist.');
            return Command::FAILURE;
        }

        $courseIds = $this->getCourseIdsToProcess($hasItemProperty, $hasAttendanceCId);

        if (0 === \count($courseIds)) {
            $io->success('No attendances to migrate (nothing pending).');
            if ($dropItemProperty) {
                $this->maybeDropItemProperty($io);
            }
            return Command::SUCCESS;
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
                hasItemProperty: $hasItemProperty,
                hasAttendanceCId: $hasAttendanceCId,
                hasAttendanceTitle: $hasAttendanceTitle,
                hasAttendanceName: $hasAttendanceName
            );

            if (0 === \count($attendanceRows)) {
                continue;
            }

            $io->section("Course {$courseId}: migrating ".\count($attendanceRows).' attendances');

            $displayOrder = 0;
            $this->connection->beginTransaction();

            try {
                foreach ($attendanceRows as $row) {
                    $attendanceId = (int) $row['iid'];

                    $attendanceTitle = $this->pickAttendanceTitle($row, $attendanceId);

                    $attendanceSessionId = isset($row['session_id']) ? (int) $row['session_id'] : 0;
                    $attendanceSessionId = 0 === $attendanceSessionId ? null : $attendanceSessionId;

                    $ip = [];
                    if ($hasItemProperty) {
                        $ip = $this->connection->fetchAssociative(
                            "SELECT insert_date, lastedit_date, lastedit_user_id, visibility, start_visible, end_visible, to_group_id, to_user_id
                             FROM c_item_property
                             WHERE tool = 'attendance' AND ref = :ref AND c_id = :cid
                             LIMIT 1",
                            ['ref' => $attendanceId, 'cid' => $courseId]
                        ) ?: [];
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

                    $newPath = $coursePath.'-'.$resourceNodeId.'/';
                    $this->connection->update('resource_node', ['path' => $newPath], ['id' => $resourceNodeId]);

                    $this->connection->update('c_attendance', ['resource_node_id' => $resourceNodeId], ['iid' => $attendanceId]);

                    $displayOrder++;
                    $processedAttendances++;
                }

                $this->connection->commit();
                $processedCourses++;
            } catch (\Throwable $e) {
                $this->connection->rollBack();
                $io->error("Course {$courseId}: transaction failed - ".$e->getMessage());
                return Command::FAILURE;
            }
        }

        $io->success("Done. Courses processed: {$processedCourses}. Attendances migrated: {$processedAttendances}.");

        if ($dropItemProperty) {
            $this->maybeDropItemProperty($io);
        } else {
            if ($this->tableExists('c_item_property')) {
                $io->note('c_item_property still exists. You can drop it later or rerun this command with --drop-c-item-property once you confirm no pending attendances remain.');
            }
        }

        return Command::SUCCESS;
    }

    private function getCourseIdsToProcess(bool $hasItemProperty, bool $hasAttendanceCId): array
    {
        if ($hasItemProperty) {
            return $this->connection->fetchFirstColumn(
                "SELECT DISTINCT c_id
                 FROM c_item_property
                 WHERE tool = 'attendance'
                 ORDER BY c_id"
            );
        }

        // Fallback: legacy schema still has c_attendance.c_id
        if ($hasAttendanceCId) {
            return $this->connection->fetchFirstColumn(
                "SELECT DISTINCT c_id
                 FROM c_attendance
                 WHERE resource_node_id IS NULL
                 ORDER BY c_id"
            );
        }

        return [];
    }

    private function fetchPendingAttendancesForCourse(
        int $courseId,
        bool $hasItemProperty,
        bool $hasAttendanceCId,
        bool $hasAttendanceTitle,
        bool $hasAttendanceName
    ): array {
        $selectTitle = $hasAttendanceTitle ? 'a.title' : 'NULL AS title';
        $selectName = $hasAttendanceName ? 'a.name' : 'NULL AS name';

        if ($hasItemProperty) {
            return $this->connection->fetchAllAssociative(
                "SELECT a.iid, {$selectTitle}, {$selectName}, a.session_id
                 FROM c_attendance a
                 INNER JOIN c_item_property ip
                    ON ip.tool = 'attendance'
                   AND ip.ref = a.iid
                   AND ip.c_id = :cid
                 WHERE a.resource_node_id IS NULL
                 ORDER BY a.iid",
                ['cid' => $courseId]
            );
        }

        // Fallback using legacy c_id
        if ($hasAttendanceCId) {
            return $this->connection->fetchAllAssociative(
                "SELECT a.iid, {$selectTitle}, {$selectName}, a.session_id
                 FROM c_attendance a
                 WHERE a.c_id = :cid AND a.resource_node_id IS NULL
                 ORDER BY a.iid",
                ['cid' => $courseId]
            );
        }

        return [];
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
            // DBAL generates the proper DROP TABLE for the current platform.
            $sm = $this->connection->createSchemaManager();
            $sm->dropTable('c_item_property');

            $io->success('Legacy table "c_item_property" dropped.');
        } catch (DbalException $e) {
            $io->error('Failed to drop legacy table "c_item_property": '.$e->getMessage());
            // Optional: throw $e; // if you want to fail hard
        }
    }

    private function getFallbackAdminId(): int
    {
        $id = $this->connection->fetchOne(
            "SELECT id FROM user WHERE roles LIKE :role LIMIT 1",
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
        } catch (\Throwable) {
            return false;
        }
    }

    private function tableExists(string $tableName): bool
    {
        try {
            $sm = $this->connection->createSchemaManager();
            return \in_array($tableName, $sm->listTableNames(), true);
        } catch (\Throwable) {
            return false;
        }
    }

    private function tableHasColumn(string $tableName, string $columnName): bool
    {
        try {
            $sm = $this->connection->createSchemaManager();
            $table = $sm->introspectTable($tableName);
            return $table->hasColumn($columnName);
        } catch (\Throwable) {
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
