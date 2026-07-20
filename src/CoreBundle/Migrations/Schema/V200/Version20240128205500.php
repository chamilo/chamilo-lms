<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Schema\Schema;
use RuntimeException;
use Symfony\Component\Uid\Uuid;
use Throwable;

final class Version20240128205500 extends AbstractMigrationChamilo
{
    private const METADATA_BATCH_SIZE = 1000;
    private const RESOURCE_NODE_TITLE_MAX_LENGTH = 255;

    public function getDescription(): string
    {
        return 'Prepare certificate resource metadata with transactional DBAL batches; physical files are migrated separately';
    }

    public function isTransactional(): bool
    {
        // Every batch commits atomically and only certificates with a null
        // resource_node_id are selected, so interrupted runs are resumable.
        return false;
    }

    public function up(Schema $schema): void
    {
        $resourceTypeId = $this->resolveUserFilesResourceTypeId();
        $uuidIsBinary = $this->detectUuidIsBinary();

        $total = (int) $this->connection->fetchOne(
            <<<'SQL'
SELECT COUNT(*)
FROM gradebook_certificate gc
INNER JOIN user u ON u.id = gc.user_id
INNER JOIN resource_node parent ON parent.id = u.resource_node_id
WHERE gc.resource_node_id IS NULL
  AND gc.path_certificate IS NOT NULL
  AND TRIM(gc.path_certificate) <> ''
  AND parent.path IS NOT NULL
  AND TRIM(parent.path) <> ''
SQL
        );

        if (0 === $total) {
            $this->write('No pending certificate resource metadata.');

            return;
        }

        $lastId = 0;
        $seen = 0;
        $prepared = 0;
        $startedAt = microtime(true);

        $this->write(sprintf(
            'Certificate DBAL metadata phase started: pending=%d batch_size=%d uuid_binary=%s.',
            $total,
            self::METADATA_BATCH_SIZE,
            $uuidIsBinary ? 'yes' : 'no'
        ));

        while (true) {
            $rows = $this->connection->fetchAllAssociative(
                sprintf(<<<'SQL'
SELECT
    gc.id,
    gc.user_id,
    gc.path_certificate,
    parent.id AS parent_node_id,
    parent.path AS parent_path,
    parent.level AS parent_level
FROM gradebook_certificate gc
INNER JOIN user u ON u.id = gc.user_id
INNER JOIN resource_node parent ON parent.id = u.resource_node_id
WHERE gc.resource_node_id IS NULL
  AND gc.path_certificate IS NOT NULL
  AND TRIM(gc.path_certificate) <> ''
  AND parent.path IS NOT NULL
  AND TRIM(parent.path) <> ''
  AND gc.id > :lastId
ORDER BY gc.id ASC
LIMIT %d
SQL, self::METADATA_BATCH_SIZE),
                ['lastId' => $lastId]
            );

            if ([] === $rows) {
                break;
            }

            $lastId = (int) $rows[array_key_last($rows)]['id'];
            $seen += count($rows);

            $prepared += $this->persistMetadataBatch(
                $rows,
                $resourceTypeId,
                $uuidIsBinary
            );

            $elapsed = max(1, (int) (microtime(true) - $startedAt));
            $rate = $seen / $elapsed;
            $remaining = max(0, $total - $seen);

            $this->write(sprintf(
                'Certificate DBAL metadata progress: %d/%d (%.2f%%), prepared=%d rate=%.2f cert/s ETA=%ds last_id=%d.',
                $seen,
                $total,
                100 * $seen / $total,
                $prepared,
                $rate,
                $rate > 0 ? (int) round($remaining / $rate) : 0,
                $lastId
            ));
        }

        $remainingEligible = (int) $this->connection->fetchOne(
            <<<'SQL'
SELECT COUNT(*)
FROM gradebook_certificate gc
INNER JOIN user u ON u.id = gc.user_id
INNER JOIN resource_node parent ON parent.id = u.resource_node_id
WHERE gc.resource_node_id IS NULL
  AND gc.path_certificate IS NOT NULL
  AND TRIM(gc.path_certificate) <> ''
  AND parent.path IS NOT NULL
  AND TRIM(parent.path) <> ''
SQL
        );

        $missingUserNode = (int) $this->connection->fetchOne(
            <<<'SQL'
SELECT COUNT(*)
FROM gradebook_certificate gc
LEFT JOIN user u ON u.id = gc.user_id
LEFT JOIN resource_node parent ON parent.id = u.resource_node_id
WHERE gc.resource_node_id IS NULL
  AND gc.path_certificate IS NOT NULL
  AND TRIM(gc.path_certificate) <> ''
  AND (u.id IS NULL OR parent.id IS NULL OR parent.path IS NULL OR TRIM(parent.path) = '')
SQL
        );

        $this->write(sprintf(
            'Certificate DBAL metadata phase completed: seen=%d/%d prepared=%d remaining_eligible=%d missing_user_node=%d elapsed=%ds. Physical files must be processed with chamilo:migration:migrate-ricky-certificate-files.',
            $seen,
            $total,
            $prepared,
            $remainingEligible,
            $missingUserNode,
            (int) (microtime(true) - $startedAt)
        ));
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     */
    private function persistMetadataBatch(array $rows, int $resourceTypeId, bool $uuidIsBinary): int
    {
        $this->connection->beginTransaction();

        try {
            $now = gmdate('Y-m-d H:i:s');
            $nodeRows = [];
            $uuidKeysByCertificateId = [];
            $metadataByCertificateId = [];

            foreach ($rows as $row) {
                $certificateId = (int) $row['id'];
                $title = $this->normalizeLogicalFileName(
                    (string) $row['path_certificate'],
                    $certificateId
                );
                $uuid = Uuid::v4();
                $uuidValue = $uuidIsBinary ? $uuid->toBinary() : $uuid->toRfc4122();
                $uuidKey = $uuidIsBinary ? bin2hex($uuidValue) : $uuidValue;

                $uuidKeysByCertificateId[$certificateId] = $uuidKey;
                $metadataByCertificateId[$certificateId] = [
                    'title' => $title,
                    'user_id' => (int) $row['user_id'],
                    'parent_node_id' => (int) $row['parent_node_id'],
                    'parent_path' => (string) $row['parent_path'],
                    'parent_level' => (int) ($row['parent_level'] ?? 0),
                ];

                $nodeRows[] = [
                    'title' => $title,
                    'slug' => 'certificate-'.$certificateId,
                    'level' => ((int) ($row['parent_level'] ?? 0)) + 1,
                    'path' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                    'public' => 0,
                    'uuid' => $uuidValue,
                    'resource_type_id' => $resourceTypeId,
                    'resource_format_id' => null,
                    'language_id' => null,
                    'creator_id' => (int) $row['user_id'],
                    'parent_id' => (int) $row['parent_node_id'],
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
                    'Expected %d inserted certificate resource nodes, found %d.',
                    count($rows),
                    count($nodeIdsByUuid)
                ));
            }

            $certificateMappings = [];
            $pathMappings = [];
            $resourceLinkRows = [];

            foreach ($metadataByCertificateId as $certificateId => $metadata) {
                $uuidKey = $uuidKeysByCertificateId[$certificateId];
                $resourceNodeId = $nodeIdsByUuid[$uuidKey] ?? 0;
                if ($resourceNodeId <= 0) {
                    throw new RuntimeException(sprintf(
                        'Resource node was not resolved for certificate %d.',
                        $certificateId
                    ));
                }

                $certificateMappings[$certificateId] = $resourceNodeId;
                $pathMappings[$resourceNodeId] = $this->buildResourcePath(
                    $metadata['parent_path'],
                    $metadata['title'],
                    $certificateId,
                    $resourceNodeId
                );

                $resourceLinkRows[] = [
                    'resource_node_id' => $resourceNodeId,
                    'c_id' => null,
                    'session_id' => null,
                    'usergroup_id' => null,
                    'group_id' => null,
                    'user_id' => $metadata['user_id'],
                    'visibility' => ResourceLink::VISIBILITY_PUBLISHED,
                    'start_visibility_at' => null,
                    'end_visibility_at' => null,
                    'display_order' => $certificateId,
                    'resource_type_group' => $resourceTypeId,
                    'created_at' => $now,
                    'updated_at' => $now,
                    'deleted_at' => null,
                    'parent_id' => null,
                ];
            }

            $this->bulkUpdateById(
                'gradebook_certificate',
                'id',
                'resource_node_id',
                $certificateMappings
            );
            $this->bulkUpdateById(
                'resource_node',
                'id',
                'path',
                $pathMappings
            );
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

            $this->connection->commit();

            return count($rows);
        } catch (Throwable $exception) {
            if ($this->connection->isTransactionActive()) {
                $this->connection->rollBack();
            }

            throw new RuntimeException(
                'Certificate DBAL metadata batch failed: '.$exception->getMessage(),
                0,
                $exception
            );
        }
    }

    private function resolveUserFilesResourceTypeId(): int
    {
        // Existing certificate resources were created through
        // GradebookCertificateRepository and are the safest source of truth
        // for this upgraded Ricky database.
        $id = $this->connection->fetchOne(
            <<<'SQL'
SELECT rn.resource_type_id
FROM gradebook_certificate gc
INNER JOIN resource_node rn
    ON rn.id = gc.resource_node_id
WHERE gc.resource_node_id IS NOT NULL
  AND rn.resource_type_id IS NOT NULL
GROUP BY rn.resource_type_id
ORDER BY COUNT(*) DESC, rn.resource_type_id
LIMIT 1
SQL
        );

        if (false !== $id && (int) $id > 0) {
            return (int) $id;
        }

        // Match GradebookCertificateRepository::getPersonalFilesResourceType():
        // when no certificate resource exists yet, fall back to the first
        // ResourceType whose title is "files", without assuming a Tool title.
        $id = $this->connection->fetchOne(
            <<<'SQL'
SELECT id
FROM resource_type
WHERE title = :resourceTypeTitle
ORDER BY id
LIMIT 1
SQL,
            ['resourceTypeTitle' => 'files']
        );

        if (false === $id || (int) $id <= 0) {
            throw new RuntimeException(
                "ResourceType 'files' was not found and no existing certificate resource could provide it."
            );
        }

        return (int) $id;
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

    private function normalizeLogicalFileName(string $path, int $certificateId): string
    {
        $fileName = basename(ltrim(trim($path), '/'));
        if ('' === $fileName || '.' === $fileName) {
            $fileName = 'certificate-'.$certificateId.'.html';
        }

        $fileName = str_replace(['/', '\\'], '-', $fileName);
        if (mb_strlen($fileName) > self::RESOURCE_NODE_TITLE_MAX_LENGTH) {
            $extension = pathinfo($fileName, PATHINFO_EXTENSION);
            $suffix = '' !== $extension ? '.'.$extension : '';
            $fileName = mb_substr(
                $fileName,
                0,
                self::RESOURCE_NODE_TITLE_MAX_LENGTH - mb_strlen($suffix)
            ).$suffix;
        }

        return $fileName;
    }

    private function buildResourcePath(
        string $parentPath,
        string $title,
        int $certificateId,
        int $resourceNodeId
    ): string {
        $parentPath = rtrim($parentPath, '/');
        if ('' === $parentPath) {
            throw new RuntimeException(sprintf(
                'Parent resource path is empty for certificate %d.',
                $certificateId
            ));
        }

        $segment = preg_replace('/\s+/u', ' ', trim($title));
        if (null === $segment || '' === $segment) {
            $segment = 'certificate-'.$certificateId;
        }
        $segment = str_replace(['/', '\\'], '-', $segment);

        return $parentPath.'/'.$segment.'-'.$certificateId.'-'.$resourceNodeId.'/';
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
}
