<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Command;

use Chamilo\CoreBundle\Component\LegacyCliBootstrapper;
use Chamilo\CoreBundle\Entity\ResourceFile;
use Chamilo\CoreBundle\Entity\SearchEngineRef;
use Chamilo\CoreBundle\Search\Xapian\CourseDescriptionXapianIndexer;
use Chamilo\CoreBundle\Search\Xapian\DocumentXapianIndexer;
use Chamilo\CoreBundle\Search\Xapian\LpXapianIndexer;
use Chamilo\CoreBundle\Search\Xapian\QuestionXapianIndexer;
use Chamilo\CoreBundle\Search\Xapian\QuizXapianIndexer;
use Chamilo\CoreBundle\Search\Xapian\SearchIndexPathResolver;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CCourseDescription;
use Chamilo\CourseBundle\Entity\CDocument;
use Chamilo\CourseBundle\Entity\CLp;
use Chamilo\CourseBundle\Entity\CQuiz;
use Chamilo\CourseBundle\Entity\CQuizQuestion;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\PostLoadEventArgs;
use Doctrine\ORM\Events;
use Doctrine\ORM\QueryBuilder;
use RuntimeException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Process\Process;
use Throwable;
use XapianWritableDatabase;

use const DIRECTORY_SEPARATOR;
use const FILTER_VALIDATE_INT;

#[AsCommand(
    name: 'chamilo:search:xapian:rebuild',
    description: 'Rebuild the Xapian search index from existing course resources.',
)]
final class XapianRebuildCommand extends Command
{
    private const int DEFAULT_BATCH_SIZE = 100;
    private const int MAX_BATCH_SIZE = 10000;
    private const array RESOURCE_KEYS = ['documents', 'course_descriptions', 'quizzes', 'questions', 'learning_paths'];

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly SettingsManager $settingsManager,
        private readonly SearchIndexPathResolver $indexPathResolver,
        private readonly DocumentXapianIndexer $documentIndexer,
        private readonly CourseDescriptionXapianIndexer $courseDescriptionIndexer,
        private readonly QuizXapianIndexer $quizIndexer,
        private readonly QuestionXapianIndexer $questionIndexer,
        private readonly LpXapianIndexer $lpIndexer,
        private readonly KernelInterface $kernel,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Show what would be rebuilt without changing the index or the database.')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Actually rebuild the Xapian index. Without this option the command is read-only.')
            ->addOption('batch-size', null, InputOption::VALUE_REQUIRED, 'Number of resources indexed per database transaction (each batch is committed on its own).', self::DEFAULT_BATCH_SIZE)
            ->setHelp(
                <<<'HELP'
Rebuilds Chamilo's Xapian index from existing resources.

The command is read-only by default:

  php bin/console chamilo:search:xapian:rebuild --dry-run

To rebuild the index:

  php bin/console chamilo:search:xapian:rebuild --force

Operational notes:
  - run it during a maintenance window;
  - stop cron/workers and avoid resource writes while it runs;
  - the current var/search directory is renamed to a timestamped backup;
  - search_engine_ref is rebuilt in short transactions of --batch-size resources
    (default 100), so the rebuild does not keep rows locked while it runs;
  - resource tables (resource_node, resource_file) are read, not updated;
  - resources deleted while the rebuild runs are skipped;
  - if the rebuild fails, the previous index and search_engine_ref rows are restored;
  - search_engine_field_value is preserved;
  - legacy c_lp_item.search_did values are cleared at the end, because they point to the old physical index.
HELP
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $force = (bool) $input->getOption('force');
        $explicitDryRun = (bool) $input->getOption('dry-run');
        $batchSize = filter_var(
            $input->getOption('batch-size'),
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1, 'max_range' => self::MAX_BATCH_SIZE]]
        );

        if (!\is_int($batchSize)) {
            $io->error(\sprintf('--batch-size must be an integer between 1 and %d.', self::MAX_BATCH_SIZE));

            return Command::FAILURE;
        }

        if ($force && $explicitDryRun) {
            $io->error('Use either --dry-run or --force, not both.');

            return Command::FAILURE;
        }

        if (!class_exists(XapianWritableDatabase::class)) {
            $io->error('Xapian PHP extension is not loaded.');

            return Command::FAILURE;
        }

        $isSearchEnabled = 'true' === (string) $this->settingsManager->getSetting('search.search_enabled', true);
        if (!$isSearchEnabled) {
            $io->error('The setting search.search_enabled is not true. Enable search before rebuilding the Xapian index.');

            return Command::FAILURE;
        }

        $indexDir = $this->indexPathResolver->getConfiguredIndexDir();
        $this->assertSafeIndexDirectory($indexDir);

        $plan = $this->buildPlan();

        $io->section('Xapian rebuild plan');
        $io->definitionList(
            ['Mode' => $force ? 'rebuild' : 'dry-run'],
            ['Index directory' => $indexDir],
            ['Batch size' => (string) $batchSize],
            ['Existing search_engine_ref rows' => (string) $plan['existing_refs']],
            ['Legacy LP item search_did rows to clear' => (string) $plan['legacy_lp_item_refs']],
        );

        $io->table(
            ['Resource type', 'Indexable rows'],
            [
                ['Documents', $plan['documents']],
                ['Course descriptions', $plan['course_descriptions']],
                ['Quizzes', $plan['quizzes']],
                ['Questions', $plan['questions']],
                ['Learning paths', $plan['learning_paths']],
            ]
        );

        if (!$force) {
            $io->success('Dry-run completed. Re-run with --force during maintenance to rebuild the index.');

            return Command::SUCCESS;
        }

        // Text extraction asks AiFeatureAccessHelper whether AI media extraction is enabled
        // for each course, which can reach the legacy BuyCourses plugin and the legacy
        // Container::$container — never anchored to this command's own container otherwise.
        // Kept outside the try below: a failure there would make restoreIndexDirectory()
        // remove the live index before a backup exists. See LegacyCliBootstrapper.
        LegacyCliBootstrapper::bootstrap($this->kernel, $this->entityManager);

        $backupPath = null;

        try {
            $backupPath = $this->prepareFreshIndexDirectory($indexDir);
            $stats = $this->rebuildDatabaseAndIndex($io, $batchSize);
            $this->runXapianCheck($io, $indexDir);
        } catch (Throwable $exception) {
            $this->restoreIndexDirectory($indexDir, $backupPath);
            $io->error($exception->getMessage());

            return Command::FAILURE;
        }

        $io->section('Rebuild summary');
        $io->table(
            ['Resource type', 'Indexed', 'Failed', 'Skipped (deleted meanwhile)'],
            [
                ['Documents', $stats['documents']['indexed'], $stats['documents']['failed'], $stats['documents']['skipped']],
                ['Course descriptions', $stats['course_descriptions']['indexed'], $stats['course_descriptions']['failed'], $stats['course_descriptions']['skipped']],
                ['Quizzes', $stats['quizzes']['indexed'], $stats['quizzes']['failed'], $stats['quizzes']['skipped']],
                ['Questions', $stats['questions']['indexed'], $stats['questions']['failed'], $stats['questions']['skipped']],
                ['Learning paths', $stats['learning_paths']['indexed'], $stats['learning_paths']['failed'], $stats['learning_paths']['skipped']],
            ]
        );

        $io->definitionList(
            ['New search_engine_ref rows' => (string) $stats['new_refs']],
            ['Legacy LP item search_did rows cleared' => (string) $stats['legacy_lp_item_refs_cleared']],
            ['Backup directory' => $backupPath ?? 'none'],
        );

        $io->success('Xapian index rebuilt successfully. Keep the backup until searches are validated.');

        return Command::SUCCESS;
    }

    /**
     * @return array<string,int>
     */
    private function buildPlan(): array
    {
        $connection = $this->entityManager->getConnection();

        return [
            'existing_refs' => (int) $connection->fetchOne('SELECT COUNT(*) FROM search_engine_ref'),
            'legacy_lp_item_refs' => (int) $connection->fetchOne('SELECT COUNT(*) FROM c_lp_item WHERE search_did IS NOT NULL'),
            'documents' => $this->countIndexableEntities(CDocument::class, true),
            'course_descriptions' => $this->countIndexableEntities(CCourseDescription::class),
            'quizzes' => $this->countIndexableEntities(CQuiz::class),
            'questions' => $this->countIndexableEntities(CQuizQuestion::class),
            'learning_paths' => $this->countIndexableEntities(CLp::class),
        ];
    }

    /**
     * @param class-string $className
     */
    private function countIndexableEntities(string $className, bool $excludeDocumentFolders = false): int
    {
        return (int) $this->createIndexableQueryBuilder($className, $excludeDocumentFolders)
            ->select('COUNT(entity.iid)')
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    /**
     * Resources whose resource node still exists (the inner join skips dangling references).
     *
     * @param class-string $className
     */
    private function createIndexableQueryBuilder(string $className, bool $excludeDocumentFolders): QueryBuilder
    {
        $qb = $this->entityManager->createQueryBuilder()
            ->from($className, 'entity')
            ->innerJoin('entity.resourceNode', 'node')
        ;

        if ($excludeDocumentFolders) {
            $qb
                ->andWhere('entity.filetype <> :folder')
                ->setParameter('folder', 'folder')
            ;
        }

        return $qb;
    }

    /**
     * Rebuilds search_engine_ref and the Xapian index in short, batched transactions.
     *
     * Each batch of $batchSize resources is committed on its own, so the command never keeps
     * one transaction (and its row locks / undo log) open for the whole run. On failure the
     * previous search_engine_ref rows are restored, so they keep matching the restored index.
     *
     * @return array<string,mixed>
     */
    private function rebuildDatabaseAndIndex(SymfonyStyle $io, int $batchSize): array
    {
        $connection = $this->entityManager->getConnection();

        $previousRefs = $this->snapshotSearchEngineRefs();

        $readOnlyGuard = $this->createReadOnlyGuard();
        $eventManager = $this->entityManager->getEventManager();
        $eventManager->addEventListener([Events::postLoad], $readOnlyGuard);

        try {
            $connection->executeStatement('DELETE FROM search_engine_ref');

            $stats = [
                'documents' => $this->reindexEntities(
                    $io,
                    'Documents',
                    CDocument::class,
                    fn (CDocument $document): ?int => $this->documentIndexer->indexDocument($document),
                    $batchSize,
                    true
                ),
                'course_descriptions' => $this->reindexEntities(
                    $io,
                    'Course descriptions',
                    CCourseDescription::class,
                    fn (CCourseDescription $description): ?int => $this->courseDescriptionIndexer->indexCourseDescription($description),
                    $batchSize
                ),
                'quizzes' => $this->reindexEntities(
                    $io,
                    'Quizzes',
                    CQuiz::class,
                    fn (CQuiz $quiz): ?int => $this->quizIndexer->indexQuiz($quiz),
                    $batchSize
                ),
                'questions' => $this->reindexEntities(
                    $io,
                    'Questions',
                    CQuizQuestion::class,
                    fn (CQuizQuestion $question): ?int => $this->questionIndexer->indexQuestion($question),
                    $batchSize
                ),
                'learning_paths' => $this->reindexEntities(
                    $io,
                    'Learning paths',
                    CLp::class,
                    fn (CLp $lp): ?int => $this->lpIndexer->indexLp($lp),
                    $batchSize
                ),
            ];

            $failed = 0;
            $indexed = 0;
            foreach (self::RESOURCE_KEYS as $key) {
                $failed += $stats[$key]['failed'];
                $indexed += $stats[$key]['indexed'];
            }

            if ($failed > 0) {
                throw new RuntimeException(\sprintf('Xapian rebuild stopped: %d expected resources could not be indexed.', $failed));
            }

            $newRefs = (int) $connection->fetchOne('SELECT COUNT(*) FROM search_engine_ref');

            // More rows than indexed resources is fine: batches no longer block the web
            // indexing listeners, which may add references for resources edited meanwhile.
            if ($newRefs < $indexed) {
                throw new RuntimeException(\sprintf('Xapian rebuild stopped: search_engine_ref has fewer rows (%d) than indexed resources (%d).', $newRefs, $indexed));
            }

            // Only once the new index is complete: these ids point to the old physical index.
            $stats['legacy_lp_item_refs_cleared'] = $connection->executeStatement(
                'UPDATE c_lp_item SET search_did = NULL WHERE search_did IS NOT NULL'
            );
            $stats['new_refs'] = $newRefs;

            return $stats;
        } catch (Throwable $exception) {
            if ($connection->isTransactionActive()) {
                $connection->rollBack();
            }

            $this->restoreSearchEngineRefs($previousRefs, $batchSize);

            throw $exception;
        } finally {
            $eventManager->removeEventListener([Events::postLoad], $readOnlyGuard);
        }
    }

    /**
     * Marks every entity loaded during the rebuild as read-only, except the search references
     * the indexers maintain. Loading a ResourceFile makes it dirty (VichUploader injects the file
     * on postLoad and ResourceFile::setFile() bumps updatedAt), so without this guard each
     * indexer flush would issue an UPDATE on resource_file and keep that row locked.
     */
    private function createReadOnlyGuard(): object
    {
        return new class {
            public function postLoad(PostLoadEventArgs $args): void
            {
                $entity = $args->getObject();
                if ($entity instanceof SearchEngineRef) {
                    return;
                }

                $unitOfWork = $args->getObjectManager()->getUnitOfWork();
                if ($unitOfWork->isInIdentityMap($entity)) {
                    $unitOfWork->markReadOnly($entity);
                }
            }
        };
    }

    /**
     * @param class-string $className
     *
     * @return array{indexed:int,failed:int,skipped:int}
     */
    private function reindexEntities(
        SymfonyStyle $io,
        string $label,
        string $className,
        callable $indexCallback,
        int $batchSize,
        bool $excludeDocumentFolders = false,
    ): array {
        $io->text('Reindexing '.$label.'...');

        $connection = $this->entityManager->getConnection();

        $indexed = 0;
        $failed = 0;
        $skipped = 0;
        $processed = 0;
        $lastId = 0;

        do {
            // Keyset pagination: every batch reads current data, with no long-lived cursor or snapshot.
            $entities = $this->createIndexableQueryBuilder($className, $excludeDocumentFolders)
                ->select('entity')
                ->andWhere('entity.iid > :lastId')
                ->setParameter('lastId', $lastId)
                ->orderBy('entity.iid', 'ASC')
                ->setMaxResults($batchSize)
                ->getQuery()
                ->getResult()
            ;

            if ([] === $entities) {
                break;
            }

            $connection->beginTransaction();

            try {
                foreach ($entities as $entity) {
                    $lastId = (int) $entity->getIid();
                    ++$processed;

                    $resourceNodeId = (int) $entity->getResourceNode()?->getId();
                    if (!$this->lockExistingResourceNode($resourceNodeId)) {
                        // Deleted since the batch was read: nothing to index, and inserting its
                        // search_engine_ref row would fail on the resource_node foreign key.
                        ++$skipped;

                        continue;
                    }

                    try {
                        $docId = $indexCallback($entity);
                        if (\is_int($docId) && $docId > 0) {
                            ++$indexed;
                        } else {
                            ++$failed;
                        }
                    } catch (Throwable $exception) {
                        if (!$this->entityManager->isOpen()) {
                            throw $exception;
                        }

                        ++$failed;
                        error_log('[Xapian] Rebuild failed for '.$label.': '.$exception->getMessage());
                    }
                }

                $this->persistCachedMediaText();
                $this->entityManager->flush();
                $connection->commit();
            } catch (Throwable $exception) {
                if ($connection->isTransactionActive()) {
                    $connection->rollBack();
                }

                throw $exception;
            }

            $this->entityManager->clear();
            gc_collect_cycles();
            $io->text(\sprintf('  %s processed: %d', $label, $processed));
        } while (\count($entities) === $batchSize);

        $io->text(\sprintf('  %s indexed: %d, failed: %d, skipped (deleted meanwhile): %d', $label, $indexed, $failed, $skipped));

        return [
            'indexed' => $indexed,
            'failed' => $failed,
            'skipped' => $skipped,
        ];
    }

    /**
     * Shared-locks the node until the batch commits (the same lock the search_engine_ref
     * foreign key check takes), so it cannot disappear between this check and the insert.
     */
    private function lockExistingResourceNode(int $resourceNodeId): bool
    {
        if ($resourceNodeId <= 0) {
            return false;
        }

        return false !== $this->entityManager->getConnection()->fetchOne(
            'SELECT id FROM resource_node WHERE id = ? LOCK IN SHARE MODE',
            [$resourceNodeId]
        );
    }

    /**
     * Entities loaded by the rebuild are read-only, so the AI media text cache that
     * DocumentRawTextExtractor stores in ResourceFile::$metadata is written explicitly,
     * touching only that column and only for the files whose cache actually changed.
     */
    private function persistCachedMediaText(): void
    {
        $unitOfWork = $this->entityManager->getUnitOfWork();
        $connection = $this->entityManager->getConnection();

        foreach ($unitOfWork->getIdentityMap()[ResourceFile::class] ?? [] as $resourceFile) {
            if (!$resourceFile instanceof ResourceFile || $unitOfWork->isUninitializedObject($resourceFile)) {
                continue;
            }

            $originalMetadata = $unitOfWork->getOriginalEntityData($resourceFile)['metadata'] ?? null;
            $metadata = $resourceFile->getMetadata();
            if ($metadata === $originalMetadata || null === $resourceFile->getId()) {
                continue;
            }

            $connection->update(
                'resource_file',
                ['metadata' => $metadata],
                ['id' => $resourceFile->getId()],
                ['metadata' => Types::JSON]
            );
        }
    }

    /**
     * @return array<int,int> resource_node_id => search_did
     */
    private function snapshotSearchEngineRefs(): array
    {
        $refs = [];
        $rows = $this->entityManager->getConnection()->iterateNumeric(
            'SELECT resource_node_id, search_did FROM search_engine_ref WHERE resource_node_id IS NOT NULL'
        );

        foreach ($rows as $row) {
            $refs[(int) $row[0]] = (int) $row[1];
        }

        return $refs;
    }

    /**
     * Puts back the references of the index that restoreIndexDirectory() restores,
     * skipping resource nodes deleted in the meantime.
     *
     * @param array<int,int> $previousRefs
     */
    private function restoreSearchEngineRefs(array $previousRefs, int $batchSize): void
    {
        $connection = $this->entityManager->getConnection();

        try {
            $connection->executeStatement('DELETE FROM search_engine_ref');

            foreach (array_chunk($previousRefs, $batchSize, true) as $chunk) {
                $connection->beginTransaction();
                foreach ($chunk as $resourceNodeId => $searchDid) {
                    $connection->executeStatement(
                        'INSERT INTO search_engine_ref (resource_node_id, search_did) SELECT id, ? FROM resource_node WHERE id = ?',
                        [$searchDid, $resourceNodeId]
                    );
                }
                $connection->commit();
            }
        } catch (Throwable $exception) {
            if ($connection->isTransactionActive()) {
                $connection->rollBack();
            }

            error_log('[Xapian] Rebuild could not restore the previous search_engine_ref rows: '.$exception->getMessage());
        }
    }

    private function assertSafeIndexDirectory(string $indexDir): void
    {
        $indexDir = rtrim($indexDir, DIRECTORY_SEPARATOR);
        if ('' === $indexDir || DIRECTORY_SEPARATOR === $indexDir) {
            throw new RuntimeException('Unsafe Xapian index directory configuration.');
        }

        if (is_file($indexDir)) {
            throw new RuntimeException('The configured Xapian index path exists and is a file: '.$indexDir);
        }

        $parentDir = \dirname($indexDir);
        if ('' === $parentDir || DIRECTORY_SEPARATOR === $parentDir || $parentDir === $indexDir) {
            throw new RuntimeException('Unsafe Xapian index parent directory configuration.');
        }
    }

    private function prepareFreshIndexDirectory(string $indexDir): ?string
    {
        $indexDir = rtrim($indexDir, DIRECTORY_SEPARATOR);
        $parentDir = \dirname($indexDir);

        if (!is_dir($parentDir) && !@mkdir($parentDir, 0775, true) && !is_dir($parentDir)) {
            throw new RuntimeException('Unable to create Xapian index parent directory: '.$parentDir);
        }

        $backupPath = null;
        if (is_dir($indexDir)) {
            $backupPath = $this->buildBackupPath($indexDir);
            if (!@rename($indexDir, $backupPath)) {
                throw new RuntimeException('Unable to move current Xapian index to backup: '.$backupPath);
            }
        }

        if (!@mkdir($indexDir, 0775, true) && !is_dir($indexDir)) {
            if (null !== $backupPath) {
                @rename($backupPath, $indexDir);
            }

            throw new RuntimeException('Unable to create fresh Xapian index directory: '.$indexDir);
        }

        return $backupPath;
    }

    private function buildBackupPath(string $indexDir): string
    {
        $timestamp = (new DateTimeImmutable('now'))->format('Ymd-His');
        $basePath = $indexDir.'-backup-'.$timestamp;
        $backupPath = $basePath;
        $suffix = 1;

        while (file_exists($backupPath)) {
            $backupPath = $basePath.'-'.$suffix;
            ++$suffix;
        }

        return $backupPath;
    }

    private function restoreIndexDirectory(string $indexDir, ?string $backupPath): void
    {
        $indexDir = rtrim($indexDir, DIRECTORY_SEPARATOR);

        if (is_dir($indexDir)) {
            $this->removeDirectory($indexDir);
        }

        if (null !== $backupPath && is_dir($backupPath)) {
            @rename($backupPath, $indexDir);
        }
    }

    private function removeDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        $items = scandir($directory);
        if (false === $items) {
            throw new RuntimeException('Unable to read directory: '.$directory);
        }

        foreach ($items as $item) {
            if ('.' === $item || '..' === $item) {
                continue;
            }

            $path = $directory.DIRECTORY_SEPARATOR.$item;
            if (is_dir($path) && !is_link($path)) {
                $this->removeDirectory($path);
            } else {
                @unlink($path);
            }
        }

        @rmdir($directory);
    }

    private function runXapianCheck(SymfonyStyle $io, string $indexDir): void
    {
        $process = new Process(['xapian-check', $indexDir]);
        $process->setTimeout(null);

        try {
            $process->run();
        } catch (Throwable $exception) {
            $io->warning('xapian-check could not be executed: '.$exception->getMessage());

            return;
        }

        if ($process->isSuccessful()) {
            $io->success('xapian-check completed successfully.');

            return;
        }

        $io->warning('xapian-check finished with exit code '.$process->getExitCode().'. Review the output manually.');
        if ('' !== trim($process->getErrorOutput())) {
            $io->writeln(trim($process->getErrorOutput()));
        }
        if ('' !== trim($process->getOutput())) {
            $io->writeln(trim($process->getOutput()));
        }
    }
}
