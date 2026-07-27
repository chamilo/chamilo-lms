<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Chamilo\CourseBundle\Entity\CStudentPublication;
use Chamilo\CourseBundle\Entity\CStudentPublicationComment;
use Chamilo\CourseBundle\Repository\CStudentPublicationCommentRepository;
use Chamilo\CourseBundle\Repository\CStudentPublicationRepository;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Schema\Schema;
use RuntimeException;
use Throwable;

final class Version20250501000100 extends AbstractMigrationChamilo
{
    private const int READ_BATCH_SIZE = 500;
    private const int FLUSH_BATCH_SIZE = 25;

    public function getDescription(): string
    {
        return 'Migrate remaining student publication files with schema-aware DBAL candidates and bounded filesystem batches';
    }

    public function up(Schema $schema): void
    {
        /** @var CStudentPublicationRepository $publicationRepository */
        $publicationRepository = $this->container->get(CStudentPublicationRepository::class);

        /** @var CStudentPublicationCommentRepository $commentRepository */
        $commentRepository = $this->container->get(CStudentPublicationCommentRepository::class);

        $publicationSummary = $this->migratePublicationFiles($publicationRepository);
        $commentSummary = $this->migrateCommentFiles($commentRepository);

        $this->entityManager->flush();
        $this->entityManager->clear();

        $this->getLogger()->info('Student publication supplemental file migration completed.', [
            'publications_seen' => $publicationSummary['seen'],
            'publication_files_migrated' => $publicationSummary['migrated'],
            'publication_files_missing' => $publicationSummary['missing'],
            'publication_phase_skipped' => $publicationSummary['skipped'],
            'comments_seen' => $commentSummary['seen'],
            'comment_files_migrated' => $commentSummary['migrated'],
            'comment_files_missing' => $commentSummary['missing'],
        ]);
    }

    /**
     * @return array{seen: int, migrated: int, missing: int, skipped: bool}
     */
    private function migratePublicationFiles(CStudentPublicationRepository $repository): array
    {
        $columns = $this->getTableColumns('c_student_publication');
        $hasUrl = isset($columns['url']);
        $hasCorrectionUrl = isset($columns['url_correction']);

        if (!$hasUrl && !$hasCorrectionUrl) {
            // These legacy source columns are intentionally removed by
            // Version20240811221400. Earlier Ricky migrations already copied
            // the available publication and correction files before that
            // destructive schema cleanup.
            $this->getLogger()->notice(
                'Skipping supplemental publication files: legacy url columns no longer exist in c_student_publication.'
            );

            return ['seen' => 0, 'migrated' => 0, 'missing' => 0, 'skipped' => true];
        }

        $urlExpression = $hasUrl ? 'publication.url' : 'NULL';
        $correctionExpression = $hasCorrectionUrl ? 'publication.url_correction' : 'NULL';
        $pathPredicates = [];
        if ($hasUrl) {
            $pathPredicates[] = "(publication.url IS NOT NULL AND publication.url <> '')";
        }
        if ($hasCorrectionUrl) {
            $pathPredicates[] = "(publication.url_correction IS NOT NULL AND publication.url_correction <> '')";
        }

        $lastIid = 0;
        $seen = 0;
        $migrated = 0;
        $missing = 0;
        $pendingFlush = 0;

        while (true) {
            $sql = \sprintf(
                <<<'SQL'
SELECT
    publication.iid,
    publication.resource_node_id,
    %s AS legacy_url,
    %s AS legacy_correction_url,
    course.directory
FROM c_student_publication publication
INNER JOIN course
    ON course.id = (
        SELECT MIN(link.c_id)
        FROM resource_link link
        WHERE link.resource_node_id = publication.resource_node_id
          AND link.c_id IS NOT NULL
    )
WHERE publication.resource_node_id IS NOT NULL
  AND publication.filetype = 'file'
  AND publication.iid > :lastIid
  AND (%s)
ORDER BY publication.iid
LIMIT %d
SQL,
                $urlExpression,
                $correctionExpression,
                implode(' OR ', $pathPredicates),
                self::READ_BATCH_SIZE
            );

            $rows = $this->connection->fetchAllAssociative($sql, ['lastIid' => $lastIid]);
            if ([] === $rows) {
                break;
            }

            $lastIid = (int) $rows[array_key_last($rows)]['iid'];
            $existingFiles = $this->loadExistingResourceFilesForRows($rows);

            foreach ($rows as $row) {
                ++$seen;
                $iid = (int) $row['iid'];
                $nodeId = (int) $row['resource_node_id'];
                $directory = (string) $row['directory'];
                $publication = null;

                foreach ([
                    ['path' => (string) ($row['legacy_url'] ?? ''), 'is_correction' => false],
                    ['path' => (string) ($row['legacy_correction_url'] ?? ''), 'is_correction' => true],
                ] as $candidate) {
                    $legacyPath = trim($candidate['path']);
                    if ('' === $legacyPath) {
                        continue;
                    }

                    $filename = basename($legacyPath);
                    if ('' === $filename) {
                        continue;
                    }

                    $fileKey = $nodeId.':'.$filename;
                    if (isset($existingFiles[$fileKey])) {
                        continue;
                    }

                    $source = $this->resolveCourseFile($directory, $legacyPath, true);
                    if (null === $source) {
                        ++$missing;

                        continue;
                    }

                    $publication ??= $repository->find($iid);
                    if (!$publication instanceof CStudentPublication || !$publication->hasResourceNode()) {
                        continue;
                    }

                    if ($this->addLegacyFileToResource($source, $repository, $publication, $iid, $filename)) {
                        if ($candidate['is_correction']) {
                            $publication->setExtensions($filename);
                        }
                        $this->entityManager->persist($publication);
                        $existingFiles[$fileKey] = true;
                        ++$migrated;
                        ++$pendingFlush;
                    }
                }

                if ($pendingFlush >= self::FLUSH_BATCH_SIZE) {
                    $this->entityManager->flush();
                    $this->entityManager->clear();
                    $pendingFlush = 0;
                }
            }

            $this->getLogger()->info('Student publication supplemental file progress.', [
                'seen' => $seen,
                'migrated' => $migrated,
                'missing' => $missing,
                'last_iid' => $lastIid,
            ]);
        }

        $this->entityManager->flush();
        $this->entityManager->clear();

        return ['seen' => $seen, 'migrated' => $migrated, 'missing' => $missing, 'skipped' => false];
    }

    /**
     * @return array{seen: int, migrated: int, missing: int}
     */
    private function migrateCommentFiles(CStudentPublicationCommentRepository $repository): array
    {
        $columns = $this->getTableColumns('c_student_publication_comment');
        if (!isset($columns['file'])) {
            $this->getLogger()->notice(
                'Skipping supplemental comment files: file column does not exist in c_student_publication_comment.'
            );

            return ['seen' => 0, 'migrated' => 0, 'missing' => 0];
        }

        $lastIid = 0;
        $seen = 0;
        $migrated = 0;
        $missing = 0;
        $pendingFlush = 0;

        while (true) {
            $rows = $this->connection->fetchAllAssociative(
                \sprintf(
                    <<<'SQL'
SELECT
    comment.iid,
    comment.resource_node_id,
    comment.file,
    course.directory
FROM c_student_publication_comment comment
INNER JOIN course
    ON course.id = (
        SELECT MIN(link.c_id)
        FROM resource_link link
        WHERE link.resource_node_id = comment.resource_node_id
          AND link.c_id IS NOT NULL
    )
WHERE comment.resource_node_id IS NOT NULL
  AND comment.file IS NOT NULL
  AND comment.file <> ''
  AND comment.iid > :lastIid
ORDER BY comment.iid
LIMIT %d
SQL,
                    self::READ_BATCH_SIZE
                ),
                ['lastIid' => $lastIid]
            );

            if ([] === $rows) {
                break;
            }

            $lastIid = (int) $rows[array_key_last($rows)]['iid'];
            $existingFiles = $this->loadExistingResourceFilesForRows($rows);

            foreach ($rows as $row) {
                ++$seen;
                $iid = (int) $row['iid'];
                $nodeId = (int) $row['resource_node_id'];
                $legacyPath = trim((string) $row['file']);
                $filename = basename($legacyPath);
                $fileKey = $nodeId.':'.$filename;

                if ('' === $filename || isset($existingFiles[$fileKey])) {
                    continue;
                }

                $source = $this->resolveCourseFile((string) $row['directory'], $legacyPath, true);
                if (null === $source) {
                    ++$missing;

                    continue;
                }

                $comment = $repository->find($iid);
                if (!$comment instanceof CStudentPublicationComment || !$comment->hasResourceNode()) {
                    continue;
                }

                if ($this->addLegacyFileToResource($source, $repository, $comment, $iid, $filename)) {
                    $this->entityManager->persist($comment);
                    $existingFiles[$fileKey] = true;
                    ++$migrated;
                    ++$pendingFlush;
                }

                if ($pendingFlush >= self::FLUSH_BATCH_SIZE) {
                    $this->entityManager->flush();
                    $this->entityManager->clear();
                    $pendingFlush = 0;
                }
            }

            $this->getLogger()->info('Student publication comment file progress.', [
                'seen' => $seen,
                'migrated' => $migrated,
                'missing' => $missing,
                'last_iid' => $lastIid,
            ]);
        }

        $this->entityManager->flush();
        $this->entityManager->clear();

        return ['seen' => $seen, 'migrated' => $migrated, 'missing' => $missing];
    }

    private function resolveCourseFile(string $courseDirectory, string $legacyPath, bool $includeWorkFallback): ?string
    {
        $legacyPath = ltrim($legacyPath, '/');
        $base = $this->getUpdateRootPath().'/app/courses/'.$courseDirectory.'/';
        $candidates = [$base.$legacyPath];

        if ($includeWorkFallback) {
            $candidates[] = $base.'work/'.$legacyPath;
            $candidates[] = $base.'work/'.basename($legacyPath);
        }

        foreach (array_unique($candidates) as $candidate) {
            if ($this->fileExists($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     *
     * @return array<string, true>
     */
    private function loadExistingResourceFilesForRows(array $rows): array
    {
        $nodeIds = [];
        foreach ($rows as $row) {
            $nodeId = (int) ($row['resource_node_id'] ?? 0);
            if ($nodeId > 0) {
                $nodeIds[$nodeId] = $nodeId;
            }
        }

        if ([] === $nodeIds) {
            return [];
        }

        $fileRows = $this->connection->executeQuery(
            <<<'SQL'
SELECT resource_node_id, title, original_name
FROM resource_file
WHERE resource_node_id IN (:nodeIds)
SQL,
            ['nodeIds' => array_values($nodeIds)],
            ['nodeIds' => ArrayParameterType::INTEGER]
        )->fetchAllAssociative();

        $result = [];
        foreach ($fileRows as $fileRow) {
            $nodeId = (int) $fileRow['resource_node_id'];
            foreach ([(string) ($fileRow['title'] ?? ''), (string) ($fileRow['original_name'] ?? '')] as $name) {
                if ('' !== $name) {
                    $result[$nodeId.':'.$name] = true;
                }
            }
        }

        return $result;
    }

    /**
     * @return array<string, true>
     */
    private function getTableColumns(string $tableName): array
    {
        try {
            $table = $this->connection->createSchemaManager()->introspectTable($tableName);
            $result = [];
            foreach ($table->getColumns() as $column) {
                $result[$column->getName()] = true;
            }

            return $result;
        } catch (Throwable $exception) {
            throw new RuntimeException(\sprintf('Unable to inspect table %s: %s', $tableName, $exception->getMessage()), 0, $exception);
        }
    }
}
