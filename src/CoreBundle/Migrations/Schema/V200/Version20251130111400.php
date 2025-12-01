<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Entity\Asset;
use Chamilo\CoreBundle\Entity\ResourceFile;
use Chamilo\CoreBundle\Entity\ResourceNode;
use Chamilo\CoreBundle\Entity\ResourceType;
use Chamilo\CoreBundle\Helpers\CreateUploadedFileHelper;
use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Chamilo\CoreBundle\Repository\AssetRepository;
use Doctrine\DBAL\Schema\Schema;
use Throwable;

final class Version20251130111400 extends AbstractMigrationChamilo
{
    private const DRY_RUN = false;

    public function getDescription(): string
    {
        return 'Migrate attempt_file / attempt_feedback assets to ResourceNode/ResourceFile and fill resource_node_id.';
    }

    public function up(Schema $schema): void
    {
        $this->entityManager->clear();

        /** @var AssetRepository $assetRepo */
        $assetRepo = $this->container->get(AssetRepository::class);

        $resourceTypeRepo = $this->entityManager->getRepository(ResourceType::class);

        $attemptFileType = $resourceTypeRepo->findOneBy(['title' => 'attempt_file']);
        $attemptFeedbackType = $resourceTypeRepo->findOneBy(['title' => 'attempt_feedback']);

        if (null === $attemptFileType || null === $attemptFeedbackType) {
            error_log('[MIGRATION] ResourceType "attempt_file" or "attempt_feedback" not found. Aborting data migration.');

            return;
        }

        $this->migrateAttemptFiles($assetRepo, $attemptFileType);
        $this->migrateAttemptFeedback($assetRepo, $attemptFeedbackType);
    }

    public function down(Schema $schema): void
    {
        // No-op: data migration is not reversible in a safe way.
    }

    private function migrateAttemptFiles(
        AssetRepository $assetRepo,
        ResourceType $attemptFileType
    ): void {
        $sql = 'SELECT id, attempt_id, asset_id
                FROM attempt_file
                WHERE asset_id IS NOT NULL
                  AND resource_node_id IS NULL';

        $rows = $this->connection->executeQuery($sql)->fetchAllAssociative();

        $total = \count($rows);
        $processed = 0;
        $migrated = 0;

        error_log('[MIGRATION][attempt_file] Candidates='.(string) $total);

        foreach ($rows as $row) {
            $processed++;

            $rowId = $row['id'];         // binary(16) UUID
            $assetId = $row['asset_id']; // binary(16) UUID

            $rowIdHex = bin2hex($rowId);
            $assetIdHex = bin2hex($assetId);

            error_log(
                sprintf(
                    '[MIGRATION][attempt_file] Processing rowId=%s assetId=%s',
                    $rowIdHex,
                    $assetIdHex
                )
            );

            try {
                /** @var Asset|null $asset */
                $asset = $assetRepo->find($assetId);

                if (null === $asset) {
                    error_log(
                        sprintf(
                            '[MIGRATION][attempt_file] Missing Asset for attempt_file.id=%s (assetId=%s)',
                            $rowIdHex,
                            $assetIdHex
                        )
                    );

                    continue;
                }

                $ok = $this->migrateSingleAssetToResourceNode(
                    'attempt_file',
                    'attempt_file',
                    $rowId,
                    $asset,
                    $assetRepo,
                    $attemptFileType
                );

                if ($ok) {
                    $migrated++;
                }
            } catch (Throwable $e) {
                error_log(
                    sprintf(
                        '[MIGRATION][attempt_file] Error for attempt_file.id=%s: %s',
                        $rowIdHex,
                        $e->getMessage()
                    )
                );
            }

            if (0 === $processed % 50) {
                // Free some memory
                $this->entityManager->clear(ResourceNode::class);
                $this->entityManager->clear(ResourceFile::class);
                $this->entityManager->clear(Asset::class);
            }
        }

        error_log(
            sprintf(
                '[MIGRATION][attempt_file] Processed=%d, Migrated=%d (Total=%d)',
                $processed,
                $migrated,
                $total
            )
        );
    }

    private function migrateAttemptFeedback(
        AssetRepository $assetRepo,
        ResourceType $attemptFeedbackType
    ): void {
        $sql = 'SELECT id, attempt_id, asset_id
                FROM attempt_feedback
                WHERE asset_id IS NOT NULL
                  AND resource_node_id IS NULL';

        $rows = $this->connection->executeQuery($sql)->fetchAllAssociative();

        $total = \count($rows);
        $processed = 0;
        $migrated = 0;

        error_log('[MIGRATION][attempt_feedback] Candidates='.(string) $total);

        foreach ($rows as $row) {
            $processed++;

            $rowId = $row['id'];         // binary(16) UUID
            $assetId = $row['asset_id']; // binary(16) UUID

            $rowIdHex = bin2hex($rowId);
            $assetIdHex = bin2hex($assetId);

            error_log(
                sprintf(
                    '[MIGRATION][attempt_feedback] Processing rowId=%s assetId=%s',
                    $rowIdHex,
                    $assetIdHex
                )
            );

            try {
                /** @var Asset|null $asset */
                $asset = $assetRepo->find($assetId);

                if (null === $asset) {
                    error_log(
                        sprintf(
                            '[MIGRATION][attempt_feedback] Missing Asset for attempt_feedback.id=%s (assetId=%s)',
                            $rowIdHex,
                            $assetIdHex
                        )
                    );

                    continue;
                }

                $ok = $this->migrateSingleAssetToResourceNode(
                    'attempt_feedback',
                    'attempt_feedback',
                    $rowId,
                    $asset,
                    $assetRepo,
                    $attemptFeedbackType
                );

                if ($ok) {
                    $migrated++;
                }
            } catch (Throwable $e) {
                error_log(
                    sprintf(
                        '[MIGRATION][attempt_feedback] Error for attempt_feedback.id=%s: %s',
                        $rowIdHex,
                        $e->getMessage()
                    )
                );
            }

            if (0 === $processed % 50) {
                $this->entityManager->clear(ResourceNode::class);
                $this->entityManager->clear(ResourceFile::class);
                $this->entityManager->clear(Asset::class);
            }
        }

        error_log(
            sprintf(
                '[MIGRATION][attempt_feedback] Processed=%d, Migrated=%d (Total=%d)',
                $processed,
                $migrated,
                $total
            )
        );
    }

    /**
     * Shared logic for attempt_file and attempt_feedback:
     *  - Read Asset content using AssetRepository.
     *  - Create a ResourceNode with the given ResourceType.
     *  - Create a ResourceFile with setFile() (Vich) using the Asset content.
     *  - Update resource_node_id in the corresponding table.
     *
     * @return bool true if the migration succeeded, false if it was skipped.
     */
    private function migrateSingleAssetToResourceNode(
        string $context,
        string $tableName,
        string $rowId,
        Asset $asset,
        AssetRepository $assetRepo,
        ResourceType $resourceType
    ): bool {
        $content = $assetRepo->getAssetContent($asset);

        if (!\is_string($content) || '' === $content) {
            $filePath = '';
            $exists = null;

            try {
                $filePath = $assetRepo->getStorage()->resolveUri($asset);
                $exists = $assetRepo->getFileSystem()->fileExists($filePath);
            } catch (Throwable $e) {
                error_log(
                    sprintf(
                        '[MIGRATION][%s] Error while checking filesystem for Asset id=%s: %s',
                        $context,
                        (string) $asset->getId(),
                        $e->getMessage()
                    )
                );
            }

            error_log(
                sprintf(
                    '[MIGRATION][%s] Empty content for Asset id=%s, title=%s, mime=%s, size=%d, path=%s, exists=%s',
                    $context,
                    (string) $asset->getId(),
                    (string) $asset->getTitle(),
                    (string) $asset->getMimeType(),
                    (int) $asset->getSize(),
                    $filePath,
                    true === $exists ? 'yes' : 'no'
                )
            );

            return false;
        }

        $originalName = $asset->getTitle();
        $mimeType = 'application/octet-stream';

        if (method_exists($asset, 'getMimeType') && null !== $asset->getMimeType()) {
            $mimeType = (string) $asset->getMimeType();
        }

        if (self::DRY_RUN) {
            error_log(
                sprintf(
                    '[MIGRATION][DRY_RUN][%s] Would create ResourceNode/ResourceFile for table=%s rowId=%s',
                    $context,
                    $tableName,
                    bin2hex($rowId)
                )
            );

            return true;
        }

        $node = new ResourceNode();
        $node->setTitle($originalName ?: 'attempt_' . $context);
        $node->setResourceType($resourceType);

        $this->entityManager->persist($node);

        $uploadedFile = CreateUploadedFileHelper::fromString(
            $originalName ?: 'file_' . $context,
            $mimeType,
            $content
        );

        $resourceFile = new ResourceFile();
        $resourceFile->setResourceNode($node);
        $resourceFile->setFile($uploadedFile);
        $this->entityManager->persist($resourceFile);
        $this->entityManager->flush();

        $this->connection->update(
            $tableName,
            ['resource_node_id' => $node->getId()],
            ['id' => $rowId]
        );

        error_log(
            sprintf(
                '[MIGRATION][%s] Migrated rowId=%s to ResourceNode id=%d',
                $context,
                bin2hex($rowId),
                (int) $node->getId()
            )
        );

        return true;
    }
}
