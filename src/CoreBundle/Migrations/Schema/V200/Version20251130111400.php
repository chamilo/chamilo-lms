<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Entity\Asset;
use Chamilo\CoreBundle\Entity\ResourceFile;
use Chamilo\CoreBundle\Entity\ResourceNode;
use Chamilo\CoreBundle\Entity\ResourceType;
use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Chamilo\CoreBundle\Repository\AssetRepository;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Throwable;

final class Version20251130111400 extends AbstractMigrationChamilo
{
    private const DRY_RUN = false;

    public function getDescription(): string
    {
        return 'Migrate attempt_file / attempt_feedback assets to ResourceNode/ResourceFile and fill resource_node_id, then cleanup asset references and orphan nodes.';
    }

    /**
     * This migration runs outside a global DB transaction.
     * We want to be able to skip broken rows without rolling back everything.
     */
    public function isTransactional(): bool
    {
        return false;
    }

    public function up(Schema $schema): void
    {
        $this->ensureEntityManagerIsOpen();

        $resourceTypeRepo = $this->entityManager->getRepository(ResourceType::class);

        /** @var ResourceType|null $attemptFileType */
        $attemptFileType = $resourceTypeRepo->findOneBy(['title' => 'attempt_file']);
        /** @var ResourceType|null $attemptFeedbackType */
        $attemptFeedbackType = $resourceTypeRepo->findOneBy(['title' => 'attempt_feedback']);

        if (null === $attemptFileType || null === $attemptFeedbackType) {
            error_log('[MIGRATION] ResourceType "attempt_file" or "attempt_feedback" not found. Aborting data migration.');

            return;
        }

        /** @var AssetRepository $assetRepo */
        $assetRepo = $this->getAssetRepository();

        // 1) Main migrations (create ResourceNode/ResourceFile, clear asset_id, delete unused Asset).
        $this->migrateAttemptFiles($assetRepo, $attemptFileType);
        $this->migrateAttemptFeedback($assetRepo, $attemptFeedbackType);

        // 2) Cleanup: ensure there are no resource_node_id values without a ResourceFile.
        $this->cleanupOrphanNodesWithoutFile();
    }

    public function down(Schema $schema): void
    {
        // No-op: data migration is not reversible in a safe way.
    }

    /**
     * Migrate and/or cleanup attempt_file rows:
     * - If resource_node_id IS NULL: create ResourceNode/ResourceFile and then clear asset_id.
     * - If resource_node_id IS NOT NULL: only clear asset_id (row was already migrated).
     */
    private function migrateAttemptFiles(
        AssetRepository $assetRepo,
        ResourceType $attemptFileType
    ): void {
        $sql = 'SELECT id, attempt_id, asset_id, resource_node_id
                FROM attempt_file
                WHERE asset_id IS NOT NULL';

        $stmt = $this->connection->executeQuery($sql);

        $processed = 0;
        $migrated = 0;
        $cleanedOnly = 0;

        error_log('[MIGRATION][attempt_file] Starting migration for attempt_file rows.');

        while (false !== ($row = $stmt->fetchAssociative())) {
            $processed++;

            $rowId = $row['id'];             // binary(16) UUID or similar
            $attemptId = $row['attempt_id']; // int or other scalar
            $assetId = $row['asset_id'];     // binary(16) UUID
            $resourceNodeId = $row['resource_node_id']; // int|null

            $rowIdForLog = $this->formatIdForLog($rowId);
            $attemptIdForLog = $this->formatIdForLog($attemptId);
            $assetIdForLog = $this->formatIdForLog($assetId);

            if (0 === $processed % 200) {
                error_log(
                    sprintf(
                        '[MIGRATION][attempt_file] Progress: processed=%d, migrated=%d, cleanedOnly=%d (last rowId=%s)',
                        $processed,
                        $migrated,
                        $cleanedOnly,
                        $rowIdForLog
                    )
                );
            }

            try {
                $this->ensureEntityManagerIsOpen();

                /** @var Asset|null $asset */
                $asset = $assetRepo->find($assetId);

                if (null === $asset) {
                    // Asset record is gone, just clear the asset_id reference.
                    $this->connection->update(
                        'attempt_file',
                        ['asset_id' => null],
                        ['id' => $rowId]
                    );

                    error_log(
                        sprintf(
                            '[MIGRATION][attempt_file] Cleared asset_id for attempt_file.id=%s (attemptId=%s assetId=%s) because Asset is missing.',
                            $rowIdForLog,
                            $attemptIdForLog,
                            $assetIdForLog
                        )
                    );

                    continue;
                }

                if (null === $resourceNodeId) {
                    // Not migrated yet: create ResourceNode/ResourceFile then cleanup the Asset reference.
                    $ok = $this->migrateSingleAssetToResourceNode(
                        $assetRepo,
                        'attempt_file',
                        'attempt_file',
                        $rowId,
                        $asset,
                        $attemptFileType
                    );

                    if ($ok) {
                        $migrated++;
                    }
                } else {
                    // Already has a resource_node_id: only cleanup asset_id and possibly delete the Asset if unused.
                    $this->clearAssetReference(
                        $assetRepo,
                        'attempt_file',
                        'attempt_file',
                        $rowId,
                        $rowIdForLog,
                        $asset
                    );
                    $cleanedOnly++;
                }
            } catch (Throwable $e) {
                error_log(
                    sprintf(
                        '[MIGRATION][attempt_file] Error for attempt_file.id=%s (attemptId=%s): %s',
                        $rowIdForLog,
                        $attemptIdForLog,
                        $e->getMessage()
                    )
                );

                $this->ensureEntityManagerIsOpen();
            }

            if (0 === $processed % 50) {
                // Free some memory for long-running migrations.
                $this->entityManager->clear(ResourceNode::class);
                $this->entityManager->clear(ResourceFile::class);
                $this->entityManager->clear(Asset::class);
            }
        }

        error_log(
            sprintf(
                '[MIGRATION][attempt_file] Finished. Processed=%d, Migrated=%d, CleanedOnly=%d',
                $processed,
                $migrated,
                $cleanedOnly
            )
        );
    }

    /**
     * Same logic as attempt_file but for attempt_feedback.
     */
    private function migrateAttemptFeedback(
        AssetRepository $assetRepo,
        ResourceType $attemptFeedbackType
    ): void {
        $sql = 'SELECT id, attempt_id, asset_id, resource_node_id
                FROM attempt_feedback
                WHERE asset_id IS NOT NULL';

        $stmt = $this->connection->executeQuery($sql);

        $processed = 0;
        $migrated = 0;
        $cleanedOnly = 0;

        error_log('[MIGRATION][attempt_feedback] Starting migration for attempt_feedback rows.');

        while (false !== ($row = $stmt->fetchAssociative())) {
            $processed++;

            $rowId = $row['id'];             // binary(16) UUID or similar
            $attemptId = $row['attempt_id']; // int or other scalar
            $assetId = $row['asset_id'];     // binary(16) UUID
            $resourceNodeId = $row['resource_node_id']; // int|null

            $rowIdForLog = $this->formatIdForLog($rowId);
            $attemptIdForLog = $this->formatIdForLog($attemptId);
            $assetIdForLog = $this->formatIdForLog($assetId);

            if (0 === $processed % 200) {
                error_log(
                    sprintf(
                        '[MIGRATION][attempt_feedback] Progress: processed=%d, migrated=%d, cleanedOnly=%d (last rowId=%s)',
                        $processed,
                        $migrated,
                        $cleanedOnly,
                        $rowIdForLog
                    )
                );
            }

            try {
                $this->ensureEntityManagerIsOpen();

                /** @var Asset|null $asset */
                $asset = $assetRepo->find($assetId);

                if (null === $asset) {
                    // Asset record is gone, just clear the asset_id reference.
                    $this->connection->update(
                        'attempt_feedback',
                        ['asset_id' => null],
                        ['id' => $rowId]
                    );

                    error_log(
                        sprintf(
                            '[MIGRATION][attempt_feedback] Cleared asset_id for attempt_feedback.id=%s (attemptId=%s assetId=%s) because Asset is missing.',
                            $rowIdForLog,
                            $attemptIdForLog,
                            $assetIdForLog
                        )
                    );

                    continue;
                }

                if (null === $resourceNodeId) {
                    $ok = $this->migrateSingleAssetToResourceNode(
                        $assetRepo,
                        'attempt_feedback',
                        'attempt_feedback',
                        $rowId,
                        $asset,
                        $attemptFeedbackType
                    );

                    if ($ok) {
                        $migrated++;
                    }
                } else {
                    $this->clearAssetReference(
                        $assetRepo,
                        'attempt_feedback',
                        'attempt_feedback',
                        $rowId,
                        $rowIdForLog,
                        $asset
                    );
                    $cleanedOnly++;
                }
            } catch (Throwable $e) {
                error_log(
                    sprintf(
                        '[MIGRATION][attempt_feedback] Error for attempt_feedback.id=%s (attemptId=%s): %s',
                        $rowIdForLog,
                        $attemptIdForLog,
                        $e->getMessage()
                    )
                );

                $this->ensureEntityManagerIsOpen();
            }

            if (0 === $processed % 50) {
                $this->entityManager->clear(ResourceNode::class);
                $this->entityManager->clear(ResourceFile::class);
                $this->entityManager->clear(Asset::class);
            }
        }

        error_log(
            sprintf(
                '[MIGRATION][attempt_feedback] Finished. Processed=%d, Migrated=%d, CleanedOnly=%d',
                $processed,
                $migrated,
                $cleanedOnly
            )
        );
    }

    /**
     * Shared logic for attempt_file and attempt_feedback:
     *  - Create ResourceNode with the given ResourceType.
     *  - Create ResourceFile with setFile() (Vich) using an UploadedFile from the Asset.
     *  - Update resource_node_id AND clear asset_id in the corresponding table.
     *  - If the Asset has no more references, delete it (DB + filesystem).
     *
     * @return bool true if the migration succeeded, false if it was skipped.
     */
    private function migrateSingleAssetToResourceNode(
        AssetRepository $assetRepo,
        string $context,
        string $tableName,
        string $rowId,
        Asset $asset,
        ResourceType $resourceType
    ): bool {
        $rowIdForLog = $this->formatIdForLog($rowId);

        if (self::DRY_RUN) {
            error_log(
                sprintf(
                    '[MIGRATION][DRY_RUN][%s] Would create ResourceNode/ResourceFile for table=%s rowId=%s (Asset id=%s)',
                    $context,
                    $tableName,
                    $rowIdForLog,
                    (string) $asset->getId()
                )
            );

            return true;
        }

        // 1) Build an UploadedFile from the existing Asset file using streams.
        $uploadedFile = $this->createUploadedFileFromAsset($assetRepo, $asset, $context);

        if (null === $uploadedFile) {
            error_log(
                sprintf(
                    '[MIGRATION][%s] Skipping rowId=%s because UploadedFile could not be created (Asset id=%s).',
                    $context,
                    $rowIdForLog,
                    (string) $asset->getId()
                )
            );

            return false;
        }

        try {
            // 2) Create ResourceNode
            $this->ensureEntityManagerIsOpen();

            $node = new ResourceNode();
            $node->setTitle($asset->getTitle() ?: 'attempt_' . $context);
            $node->setResourceType($resourceType);
            $this->entityManager->persist($node);

            // 3) Create ResourceFile with Vich file
            $resourceFile = new ResourceFile();
            $resourceFile->setResourceNode($node);
            $resourceFile->setFile($uploadedFile);
            $this->entityManager->persist($resourceFile);

            $this->entityManager->flush();

            // 4) Update the link table: set resource_node_id AND clear asset_id
            $this->connection->update(
                $tableName,
                [
                    'resource_node_id' => $node->getId(),
                    'asset_id' => null,
                ],
                ['id' => $rowId]
            );

            // 5) Clean up the temporary file if it still exists
            $realPath = $uploadedFile->getRealPath();
            if ($realPath && is_file($realPath)) {
                @unlink($realPath);
            }

            // 6) Delete Asset if there are no more references in attempt_file/attempt_feedback
            $this->cleanupAssetIfUnused($assetRepo, $asset);

            error_log(
                sprintf(
                    '[MIGRATION][%s] Migrated rowId=%s to ResourceNode id=%d and cleared asset reference (Asset id=%s)',
                    $context,
                    $rowIdForLog,
                    (int) $node->getId(),
                    (string) $asset->getId()
                )
            );

            return true;
        } catch (Throwable $e) {
            error_log(
                sprintf(
                    '[MIGRATION][%s] Failed to migrate rowId=%s (Asset id=%s): %s',
                    $context,
                    $rowIdForLog,
                    (string) $asset->getId(),
                    $e->getMessage()
                )
            );

            $this->ensureEntityManagerIsOpen();

            $realPath = $uploadedFile->getRealPath();
            if ($realPath && is_file($realPath)) {
                @unlink($realPath);
            }

            return false;
        }
    }

    /**
     * Clear asset_id for a single row and delete the Asset if there are no remaining references.
     */
    private function clearAssetReference(
        AssetRepository $assetRepo,
        string $context,
        string $tableName,
        string $rowId,
        string $rowIdForLog,
        Asset $asset
    ): void {
        // Clear asset_id for this row
        $this->connection->update(
            $tableName,
            ['asset_id' => null],
            ['id' => $rowId]
        );

        // Try to delete the Asset if it has no more references
        $this->cleanupAssetIfUnused($assetRepo, $asset);

        error_log(
            sprintf(
                '[MIGRATION][%s] Cleared asset_id for %s.id=%s (Asset id=%s, maybe deleted if unused).',
                $context,
                $tableName,
                $rowIdForLog,
                (string) $asset->getId()
            )
        );
    }

    /**
     * Delete the Asset (DB + filesystem) if it is no longer referenced
     * by attempt_file or attempt_feedback.
     */
    private function cleanupAssetIfUnused(AssetRepository $assetRepo, Asset $asset): void
    {
        $assetId = $asset->getId();

        $refs = (int) $this->connection->fetchOne(
            'SELECT
                 (SELECT COUNT(*) FROM attempt_file WHERE asset_id = :asset_id) +
                 (SELECT COUNT(*) FROM attempt_feedback WHERE asset_id = :asset_id) AS total_refs',
            ['asset_id' => $assetId]
        );

        if ($refs > 0) {
            // Still referenced somewhere, do not delete.
            return;
        }

        // No more references: delete Asset from DB and filesystem
        $assetRepo->delete($asset);

        error_log(
            sprintf(
                '[MIGRATION][asset] Deleted Asset id=%s because it has no remaining references.',
                $this->formatIdForLog($assetId)
            )
        );
    }

    /**
     * Cleanup step after migration:
     * Ensure that there are no resource_node_id values without a ResourceFile
     * in attempt_file / attempt_feedback, and delete orphan ResourceNode entries.
     */
    private function cleanupOrphanNodesWithoutFile(): void
    {
        error_log('[MIGRATION][cleanup] Starting orphan ResourceNode cleanup for attempts.');

        // 1) attempt_file: nodes without ResourceFile
        $nodeIds = $this->connection->fetchFirstColumn(
            'SELECT DISTINCT af.resource_node_id
             FROM attempt_file af
             LEFT JOIN resource_file rf ON rf.resource_node_id = af.resource_node_id
             WHERE af.resource_node_id IS NOT NULL
               AND rf.id IS NULL'
        );

        if (!empty($nodeIds)) {
            // Remove resource_node_id from attempt_file
            $this->connection->executeStatement(
                'UPDATE attempt_file
                 SET resource_node_id = NULL
                 WHERE resource_node_id IN (?)',
                [$nodeIds],
                [\Doctrine\DBAL\Connection::PARAM_INT_ARRAY]
            );

            // Delete orphan ResourceNode entries that do not have any ResourceFile
            $this->connection->executeStatement(
                'DELETE rn
                 FROM resource_node rn
                 LEFT JOIN resource_file rf ON rf.resource_node_id = rn.id
                 WHERE rn.id IN (?)
                   AND rf.id IS NULL',
                [$nodeIds],
                [\Doctrine\DBAL\Connection::PARAM_INT_ARRAY]
            );

            error_log(
                sprintf(
                    '[MIGRATION][cleanup] Cleaned orphan attempt_file nodes without ResourceFile. Count=%d',
                    \count($nodeIds)
                )
            );
        } else {
            error_log('[MIGRATION][cleanup] No orphan attempt_file nodes without ResourceFile found.');
        }

        // 2) attempt_feedback: nodes without ResourceFile
        $fbNodeIds = $this->connection->fetchFirstColumn(
            'SELECT DISTINCT af.resource_node_id
             FROM attempt_feedback af
             LEFT JOIN resource_file rf ON rf.resource_node_id = af.resource_node_id
             WHERE af.resource_node_id IS NOT NULL
               AND rf.id IS NULL'
        );

        if (!empty($fbNodeIds)) {
            $this->connection->executeStatement(
                'UPDATE attempt_feedback
                 SET resource_node_id = NULL
                 WHERE resource_node_id IN (?)',
                [$fbNodeIds],
                [\Doctrine\DBAL\Connection::PARAM_INT_ARRAY]
            );

            $this->connection->executeStatement(
                'DELETE rn
                 FROM resource_node rn
                 LEFT JOIN resource_file rf ON rf.resource_node_id = rn.id
                 WHERE rn.id IN (?)
                   AND rf.id IS NULL',
                [$fbNodeIds],
                [\Doctrine\DBAL\Connection::PARAM_INT_ARRAY]
            );

            error_log(
                sprintf(
                    '[MIGRATION][cleanup] Cleaned orphan attempt_feedback nodes without ResourceFile. Count=%d',
                    \count($fbNodeIds)
                )
            );
        } else {
            error_log('[MIGRATION][cleanup] No orphan attempt_feedback nodes without ResourceFile found.');
        }
    }

    /**
     * Create an UploadedFile from an Asset using Flysystem streams.
     * This avoids loading the whole file content as a single string in memory.
     */
    private function createUploadedFileFromAsset(
        AssetRepository $assetRepo,
        Asset $asset,
        string $context
    ): ?UploadedFile {
        $filePath = '';
        $stream = null;
        $tmpPath = false;

        try {
            $filePath = $assetRepo->getStorage()->resolveUri($asset);
            $fs = $assetRepo->getFileSystem();

            if (!$fs->fileExists($filePath)) {
                error_log(
                    sprintf(
                        '[MIGRATION][%s] File does not exist in filesystem for Asset id=%s, path=%s',
                        $context,
                        (string) $asset->getId(),
                        $filePath
                    )
                );

                return null;
            }

            $stream = $fs->readStream($filePath);
            if (false === $stream) {
                error_log(
                    sprintf(
                        '[MIGRATION][%s] Could not open read stream for Asset id=%s, path=%s',
                        $context,
                        (string) $asset->getId(),
                        $filePath
                    )
                );

                return null;
            }

            $tmpPath = tempnam(sys_get_temp_dir(), 'asset_migrate_');
            if (false === $tmpPath) {
                error_log(
                    sprintf(
                        '[MIGRATION][%s] Failed to create temporary file for Asset id=%s',
                        $context,
                        (string) $asset->getId()
                    )
                );

                fclose($stream);

                return null;
            }

            $tmpHandle = fopen($tmpPath, 'wb');
            if (false === $tmpHandle) {
                error_log(
                    sprintf(
                        '[MIGRATION][%s] Failed to open temporary file for writing: %s (Asset id=%s)',
                        $context,
                        $tmpPath,
                        (string) $asset->getId()
                    )
                );

                fclose($stream);
                @unlink($tmpPath);

                return null;
            }

            stream_copy_to_stream($stream, $tmpHandle);
            fclose($stream);
            fclose($tmpHandle);

            $originalName = $asset->getTitle() ?: basename($filePath);

            $mimeType = 'application/octet-stream';
            if (method_exists($asset, 'getMimeType') && null !== $asset->getMimeType()) {
                $mimeType = (string) $asset->getMimeType();
            }

            // last argument "true" => test mode, do not check HTTP upload.
            return new UploadedFile($tmpPath, $originalName, $mimeType, null, true);
        } catch (Throwable $e) {
            error_log(
                sprintf(
                    '[MIGRATION][%s] Exception while creating UploadedFile for Asset id=%s, path=%s: %s',
                    $context,
                    (string) $asset->getId(),
                    $filePath,
                    $e->getMessage()
                )
            );

            if (is_resource($stream)) {
                fclose($stream);
            }

            if (false !== $tmpPath && is_file($tmpPath)) {
                @unlink($tmpPath);
            }

            return null;
        }
    }

    /**
     * Always get an EntityManager that is open and in sync with Doctrine.
     * This is important if a previous flush closed the EM.
     */
    private function ensureEntityManagerIsOpen(): void
    {
        /** @var ManagerRegistry $doctrine */
        $doctrine = $this->container->get('doctrine');

        $em = $doctrine->getManager();

        if (!$em->isOpen()) {
            $doctrine->resetManager();
            $em = $doctrine->getManager();

            error_log('[MIGRATION] EntityManager was closed and has been reset.');
        }

        $this->entityManager = $em;
    }

    /**
     * Always get an AssetRepository bound to the current EntityManager.
     */
    private function getAssetRepository(): AssetRepository
    {
        /** @var AssetRepository $assetRepo */
        $assetRepo = $this->container->get(AssetRepository::class);

        return $assetRepo;
    }

    /**
     * Format any kind of primary key / UUID value for logging.
     * - If it is a binary string, log hex.
     * - If it is an int, log the numeric value.
     * - If it is null, log "NULL".
     */
    private function formatIdForLog(mixed $value): string
    {
        if (null === $value) {
            return 'NULL';
        }

        if (\is_string($value)) {
            return bin2hex($value);
        }

        return (string) $value;
    }
}
