<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Entity\Asset;
use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Uid\Uuid;

final class Version20240519210000 extends AbstractMigrationChamilo
{
    private const int SELECT_BATCH_SIZE = 250;
    private const int ASSET_FLUSH_BATCH_SIZE = 25;

    public function getDescription(): string
    {
        return 'Migrate exercise audio files with direct course paths and batched assets';
    }

    public function isTransactional(): bool
    {
        // Filesystem writes cannot be rolled back. Each batch is made
        // idempotent through attempt_file.attempt_id checks.
        return false;
    }

    public function up(Schema $schema): void
    {
        if (!$schema->hasTable('attempt_file')) {
            $this->getLogger()->warning('attempt_file table is missing; exercise audio migration skipped.');

            return;
        }

        $attemptFileTable = $this->connection->createSchemaManager()->introspectTable('attempt_file');
        if (!$attemptFileTable->hasColumn('asset_id')) {
            $this->getLogger()->warning('attempt_file.asset_id is missing; exercise audio migration skipped.');

            return;
        }

        $lastId = 0;
        $seen = 0;
        $migrated = 0;
        $missing = 0;
        $queued = [];

        while (true) {
            $rows = $this->connection->fetchAllAssociative(
                \sprintf(<<<'SQL'
SELECT
    attempt.id,
    attempt.exe_id,
    attempt.question_id,
    attempt.user_id,
    attempt.filename,
    exercise.session_id,
    exercise.exe_exo_id,
    course.directory
FROM track_e_attempt attempt
INNER JOIN track_e_exercises exercise
    ON exercise.exe_id = attempt.exe_id
INNER JOIN course
    ON course.id = exercise.c_id
WHERE attempt.id > :lastId
  AND attempt.filename IS NOT NULL
  AND TRIM(attempt.filename) <> ''
  AND NOT EXISTS (
      SELECT 1
      FROM attempt_file existing_file
      WHERE existing_file.attempt_id = attempt.id
  )
ORDER BY attempt.id
LIMIT %d
SQL, self::SELECT_BATCH_SIZE),
                ['lastId' => $lastId]
            );

            if ([] === $rows) {
                break;
            }

            $lastId = (int) $rows[array_key_last($rows)]['id'];

            foreach ($rows as $row) {
                ++$seen;
                $fileName = basename((string) $row['filename']);
                $relativePath = \sprintf(
                    '%d/%d/%d/%d/%s',
                    (int) $row['session_id'],
                    (int) $row['exe_exo_id'],
                    (int) $row['question_id'],
                    (int) $row['user_id'],
                    $fileName
                );
                $filePath = $this->getUpdateRootPath().'/app/courses/'.(string) $row['directory'].'/exercises/'.$relativePath;

                if (!$this->fileExists($filePath)) {
                    ++$missing;
                    $this->warnIf(true, 'Exercise audio file not found: '.$filePath);

                    continue;
                }

                $mimeType = mime_content_type($filePath) ?: 'application/octet-stream';
                $uploadedFile = new UploadedFile($filePath, $fileName, $mimeType, null, true);
                $asset = (new Asset())
                    ->setCategory(Asset::EXERCISE_ATTEMPT)
                    ->setTitle($fileName)
                    ->setFile($uploadedFile)
                ;

                $this->entityManager->persist($asset);
                $queued[] = [
                    'attempt_id' => (int) $row['id'],
                    'asset' => $asset,
                ];

                if (\count($queued) >= self::ASSET_FLUSH_BATCH_SIZE) {
                    $migrated += $this->flushAssetBatch($queued, $attemptFileTable);
                    $queued = [];
                }
            }

            $this->getLogger()->info('Exercise audio migration progress.', [
                'seen' => $seen,
                'migrated' => $migrated,
                'missing' => $missing,
                'last_attempt_id' => $lastId,
            ]);
        }

        if ([] !== $queued) {
            $migrated += $this->flushAssetBatch($queued, $attemptFileTable);
        }

        $this->getLogger()->info('Exercise audio migration completed.', [
            'seen' => $seen,
            'migrated' => $migrated,
            'missing' => $missing,
        ]);
    }

    /**
     * @param array<int, array{attempt_id: int, asset: Asset}> $queued
     */
    private function flushAssetBatch(array $queued, Table $attemptFileTable): int
    {
        $this->entityManager->flush();
        $now = gmdate('Y-m-d H:i:s');
        $inserted = 0;

        foreach ($queued as $item) {
            $attemptId = $item['attempt_id'];
            if (false !== $this->connection->fetchOne(
                'SELECT id FROM attempt_file WHERE attempt_id = :attemptId LIMIT 1',
                ['attemptId' => $attemptId]
            )) {
                continue;
            }

            $row = [
                'id' => Uuid::v4()->toBinary(),
                'attempt_id' => $attemptId,
                'asset_id' => $item['asset']->getId()->toBinary(),
            ];
            $types = [
                'id' => ParameterType::BINARY,
                'asset_id' => ParameterType::BINARY,
            ];

            if ($attemptFileTable->hasColumn('created_at')) {
                $row['created_at'] = $now;
            }
            if ($attemptFileTable->hasColumn('updated_at')) {
                $row['updated_at'] = $now;
            }
            if ($attemptFileTable->hasColumn('comment')) {
                $row['comment'] = '';
            }

            $this->connection->insert('attempt_file', $row, $types);
            ++$inserted;
        }

        $this->entityManager->clear(Asset::class);

        return $inserted;
    }

    public function down(Schema $schema): void
    {
        // Filesystem migration is not reversible.
    }
}
