<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Schema;
use RuntimeException;
use Symfony\Component\Uid\Uuid;
use Throwable;

final class Version20240323181500 extends AbstractMigrationChamilo
{
    private const SYS_CALENDAR_BATCH_SIZE = 1000;
    private const MAP_TABLE = 'tmp_ricky_sys_calendar_map';
    private const RESOURCE_NODE_TITLE_MAX_LENGTH = 255;

    public function getDescription(): string
    {
        return 'Migrate sys_calendar to c_calendar_event with resumable transactional DBAL batches';
    }

    public function isTransactional(): bool
    {
        // Each batch is committed atomically and recorded in a persistent
        // mapping table, allowing a stopped migration to resume safely.
        return false;
    }

    public function up(Schema $schema): void
    {
        if (!$schema->hasTable('sys_calendar')) {
            $this->write('sys_calendar does not exist; nothing to migrate.');

            return;
        }

        $admin = $this->getAdmin();
        $adminNode = $admin->getResourceNode();
        if (null === $adminNode || null === $adminNode->getId()) {
            throw new RuntimeException('The administrator has no resource node.');
        }

        $adminId = (int) $admin->getId();
        $adminNodeId = (int) $adminNode->getId();
        $adminNodePath = trim((string) $adminNode->getPath());
        $adminNodeLevel = (int) $adminNode->getLevel();

        if ('' === $adminNodePath) {
            throw new RuntimeException('The administrator resource-node path is empty.');
        }

        $resourceTypeId = $this->resolveCalendarResourceTypeId();
        $uuidIsBinary = $this->detectUuidIsBinary();
        $eventColumns = $this->resolveEventInsertColumns();

        $this->createMappingTable();

        $total = (int) $this->connection->fetchOne(
            sprintf(
                'SELECT COUNT(*)
                 FROM sys_calendar source
                 LEFT JOIN %s mapped ON mapped.old_id = source.id
                 WHERE mapped.old_id IS NULL',
                self::MAP_TABLE
            )
        );

        if (0 === $total) {
            $this->updateAdminAgendaReminders($schema);
            $this->dropMappingTable();
            $this->write('No pending sys_calendar rows.');

            return;
        }

        $seen = 0;
        $prepared = 0;
        $startedAt = microtime(true);

        $this->write(sprintf(
            'sys_calendar DBAL phase started: pending=%d batch_size=%d uuid_binary=%s.',
            $total,
            self::SYS_CALENDAR_BATCH_SIZE,
            $uuidIsBinary ? 'yes' : 'no'
        ));

        while (true) {
            $rows = $this->connection->fetchAllAssociative(
                sprintf(
                    'SELECT source.id, source.title, source.content, source.start_date,
                            source.end_date, source.all_day, source.color
                     FROM sys_calendar source
                     LEFT JOIN %s mapped ON mapped.old_id = source.id
                     WHERE mapped.old_id IS NULL
                     ORDER BY source.id
                     LIMIT %d',
                    self::MAP_TABLE,
                    self::SYS_CALENDAR_BATCH_SIZE
                )
            );

            if ([] === $rows) {
                break;
            }

            $seen += count($rows);
            $prepared += $this->persistBatch(
                $rows,
                $eventColumns,
                $resourceTypeId,
                $adminId,
                $adminNodeId,
                $adminNodePath,
                $adminNodeLevel,
                $uuidIsBinary
            );

            $elapsed = max(1, (int) (microtime(true) - $startedAt));
            $rate = $seen / $elapsed;
            $remaining = max(0, $total - $seen);
            $lastId = (int) $rows[array_key_last($rows)]['id'];

            $this->write(sprintf(
                'sys_calendar DBAL progress: %d/%d (%.2f%%), prepared=%d rate=%.2f events/s ETA=%ds last_id=%d.',
                $seen,
                $total,
                100 * $seen / $total,
                $prepared,
                $rate,
                $rate > 0 ? (int) round($remaining / $rate) : 0,
                $lastId
            ));
        }

        $this->updateAdminAgendaReminders($schema);

        $remaining = (int) $this->connection->fetchOne(
            sprintf(
                'SELECT COUNT(*)
                 FROM sys_calendar source
                 LEFT JOIN %s mapped ON mapped.old_id = source.id
                 WHERE mapped.old_id IS NULL',
                self::MAP_TABLE
            )
        );

        if (0 !== $remaining) {
            throw new RuntimeException(sprintf(
                'sys_calendar migration finished with %d unmapped rows.',
                $remaining
            ));
        }

        $this->dropMappingTable();

        $this->write(sprintf(
            'sys_calendar DBAL phase completed: seen=%d/%d prepared=%d remaining=%d elapsed=%ds.',
            $seen,
            $total,
            $prepared,
            $remaining,
            (int) (microtime(true) - $startedAt)
        ));
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @param array<int, string>               $eventColumns
     */
    private function persistBatch(
        array $rows,
        array $eventColumns,
        int $resourceTypeId,
        int $adminId,
        int $adminNodeId,
        string $adminNodePath,
        int $adminNodeLevel,
        bool $uuidIsBinary
    ): int {
        $this->connection->beginTransaction();

        try {
            $now = gmdate('Y-m-d H:i:s');
            $nodeRows = [];
            $uuidKeysBySourceId = [];
            $sourceMetadata = [];

            foreach ($rows as $row) {
                $sourceId = (int) $row['id'];
                $eventTitle = '' !== trim((string) ($row['title'] ?? ''))
                    ? (string) $row['title']
                    : '-';
                $nodeTitle = $this->normalizeResourceTitle($eventTitle, $sourceId);
                $uuid = Uuid::v4();
                $uuidValue = $uuidIsBinary ? $uuid->toBinary() : $uuid->toRfc4122();
                $uuidKey = $uuidIsBinary ? bin2hex($uuidValue) : $uuidValue;

                $uuidKeysBySourceId[$sourceId] = $uuidKey;
                $sourceMetadata[$sourceId] = [
                    'event_title' => $eventTitle,
                    'node_title' => $nodeTitle,
                    'content' => (string) ($row['content'] ?? ''),
                    'start_date' => $this->normalizeDate($row['start_date'] ?? null),
                    'end_date' => $this->normalizeDate($row['end_date'] ?? null),
                    'all_day' => (int) ($row['all_day'] ?? 0),
                    'color' => (string) ($row['color'] ?? ''),
                ];

                $nodeRows[] = [
                    'title' => $nodeTitle,
                    'slug' => 'sys-calendar-'.$sourceId,
                    'level' => $adminNodeLevel + 1,
                    'path' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                    'public' => 0,
                    'uuid' => $uuidValue,
                    'resource_type_id' => $resourceTypeId,
                    'resource_format_id' => null,
                    'language_id' => null,
                    'creator_id' => $adminId,
                    'parent_id' => $adminNodeId,
                ];
            }

            $this->bulkInsert(
                'resource_node',
                [
                    'title', 'slug', 'level', 'path', 'created_at', 'updated_at',
                    'public', 'uuid', 'resource_type_id', 'resource_format_id',
                    'language_id', 'creator_id', 'parent_id',
                ],
                $nodeRows,
                $uuidIsBinary ? ['uuid'] : []
            );

            $nodeIdsByUuid = $this->fetchNodeIdsByUuid($nodeRows, $uuidIsBinary);
            if (count($nodeIdsByUuid) !== count($rows)) {
                throw new RuntimeException(sprintf(
                    'Expected %d calendar resource nodes, found %d.',
                    count($rows),
                    count($nodeIdsByUuid)
                ));
            }

            $eventRows = [];
            $nodeIds = [];
            $sourceIdByNodeId = [];

            foreach ($sourceMetadata as $sourceId => $metadata) {
                $uuidKey = $uuidKeysBySourceId[$sourceId];
                $resourceNodeId = $nodeIdsByUuid[$uuidKey] ?? 0;
                if ($resourceNodeId <= 0) {
                    throw new RuntimeException(sprintf(
                        'Resource node was not resolved for sys_calendar %d.',
                        $sourceId
                    ));
                }

                $nodeIds[] = $resourceNodeId;
                $sourceIdByNodeId[$resourceNodeId] = $sourceId;

                $values = [
                    'title' => $metadata['event_title'],
                    'content' => $metadata['content'],
                    'start_date' => $metadata['start_date'],
                    'end_date' => $metadata['end_date'],
                    'parent_event_id' => null,
                    'all_day' => $metadata['all_day'],
                    'comment' => null,
                    'color' => $metadata['color'],
                    'room_id' => null,
                    'resource_node_id' => $resourceNodeId,
                    'invitation_type' => null,
                    'collective' => 0,
                    'subscription_visibility' => 0,
                    'subscription_item_id' => null,
                    'max_attendees' => 0,
                    'career_id' => null,
                    'promotion_id' => null,
                ];

                $eventRow = [];
                foreach ($eventColumns as $column) {
                    $eventRow[$column] = $values[$column] ?? null;
                }
                $eventRows[] = $eventRow;
            }

            $this->bulkInsert('c_calendar_event', $eventColumns, $eventRows);

            $eventRowsByNode = $this->connection->executeQuery(
                'SELECT iid, resource_node_id
                 FROM c_calendar_event
                 WHERE resource_node_id IN (:nodeIds)',
                ['nodeIds' => $nodeIds],
                ['nodeIds' => ArrayParameterType::INTEGER]
            )->fetchAllAssociative();

            if (count($eventRowsByNode) !== count($rows)) {
                throw new RuntimeException(sprintf(
                    'Expected %d calendar events, found %d.',
                    count($rows),
                    count($eventRowsByNode)
                ));
            }

            $pathMappings = [];
            $resourceLinkRows = [];
            $mappingRows = [];

            foreach ($eventRowsByNode as $eventRow) {
                $resourceNodeId = (int) $eventRow['resource_node_id'];
                $newEventId = (int) $eventRow['iid'];
                $sourceId = $sourceIdByNodeId[$resourceNodeId] ?? 0;
                if ($sourceId <= 0) {
                    throw new RuntimeException(sprintf(
                        'Source row was not resolved for calendar resource node %d.',
                        $resourceNodeId
                    ));
                }

                $metadata = $sourceMetadata[$sourceId];
                $pathMappings[$resourceNodeId] = $this->buildResourcePath(
                    $adminNodePath,
                    $metadata['node_title'],
                    $sourceId,
                    $resourceNodeId
                );

                $resourceLinkRows[] = [
                    'resource_node_id' => $resourceNodeId,
                    'c_id' => null,
                    'session_id' => null,
                    'usergroup_id' => null,
                    'group_id' => null,
                    'user_id' => null,
                    'visibility' => ResourceLink::VISIBILITY_DRAFT,
                    'start_visibility_at' => null,
                    'end_visibility_at' => null,
                    'display_order' => $sourceId,
                    'resource_type_group' => $resourceTypeId,
                    'created_at' => $now,
                    'updated_at' => $now,
                    'deleted_at' => null,
                    'parent_id' => null,
                ];

                $mappingRows[] = [
                    'old_id' => $sourceId,
                    'new_event_id' => $newEventId,
                    'resource_node_id' => $resourceNodeId,
                ];
            }

            $this->bulkUpdateById('resource_node', 'id', 'path', $pathMappings);
            $this->bulkInsert(
                'resource_link',
                [
                    'resource_node_id', 'c_id', 'session_id', 'usergroup_id',
                    'group_id', 'user_id', 'visibility', 'start_visibility_at',
                    'end_visibility_at', 'display_order', 'resource_type_group',
                    'created_at', 'updated_at', 'deleted_at', 'parent_id',
                ],
                $resourceLinkRows
            );
            $this->bulkInsert(
                self::MAP_TABLE,
                ['old_id', 'new_event_id', 'resource_node_id'],
                $mappingRows
            );

            $this->connection->commit();

            return count($rows);
        } catch (Throwable $exception) {
            if ($this->connection->isTransactionActive()) {
                $this->connection->rollBack();
            }

            throw new RuntimeException(
                'sys_calendar DBAL batch failed: '.$exception->getMessage(),
                0,
                $exception
            );
        }
    }

    private function resolveCalendarResourceTypeId(): int
    {
        $id = $this->connection->fetchOne(
            <<<'SQL'
SELECT rn.resource_type_id
FROM c_calendar_event event
INNER JOIN resource_node rn ON rn.id = event.resource_node_id
WHERE event.resource_node_id IS NOT NULL
  AND rn.resource_type_id IS NOT NULL
GROUP BY rn.resource_type_id
ORDER BY COUNT(*) DESC, rn.resource_type_id
LIMIT 1
SQL
        );

        if (false === $id || (int) $id <= 0) {
            throw new RuntimeException(
                'No existing calendar-event resource node could provide the resource type.'
            );
        }

        return (int) $id;
    }

    /**
     * @return array<int, string>
     */
    private function resolveEventInsertColumns(): array
    {
        $table = $this->connection->createSchemaManager()->introspectTable('c_calendar_event');

        $supported = [
            'title',
            'content',
            'start_date',
            'end_date',
            'parent_event_id',
            'all_day',
            'comment',
            'color',
            'room_id',
            'resource_node_id',
            'invitation_type',
            'collective',
            'subscription_visibility',
            'subscription_item_id',
            'max_attendees',
            'career_id',
            'promotion_id',
        ];

        $columns = [];
        foreach ($supported as $name) {
            if ($table->hasColumn($name)) {
                $columns[] = $name;
            }
        }

        foreach ($table->getColumns() as $column) {
            if ($this->columnCanBeOmitted($column)) {
                continue;
            }

            if (!in_array($column->getName(), $columns, true)) {
                throw new RuntimeException(sprintf(
                    'Unsupported mandatory c_calendar_event column: %s.',
                    $column->getName()
                ));
            }
        }

        foreach (['title', 'all_day', 'resource_node_id'] as $required) {
            if (!in_array($required, $columns, true)) {
                throw new RuntimeException(sprintf(
                    'Required c_calendar_event column was not found: %s.',
                    $required
                ));
            }
        }

        return $columns;
    }

    private function columnCanBeOmitted(Column $column): bool
    {
        if ($column->getAutoincrement()) {
            return true;
        }

        if (!$column->getNotnull()) {
            return true;
        }

        return null !== $column->getDefault();
    }

    private function updateAdminAgendaReminders(Schema $schema): void
    {
        if (!$schema->hasTable('agenda_reminder')) {
            return;
        }

        $table = $schema->getTable('agenda_reminder');
        if (!$table->hasColumn('type') || !$table->hasColumn('event_id')) {
            return;
        }

        $updated = $this->connection->executeStatement(
            sprintf(
                "UPDATE agenda_reminder reminder
                 INNER JOIN %s mapped ON mapped.old_id = reminder.event_id
                 SET reminder.event_id = mapped.new_event_id
                 WHERE reminder.type = 'admin'",
                self::MAP_TABLE
            )
        );

        $this->write(sprintf('Admin agenda reminders updated: %d.', $updated));
    }

    private function createMappingTable(): void
    {
        $this->connection->executeStatement(sprintf(
            'CREATE TABLE IF NOT EXISTS %s (
                old_id INT NOT NULL,
                new_event_id INT NOT NULL,
                resource_node_id INT NOT NULL,
                PRIMARY KEY (old_id),
                UNIQUE KEY uniq_ricky_sys_calendar_event (new_event_id),
                UNIQUE KEY uniq_ricky_sys_calendar_node (resource_node_id)
            ) ENGINE=InnoDB',
            self::MAP_TABLE
        ));
    }

    private function dropMappingTable(): void
    {
        $this->connection->executeStatement('DROP TABLE IF EXISTS '.self::MAP_TABLE);
    }

    /**
     * @param array<int, array<string, mixed>> $nodeRows
     *
     * @return array<string, int>
     */
    private function fetchNodeIdsByUuid(array $nodeRows, bool $uuidIsBinary): array
    {
        $placeholders = [];
        $parameters = [];
        $types = [];

        foreach ($nodeRows as $index => $row) {
            $name = 'uuid_'.$index;
            $placeholders[] = ':'.$name;
            $parameters[$name] = $row['uuid'];
            if ($uuidIsBinary) {
                $types[$name] = ParameterType::BINARY;
            }
        }

        $rows = $this->connection->executeQuery(
            'SELECT id, uuid FROM resource_node WHERE uuid IN ('.implode(', ', $placeholders).')',
            $parameters,
            $types
        )->fetchAllAssociative();

        $result = [];
        foreach ($rows as $row) {
            $key = $uuidIsBinary ? bin2hex((string) $row['uuid']) : (string) $row['uuid'];
            $result[$key] = (int) $row['id'];
        }

        return $result;
    }

    /**
     * @param array<int, string>               $columns
     * @param array<int, array<string, mixed>> $rows
     * @param array<int, string>               $binaryColumns
     */
    private function bulkInsert(
        string $table,
        array $columns,
        array $rows,
        array $binaryColumns = []
    ): void {
        if ([] === $rows) {
            return;
        }

        $valueGroups = [];
        $parameters = [];
        $types = [];

        foreach ($rows as $rowIndex => $row) {
            $placeholders = [];
            foreach ($columns as $column) {
                $parameterName = 'p_'.$rowIndex.'_'.preg_replace('/[^a-zA-Z0-9_]/', '_', $column);
                $placeholders[] = ':'.$parameterName;
                $parameters[$parameterName] = $row[$column] ?? null;
                if (in_array($column, $binaryColumns, true)) {
                    $types[$parameterName] = ParameterType::BINARY;
                }
            }
            $valueGroups[] = '('.implode(', ', $placeholders).')';
        }

        $this->connection->executeStatement(
            sprintf(
                'INSERT INTO %s (%s) VALUES %s',
                $table,
                implode(', ', $columns),
                implode(', ', $valueGroups)
            ),
            $parameters,
            $types
        );
    }

    /**
     * @param array<int, int|string|null> $mappings
     */
    private function bulkUpdateById(
        string $table,
        string $idColumn,
        string $valueColumn,
        array $mappings
    ): void {
        if ([] === $mappings) {
            return;
        }

        $cases = [];
        $where = [];
        $parameters = [];
        $index = 0;

        foreach ($mappings as $id => $value) {
            $idParameter = 'case_id_'.$index;
            $valueParameter = 'case_value_'.$index;
            $whereParameter = 'where_id_'.$index;
            $cases[] = "WHEN :{$idParameter} THEN :{$valueParameter}";
            $where[] = ':'.$whereParameter;
            $parameters[$idParameter] = (int) $id;
            $parameters[$valueParameter] = $value;
            $parameters[$whereParameter] = (int) $id;
            ++$index;
        }

        $this->connection->executeStatement(
            sprintf(
                'UPDATE %s SET %s = CASE %s %s ELSE %s END WHERE %s IN (%s)',
                $table,
                $valueColumn,
                $idColumn,
                implode(' ', $cases),
                $valueColumn,
                $idColumn,
                implode(', ', $where)
            ),
            $parameters
        );
    }

    private function normalizeResourceTitle(string $title, int $sourceId): string
    {
        $title = html_entity_decode(strip_tags($title), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        if (!mb_check_encoding($title, 'UTF-8')) {
            $title = (string) iconv('UTF-8', 'UTF-8//IGNORE', $title);
        }
        $title = preg_replace('/\s+/u', ' ', trim($title));
        if (null === $title || '' === $title) {
            $title = 'Calendar event #'.$sourceId;
        }
        $title = str_replace(['/', '\\'], '-', $title);
        if (mb_strlen($title) > self::RESOURCE_NODE_TITLE_MAX_LENGTH) {
            $title = mb_substr($title, 0, self::RESOURCE_NODE_TITLE_MAX_LENGTH - 3).'...';
        }

        return $title;
    }

    private function normalizeDate(mixed $value): ?string
    {
        $value = trim((string) $value);

        return '' === $value || '0000-00-00 00:00:00' === $value ? null : $value;
    }

    private function buildResourcePath(
        string $parentPath,
        string $title,
        int $sourceId,
        int $resourceNodeId
    ): string {
        $parentPath = rtrim($parentPath, '/');
        if ('' === $parentPath) {
            throw new RuntimeException(sprintf(
                'Parent resource path is empty for sys_calendar %d.',
                $sourceId
            ));
        }

        return $parentPath.'/'.$title.'-'.$sourceId.'-'.$resourceNodeId.'/';
    }

    private function detectUuidIsBinary(): bool
    {
        try {
            $table = $this->connection->createSchemaManager()->introspectTable('resource_node');
            if (!$table->hasColumn('uuid')) {
                return false;
            }

            $column = $table->getColumn('uuid');
            $type = $column->getType()->getName();
            $length = $column->getLength();

            return in_array($type, ['binary', 'varbinary'], true) || 16 === $length;
        } catch (Throwable) {
            return false;
        }
    }

    public function down(Schema $schema): void
    {
        // Data migration cannot be safely reverted automatically.
    }
}
