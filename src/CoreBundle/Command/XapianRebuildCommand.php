<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Command;

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
use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Process;
use Throwable;
use XapianWritableDatabase;

#[AsCommand(
    name: 'chamilo:search:xapian:rebuild',
    description: 'Rebuild the Xapian search index from existing course resources.',
)]
final class XapianRebuildCommand extends Command
{
    private const DEFAULT_BATCH_SIZE = 50;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly SettingsManager $settingsManager,
        private readonly SearchIndexPathResolver $indexPathResolver,
        private readonly DocumentXapianIndexer $documentIndexer,
        private readonly CourseDescriptionXapianIndexer $courseDescriptionIndexer,
        private readonly QuizXapianIndexer $quizIndexer,
        private readonly QuestionXapianIndexer $questionIndexer,
        private readonly LpXapianIndexer $lpIndexer,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Show what would be rebuilt without changing the index or the database.')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Actually rebuild the Xapian index. Without this option the command is read-only.')
            ->addOption('batch-size', null, InputOption::VALUE_REQUIRED, 'Number of entities processed before clearing Doctrine memory.', self::DEFAULT_BATCH_SIZE)
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
  - search_engine_ref is rebuilt inside a database transaction;
  - search_engine_field_value is preserved;
  - legacy c_lp_item.search_did values are cleared because they point to the old physical index.
HELP
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $force = (bool) $input->getOption('force');
        $explicitDryRun = (bool) $input->getOption('dry-run');
        $batchSize = max(1, (int) $input->getOption('batch-size'));

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
            ['Resource type', 'Indexed', 'Failed/skipped'],
            [
                ['Documents', $stats['documents']['indexed'], $stats['documents']['failed']],
                ['Course descriptions', $stats['course_descriptions']['indexed'], $stats['course_descriptions']['failed']],
                ['Quizzes', $stats['quizzes']['indexed'], $stats['quizzes']['failed']],
                ['Questions', $stats['questions']['indexed'], $stats['questions']['failed']],
                ['Learning paths', $stats['learning_paths']['indexed'], $stats['learning_paths']['failed']],
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
        $qb = $this->entityManager->createQueryBuilder()
            ->select('COUNT(entity.iid)')
            ->from($className, 'entity')
            ->where('entity.resourceNode IS NOT NULL')
        ;

        if ($excludeDocumentFolders) {
            $qb
                ->andWhere('entity.filetype <> :folder')
                ->setParameter('folder', 'folder')
            ;
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @return array<string,mixed>
     */
    private function rebuildDatabaseAndIndex(SymfonyStyle $io, int $batchSize): array
    {
        $connection = $this->entityManager->getConnection();

        $connection->beginTransaction();

        try {
            $connection->executeStatement('DELETE FROM search_engine_ref');
            $legacyLpItemRefsCleared = $connection->executeStatement(
                'UPDATE c_lp_item SET search_did = NULL WHERE search_did IS NOT NULL'
            );

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
                'legacy_lp_item_refs_cleared' => $legacyLpItemRefsCleared,
            ];

            $failed = 0;
            foreach (['documents', 'course_descriptions', 'quizzes', 'questions', 'learning_paths'] as $key) {
                $failed += (int) $stats[$key]['failed'];
            }

            if ($failed > 0) {
                throw new RuntimeException(\sprintf('Xapian rebuild stopped: %d expected resources could not be indexed.', $failed));
            }

            $newRefs = (int) $connection->fetchOne('SELECT COUNT(*) FROM search_engine_ref');
            $indexed = 0;
            foreach (['documents', 'course_descriptions', 'quizzes', 'questions', 'learning_paths'] as $key) {
                $indexed += (int) $stats[$key]['indexed'];
            }

            if ($newRefs !== $indexed) {
                throw new RuntimeException(
                    \sprintf(
                        'Xapian rebuild stopped: indexed resources (%d) do not match search_engine_ref rows (%d).',
                        $indexed,
                        $newRefs
                    )
                );
            }

            $connection->commit();

            $stats['new_refs'] = $newRefs;

            return $stats;
        } catch (Throwable $exception) {
            if ($connection->isTransactionActive()) {
                $connection->rollBack();
            }

            throw $exception;
        }
    }

    /**
     * @param class-string $className
     *
     * @return array{indexed:int,failed:int}
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

        $qb = $this->entityManager->createQueryBuilder()
            ->select('entity')
            ->from($className, 'entity')
            ->where('entity.resourceNode IS NOT NULL')
            ->orderBy('entity.iid', 'ASC')
        ;

        if ($excludeDocumentFolders) {
            $qb
                ->andWhere('entity.filetype <> :folder')
                ->setParameter('folder', 'folder')
            ;
        }

        $indexed = 0;
        $failed = 0;
        $processed = 0;

        foreach ($qb->getQuery()->toIterable() as $entity) {
            try {
                $docId = $indexCallback($entity);
                if (\is_int($docId) && $docId > 0) {
                    ++$indexed;
                } else {
                    ++$failed;
                }
            } catch (Throwable $exception) {
                ++$failed;
                error_log('[Xapian] Rebuild failed for '.$label.': '.$exception->getMessage());
            }

            ++$processed;

            if (0 === $processed % $batchSize) {
                $this->entityManager->clear();
                gc_collect_cycles();
                $io->text(\sprintf('  %s processed: %d', $label, $processed));
            }
        }

        $this->entityManager->clear();
        gc_collect_cycles();

        $io->text(\sprintf('  %s indexed: %d, failed/skipped: %d', $label, $indexed, $failed));

        return [
            'indexed' => $indexed,
            'failed' => $failed,
        ];
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

        $parentDir = dirname($indexDir);
        if ('' === $parentDir || DIRECTORY_SEPARATOR === $parentDir || $parentDir === $indexDir) {
            throw new RuntimeException('Unsafe Xapian index parent directory configuration.');
        }
    }

    private function prepareFreshIndexDirectory(string $indexDir): ?string
    {
        $indexDir = rtrim($indexDir, DIRECTORY_SEPARATOR);
        $parentDir = dirname($indexDir);

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
