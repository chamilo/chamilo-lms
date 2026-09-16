<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Command;

use Chamilo\CoreBundle\Entity\GradebookCertificate;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use RuntimeException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

use const DIRECTORY_SEPARATOR;

#[AsCommand(
    name: 'chamilo:migration:repair-legacy-certificate-history',
    description: 'Preserve legacy certificate references and archive certificates missing after an upgrade.'
)]
final class RepairLegacyCertificateHistoryCommand extends Command
{
    public function __construct(
        private readonly ManagerRegistry $registry,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                'source-database',
                null,
                InputOption::VALUE_REQUIRED,
                'Legacy Chamilo database containing gradebook_certificate.'
            )
            ->addOption(
                'legacy-root',
                null,
                InputOption::VALUE_REQUIRED,
                'Legacy Chamilo root containing app/upload/users.'
            )
            ->addOption(
                'dry-run',
                null,
                InputOption::VALUE_NONE,
                'Validate the repair without writing anything.'
            )
        ;
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ): int {
        $sourceDatabase = trim((string) $input->getOption('source-database'));
        $legacyRoot = trim((string) $input->getOption('legacy-root'));
        $dryRun = (bool) $input->getOption('dry-run');

        if (
            '' === $sourceDatabase
            || 1 !== preg_match('/^[A-Za-z0-9_]+$/', $sourceDatabase)
        ) {
            throw new RuntimeException(
                'A valid --source-database value is required.'
            );
        }

        if ('' === $legacyRoot) {
            throw new RuntimeException(
                'A --legacy-root value is required.'
            );
        }

        $resolvedLegacyRoot = realpath($legacyRoot);
        if (false === $resolvedLegacyRoot) {
            throw new RuntimeException(
                sprintf('Legacy root does not exist: %s', $legacyRoot)
            );
        }

        $usersRoot = realpath(
            rtrim($resolvedLegacyRoot, '/').'/app/upload/users'
        );

        if (false === $usersRoot) {
            throw new RuntimeException(
                sprintf(
                    'Legacy users directory was not found under %s/app/upload/users.',
                    $resolvedLegacyRoot
                )
            );
        }

        $manager = $this->registry->getManagerForClass(
            GradebookCertificate::class
        );

        if (!$manager instanceof EntityManagerInterface) {
            throw new RuntimeException(
                'Doctrine ORM entity manager was not resolved.'
            );
        }

        $connection = $manager->getConnection();

        $schemaManager = $connection->createSchemaManager();
        if (
            !$schemaManager->tablesExist(
                ['gradebook_certificate_legacy_reference']
            )
        ) {
            throw new RuntimeException(
                'gradebook_certificate_legacy_reference does not exist. Run the forward migration first.'
            );
        }

        $sourceTable = sprintf(
            '`%s`.gradebook_certificate',
            $sourceDatabase
        );

        try {
            $sourceCount = (int) $connection->fetchOne(
                'SELECT COUNT(*) FROM '.$sourceTable
            );
        } catch (Throwable $exception) {
            throw new RuntimeException(
                sprintf(
                    'Unable to read %s: %s',
                    $sourceTable,
                    $exception->getMessage()
                ),
                0,
                $exception
            );
        }

        $currentCount = (int) $connection->fetchOne(
            'SELECT COUNT(*) FROM gradebook_certificate'
        );

        $missingRows = $connection->fetchAllAssociative(
            'SELECT
                source.id,
                source.cat_id,
                source.user_id,
                source.score_certificate,
                source.created_at,
                source.path_certificate
             FROM '.$sourceTable.' source
             LEFT JOIN gradebook_certificate current_certificate
                ON current_certificate.id = source.id
             WHERE current_certificate.id IS NULL
             ORDER BY source.id ASC'
        );

        $preparedFiles = [];
        $missingFiles = [];

        foreach ($missingRows as $row) {
            $certificateId = (int) $row['id'];
            $legacyUserId = (int) $row['user_id'];

            $logicalFileName = basename(
                ltrim(
                    trim((string) $row['path_certificate']),
                    '/'
                )
            );

            if (
                $certificateId <= 0
                || $legacyUserId <= 0
                || '' === $logicalFileName
            ) {
                $missingFiles[] = $certificateId;

                continue;
            }

            $candidate = sprintf(
                '%s/%s/%d/certificate/%s',
                $usersRoot,
                substr((string) $legacyUserId, 0, 1),
                $legacyUserId,
                $logicalFileName
            );

            $resolvedFile = realpath($candidate);

            if (
                false === $resolvedFile
                || !str_starts_with(
                    $resolvedFile,
                    $usersRoot.DIRECTORY_SEPARATOR
                )
                || !is_file($resolvedFile)
                || !is_readable($resolvedFile)
            ) {
                $missingFiles[] = $certificateId;

                continue;
            }

            $content = file_get_contents($resolvedFile);

            if (!is_string($content) || '' === $content) {
                $missingFiles[] = $certificateId;

                continue;
            }

            $preparedFiles[$certificateId] = [
                'content' => $content,
                'sha256' => hash('sha256', $content),
            ];
        }

        $output->writeln(
            sprintf(
                'Legacy certificate audit: source=%d current=%d missing=%d readable_missing_files=%d unreadable_missing_files=%d dry_run=%s',
                $sourceCount,
                $currentCount,
                count($missingRows),
                count($preparedFiles),
                count($missingFiles),
                $dryRun ? 'yes' : 'no'
            )
        );

        if ([] !== $missingFiles) {
            $output->writeln(
                '<error>Missing historical certificate files detected. No repair was written.</error>'
            );

            $output->writeln(
                'Certificate IDs: '.implode(', ', $missingFiles)
            );

            return Command::FAILURE;
        }

        if ($dryRun) {
            return Command::SUCCESS;
        }

        $connection->beginTransaction();

        try {
            $connection->executeStatement(
                'INSERT INTO gradebook_certificate_legacy_reference (
                    certificate_id,
                    legacy_user_id,
                    cat_id,
                    score_certificate,
                    created_at,
                    path_certificate,
                    imported_at
                 )
                 SELECT
                    id,
                    user_id,
                    cat_id,
                    score_certificate,
                    created_at,
                    path_certificate,
                    UTC_TIMESTAMP()
                 FROM '.$sourceTable.'
                 ON DUPLICATE KEY UPDATE
                    legacy_user_id = VALUES(legacy_user_id),
                    cat_id = VALUES(cat_id),
                    score_certificate = VALUES(score_certificate),
                    created_at = VALUES(created_at),
                    path_certificate = VALUES(path_certificate)'
            );

            foreach ($preparedFiles as $certificateId => $file) {
                $connection->update(
                    'gradebook_certificate_legacy_reference',
                    [
                        'html_content' => $file['content'],
                        'content_sha256' => $file['sha256'],
                    ],
                    [
                        'certificate_id' => $certificateId,
                    ]
                );
            }

            $referenceCount = (int) $connection->fetchOne(
                'SELECT COUNT(*)
                 FROM gradebook_certificate_legacy_reference'
            );

            $archivedMissingCount = (int) $connection->fetchOne(
                'SELECT COUNT(*)
                 FROM gradebook_certificate_legacy_reference legacy
                 LEFT JOIN gradebook_certificate current_certificate
                    ON current_certificate.id = legacy.certificate_id
                 WHERE current_certificate.id IS NULL
                   AND legacy.html_content IS NOT NULL
                   AND TRIM(legacy.html_content) <> \'\''
            );

            if (
                $referenceCount !== $sourceCount
                || $archivedMissingCount !== count($missingRows)
            ) {
                throw new RuntimeException(
                    sprintf(
                        'Repair verification failed: references=%d/%d archived_missing=%d/%d.',
                        $referenceCount,
                        $sourceCount,
                        $archivedMissingCount,
                        count($missingRows)
                    )
                );
            }

            $connection->commit();

            $output->writeln(
                sprintf(
                    '<info>Legacy certificate history repaired: references=%d archived_missing=%d.</info>',
                    $referenceCount,
                    $archivedMissingCount
                )
            );

            return Command::SUCCESS;
        } catch (Throwable $exception) {
            if ($connection->isTransactionActive()) {
                $connection->rollBack();
            }

            throw new RuntimeException(
                'Legacy certificate repair failed: '.$exception->getMessage(),
                0,
                $exception
            );
        }
    }
}
