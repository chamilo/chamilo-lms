<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Command;

use Chamilo\CoreBundle\Entity\GradebookCertificate;
use Chamilo\CoreBundle\Entity\PersonalFile;
use Chamilo\CoreBundle\Repository\GradebookCertificateRepository;
use Chamilo\CoreBundle\Repository\Node\PersonalFileRepository;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use RuntimeException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Throwable;

#[AsCommand(
    name: 'chamilo:migration:migrate-ricky-certificate-files',
    description: 'Copy Ricky certificate HTML files to prepared certificate resources in resumable batches.'
)]
final class MigrateRickyCertificateFilesCommand extends Command
{
    public function __construct(
        private readonly ManagerRegistry $registry,
        private readonly GradebookCertificateRepository $certificateRepository,
        private readonly PersonalFileRepository $personalFileRepository,
        private readonly KernelInterface $kernel,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('legacy-root', null, InputOption::VALUE_REQUIRED, 'Project root containing app/upload. Defaults to kernel.project_dir; use only as an explicit override.')
            ->addOption('batch-size', null, InputOption::VALUE_REQUIRED, 'Files flushed per batch.', '100')
            ->addOption('workers', null, InputOption::VALUE_REQUIRED, 'Total number of disjoint workers.', '1')
            ->addOption('worker-id', null, InputOption::VALUE_REQUIRED, 'Zero-based worker ID.', '0')
            ->addOption('limit', null, InputOption::VALUE_REQUIRED, 'Optional maximum certificates for this worker.', '0')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Resolve sources without creating ResourceFile rows.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $batchSize = max(1, min(500, (int) $input->getOption('batch-size')));
        $workers = max(1, min(32, (int) $input->getOption('workers')));
        $workerId = (int) $input->getOption('worker-id');
        $limit = max(0, (int) $input->getOption('limit'));
        $dryRun = (bool) $input->getOption('dry-run');

        if ($workerId < 0 || $workerId >= $workers) {
            throw new RuntimeException(sprintf('worker-id must be between 0 and %d.', $workers - 1));
        }

        $legacyRoot = trim((string) $input->getOption('legacy-root'));
        if ('' === $legacyRoot) {
            $legacyRoot = $this->kernel->getProjectDir();
        }

        $resolvedLegacyRoot = realpath($legacyRoot);
        if (false === $resolvedLegacyRoot) {
            throw new RuntimeException(sprintf('Project root does not exist: %s', $legacyRoot));
        }
        $legacyRoot = rtrim($resolvedLegacyRoot, '/');

        if (!is_dir($legacyRoot.'/app/upload/users')) {
            throw new RuntimeException(sprintf(
                'Legacy users directory was not found under %s/app/upload/users. Pass --legacy-root explicitly.',
                $legacyRoot
            ));
        }

        $manager = $this->registry->getManagerForClass(GradebookCertificate::class);
        if (!$manager instanceof EntityManagerInterface) {
            throw new RuntimeException('Doctrine ORM entity manager was not resolved.');
        }

        $connection = $manager->getConnection();
        $partitionSql = 1 === $workers ? '' : ' AND MOD(gc.id, :workers) = :workerId';
        $partitionParams = 1 === $workers ? [] : ['workers' => $workers, 'workerId' => $workerId];

        $total = (int) $connection->fetchOne(
            <<<'SQL'
SELECT COUNT(DISTINCT gc.id)
FROM gradebook_certificate gc
INNER JOIN resource_node rn ON rn.id = gc.resource_node_id
LEFT JOIN resource_file rf ON rf.resource_node_id = rn.id
WHERE gc.resource_node_id IS NOT NULL
  AND gc.path_certificate IS NOT NULL
  AND TRIM(gc.path_certificate) <> ''
  AND rf.id IS NULL
SQL.$partitionSql,
            $partitionParams
        );

        if (0 === $total) {
            $output->writeln(sprintf('<info>Worker %d/%d: no pending certificate files.</info>', $workerId, $workers));

            return Command::SUCCESS;
        }

        $output->writeln(sprintf(
            '<info>Worker %d/%d started: pending=%d batch_size=%d dry_run=%s legacy_root=%s</info>',
            $workerId,
            $workers,
            $total,
            $batchSize,
            $dryRun ? 'yes' : 'no',
            $legacyRoot
        ));

        $lastId = 0;
        $seen = 0;
        $migrated = 0;
        $alreadyDone = 0;
        $missingSource = 0;
        $ambiguousSource = 0;
        $errors = 0;
        $startedAt = microtime(true);

        while (true) {
            if ($limit > 0 && $seen >= $limit) {
                break;
            }

            $currentLimit = $batchSize;
            if ($limit > 0) {
                $currentLimit = min($currentLimit, $limit - $seen);
            }

            $idSql = <<<'SQL'
SELECT gc.id
FROM gradebook_certificate gc
INNER JOIN resource_node rn ON rn.id = gc.resource_node_id
LEFT JOIN resource_file rf ON rf.resource_node_id = rn.id
WHERE gc.id > :lastId
  AND gc.resource_node_id IS NOT NULL
  AND gc.path_certificate IS NOT NULL
  AND TRIM(gc.path_certificate) <> ''
  AND rf.id IS NULL
SQL.$partitionSql.' GROUP BY gc.id ORDER BY gc.id ASC LIMIT '.$currentLimit;

            $ids = $connection->fetchFirstColumn(
                $idSql,
                ['lastId' => $lastId] + $partitionParams
            );
            $ids = array_map('intval', $ids);

            if ([] === $ids) {
                break;
            }

            $lastId = max($ids);

            /** @var list<GradebookCertificate> $certificates */
            $certificates = $manager->createQueryBuilder()
                ->select('certificate', 'certificateUser', 'certificateNode', 'certificateFiles')
                ->from(GradebookCertificate::class, 'certificate')
                ->innerJoin('certificate.user', 'certificateUser')
                ->innerJoin('certificate.resourceNode', 'certificateNode')
                ->leftJoin('certificateNode.resourceFiles', 'certificateFiles')
                ->where('certificate.id IN (:ids)')
                ->setParameter('ids', $ids)
                ->orderBy('certificate.id', 'ASC')
                ->getQuery()
                ->getResult()
            ;

            $personalFiles = $this->loadExactPersonalFiles($manager, $certificates);
            $preparedInBatch = 0;

            foreach ($certificates as $certificate) {
                ++$seen;

                if ($certificate->getResourceNode()->hasResourceFile()) {
                    ++$alreadyDone;
                    continue;
                }

                $certificateId = (int) $certificate->getId();
                $userId = (int) $certificate->getUser()->getId();
                $logicalFileName = basename(ltrim(trim((string) $certificate->getPathCertificate()), '/'));

                if ('' === $logicalFileName || '.' === $logicalFileName) {
                    ++$missingSource;
                    $output->writeln(sprintf(
                        '<comment>Worker %d: certificate %d skipped: empty logical filename.</comment>',
                        $workerId,
                        $certificateId
                    ));
                    continue;
                }

                $source = $this->resolveSource(
                    $legacyRoot,
                    $userId,
                    $logicalFileName,
                    $personalFiles
                );

                if ('ambiguous' === $source['status']) {
                    ++$ambiguousSource;
                    $output->writeln(sprintf(
                        '<comment>Worker %d: certificate %d skipped: ambiguous exact PersonalFile source for user %d and %s.</comment>',
                        $workerId,
                        $certificateId,
                        $userId,
                        $logicalFileName
                    ));
                    continue;
                }

                if ('found' !== $source['status'] || !is_string($source['content']) || '' === $source['content']) {
                    ++$missingSource;
                    $output->writeln(sprintf(
                        '<comment>Worker %d: certificate %d skipped: source not found for user %d and %s.</comment>',
                        $workerId,
                        $certificateId,
                        $userId,
                        $logicalFileName
                    ));
                    continue;
                }

                if ($dryRun) {
                    ++$migrated;
                    continue;
                }

                try {
                    $resourceFile = $this->certificateRepository->addFileFromString(
                        $certificate,
                        $logicalFileName,
                        'text/html',
                        $source['content'],
                        false
                    );

                    if (null === $resourceFile) {
                        throw new RuntimeException('addFileFromString returned null.');
                    }

                    $manager->persist($certificate);
                    ++$preparedInBatch;
                    ++$migrated;
                } catch (Throwable $exception) {
                    ++$errors;
                    $output->writeln(sprintf(
                        '<error>Worker %d: certificate %d preparation failed: %s</error>',
                        $workerId,
                        $certificateId,
                        $exception->getMessage()
                    ));
                }
            }

            if (!$dryRun && $preparedInBatch > 0) {
                try {
                    $manager->flush();
                } catch (Throwable $exception) {
                    ++$errors;
                    $output->writeln(sprintf(
                        '<error>Worker %d: batch ending at certificate %d failed during flush: %s</error>',
                        $workerId,
                        $lastId,
                        $exception->getMessage()
                    ));
                    $manager->clear();

                    return Command::FAILURE;
                }
            }

            $manager->clear();

            $elapsed = max(1, (int) (microtime(true) - $startedAt));
            $rate = $seen / $elapsed;
            $remaining = max(0, $total - $seen);
            $output->writeln(sprintf(
                'Worker %d/%d progress: %d/%d (%.2f%%), migrated=%d already_done=%d missing_source=%d ambiguous_source=%d errors=%d rate=%.2f cert/s ETA=%ds last_id=%d.',
                $workerId,
                $workers,
                $seen,
                $total,
                100 * $seen / $total,
                $migrated,
                $alreadyDone,
                $missingSource,
                $ambiguousSource,
                $errors,
                $rate,
                $rate > 0 ? (int) round($remaining / $rate) : 0,
                $lastId
            ));
        }

        $output->writeln(sprintf(
            '<info>Worker %d/%d completed: seen=%d/%d migrated=%d already_done=%d missing_source=%d ambiguous_source=%d errors=%d elapsed=%ds.</info>',
            $workerId,
            $workers,
            $seen,
            $total,
            $migrated,
            $alreadyDone,
            $missingSource,
            $ambiguousSource,
            $errors,
            (int) (microtime(true) - $startedAt)
        ));

        return $errors > 0 ? Command::FAILURE : Command::SUCCESS;
    }

    /**
     * @param list<GradebookCertificate> $certificates
     *
     * @return array<string, list<PersonalFile>>
     */
    private function loadExactPersonalFiles(
        EntityManagerInterface $manager,
        array $certificates
    ): array {
        $userIds = [];
        $titles = [];

        foreach ($certificates as $certificate) {
            $userIds[] = (int) $certificate->getUser()->getId();
            $title = basename(ltrim(trim((string) $certificate->getPathCertificate()), '/'));
            if ('' !== $title) {
                $titles[] = $title;
            }
        }

        $userIds = array_values(array_unique($userIds));
        $titles = array_values(array_unique($titles));

        if ([] === $userIds || [] === $titles) {
            return [];
        }

        /** @var list<PersonalFile> $rows */
        $rows = $manager->createQueryBuilder()
            ->select('personalFile', 'personalNode', 'personalCreator', 'personalResourceFiles')
            ->from(PersonalFile::class, 'personalFile')
            ->innerJoin('personalFile.resourceNode', 'personalNode')
            ->innerJoin('personalNode.creator', 'personalCreator')
            ->leftJoin('personalNode.resourceFiles', 'personalResourceFiles')
            ->where('personalCreator.id IN (:userIds)')
            ->andWhere('personalNode.title IN (:titles)')
            ->setParameter('userIds', $userIds)
            ->setParameter('titles', $titles)
            ->getQuery()
            ->getResult()
        ;

        $map = [];
        foreach ($rows as $personalFile) {
            $node = $personalFile->getResourceNode();
            $creator = $node->getCreator();
            if (null === $creator) {
                continue;
            }
            $map[$this->sourceKey((int) $creator->getId(), $node->getTitle())][] = $personalFile;
        }

        return $map;
    }

    /**
     * @param array<string, list<PersonalFile>> $personalFiles
     *
     * @return array{status: string, content: ?string}
     */
    private function resolveSource(
        string $legacyRoot,
        int $userId,
        string $logicalFileName,
        array $personalFiles
    ): array {
        $legacyPath = sprintf(
            '%s/app/upload/users/%s/%d/certificate/%s',
            $legacyRoot,
            substr((string) $userId, 0, 1),
            $userId,
            $logicalFileName
        );

        if (is_file($legacyPath) && is_readable($legacyPath)) {
            $content = file_get_contents($legacyPath);
            if (is_string($content) && '' !== $content) {
                return ['status' => 'found', 'content' => $content];
            }
        }

        $candidates = $personalFiles[$this->sourceKey($userId, $logicalFileName)] ?? [];
        if (count($candidates) > 1) {
            return ['status' => 'ambiguous', 'content' => null];
        }
        if (1 === count($candidates)) {
            try {
                $content = $this->personalFileRepository->getResourceFileContent($candidates[0]);
                if (is_string($content) && '' !== $content) {
                    return ['status' => 'found', 'content' => $content];
                }
            } catch (Throwable) {
                return ['status' => 'missing', 'content' => null];
            }
        }

        return ['status' => 'missing', 'content' => null];
    }

    private function sourceKey(int $userId, string $title): string
    {
        return $userId.':'.$title;
    }
}
