<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Schema\Schema;
use Throwable;

final class Version20240515124400 extends AbstractMigrationChamilo
{
    public const BATCH_SIZE = 1000;

    public function getDescription(): string
    {
        return 'Link track_e_downloads to resource links with resumable DBAL batches and deterministic file-name mapping.';
    }

    public function isTransactional(): bool
    {
        return false;
    }

    public function up(Schema $schema): void
    {
        $totalPending = (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM track_e_downloads WHERE resource_link_id IS NULL'
        );

        if (0 === $totalPending) {
            $this->getLogger()->info('No download tracking rows require resource links.');

            return;
        }

        $lastId = 0;
        $seen = 0;
        $linked = 0;
        $unmatched = 0;
        $startedAt = microtime(true);

        $this->getLogger()->info('Download resource-link DBAL migration started.', [
            'pending' => $totalPending,
            'batch_size' => self::BATCH_SIZE,
        ]);

        while (true) {
            $rows = $this->connection->fetchAllAssociative(
                'SELECT down_id, down_doc_path
                 FROM track_e_downloads
                 WHERE resource_link_id IS NULL
                   AND down_id > :lastId
                 ORDER BY down_id
                 LIMIT '.self::BATCH_SIZE,
                ['lastId' => $lastId]
            );

            if ([] === $rows) {
                break;
            }

            $lastId = (int) $rows[array_key_last($rows)]['down_id'];
            $fileNames = [];
            foreach ($rows as $row) {
                $fileName = basename(str_replace('\\', '/', (string) ($row['down_doc_path'] ?? '')));
                if ('' !== $fileName) {
                    $fileNames[$fileName] = true;
                }
            }

            $linkByFileName = [];
            if ([] !== $fileNames) {
                $matches = $this->connection->fetchAllAssociative(
                    'SELECT rf.original_name, rf.id AS resource_file_id, MIN(rl.id) AS resource_link_id
                     FROM resource_file rf
                     INNER JOIN (
                         SELECT original_name, MIN(id) AS first_resource_file_id
                         FROM resource_file
                         WHERE original_name IN (:fileNames)
                         GROUP BY original_name
                     ) first_file ON first_file.first_resource_file_id = rf.id
                     LEFT JOIN resource_link rl ON rl.resource_node_id = rf.resource_node_id
                     GROUP BY rf.original_name, rf.id
                     ORDER BY rf.id',
                    ['fileNames' => array_keys($fileNames)],
                    ['fileNames' => ArrayParameterType::STRING]
                );

                foreach ($matches as $match) {
                    $name = (string) $match['original_name'];
                    if (!isset($linkByFileName[$name])) {
                        $linkByFileName[$name] = (int) $match['resource_link_id'];
                    }
                }
            }

            $updates = [];
            foreach ($rows as $row) {
                ++$seen;
                $downloadId = (int) $row['down_id'];
                $fileName = basename(str_replace('\\', '/', (string) ($row['down_doc_path'] ?? '')));
                $resourceLinkId = $linkByFileName[$fileName] ?? null;

                if (null === $resourceLinkId) {
                    ++$unmatched;
                    continue;
                }

                $updates[$downloadId] = $resourceLinkId;
                ++$linked;
            }

            $this->connection->beginTransaction();
            try {
                $this->bulkUpdateResourceLinks($updates);
                $this->connection->commit();
            } catch (Throwable $e) {
                if ($this->connection->isTransactionActive()) {
                    $this->connection->rollBack();
                }
                throw $e;
            }

            $elapsed = max(1, (int) (microtime(true) - $startedAt));
            $rate = $seen / $elapsed;
            $remaining = max(0, $totalPending - $seen);

            $this->getLogger()->info('Download resource-link DBAL migration progress.', [
                'seen' => $seen,
                'total_pending' => $totalPending,
                'linked' => $linked,
                'unmatched' => $unmatched,
                'percentage' => round(100 * $seen / $totalPending, 2),
                'rows_per_second' => round($rate, 2),
                'eta_seconds' => $rate > 0 ? (int) round($remaining / $rate) : null,
                'last_down_id' => $lastId,
            ]);
        }

        $deleted = $this->connection->executeStatement(
            'DELETE FROM track_e_downloads WHERE resource_link_id IS NULL'
        );

        $this->getLogger()->info('Download resource-link DBAL migration completed.', [
            'initial_pending' => $totalPending,
            'seen' => $seen,
            'linked' => $linked,
            'deleted_unmatched' => $deleted,
            'remaining' => (int) $this->connection->fetchOne(
                'SELECT COUNT(*) FROM track_e_downloads WHERE resource_link_id IS NULL'
            ),
            'elapsed_seconds' => (int) (microtime(true) - $startedAt),
        ]);
    }

    /**
     * @param array<int, int> $updates
     */
    private function bulkUpdateResourceLinks(array $updates): void
    {
        if ([] === $updates) {
            return;
        }

        $cases = [];
        $ids = [];
        $parameters = [];
        $index = 0;

        foreach ($updates as $downloadId => $resourceLinkId) {
            $idName = 'id_'.$index;
            $linkName = 'link_'.$index;
            $whereName = 'where_'.$index;
            $cases[] = "WHEN :{$idName} THEN :{$linkName}";
            $ids[] = ':'.$whereName;
            $parameters[$idName] = $downloadId;
            $parameters[$linkName] = $resourceLinkId;
            $parameters[$whereName] = $downloadId;
            ++$index;
        }

        $this->connection->executeStatement(
            'UPDATE track_e_downloads
             SET resource_link_id = CASE down_id '.implode(' ', $cases).' ELSE resource_link_id END
             WHERE down_id IN ('.implode(', ', $ids).')
               AND resource_link_id IS NULL',
            $parameters
        );
    }

    public function down(Schema $schema): void {}
}
