<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Command;

use Chamilo\CoreBundle\Command\Concerns\BoundedConsoleOptionsTrait;
use Chamilo\CoreBundle\Component\Gradebook\AchievementCertificateBatchService;
use Chamilo\CoreBundle\Component\Gradebook\CourseCompletionRuleEvaluator;
use Chamilo\CoreBundle\Entity\GradebookCategory;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Throwable;

use const PHP_INT_MAX;

#[AsCommand(
    name: 'chamilo:migration:process-achievement-certificates',
    description: 'Generate missing achievement certificates safely for one configured course.'
)]
final class ProcessAchievementCertificatesCommand extends Command
{
    use BoundedConsoleOptionsTrait;

    private readonly AchievementCertificateBatchService $service;

    public function __construct(
        private readonly Connection $connection,
        private readonly EntityManagerInterface $entityManager,
        private readonly KernelInterface $kernel,
        TokenStorageInterface $tokenStorage,
    ) {
        // CoreBundle Component classes are excluded from service auto-discovery.
        $this->service = new AchievementCertificateBatchService(
            $connection,
            $entityManager,
            new CourseCompletionRuleEvaluator($connection),
            $tokenStorage
        );

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                'course-id',
                null,
                InputOption::VALUE_REQUIRED,
                'Course ID to process.'
            )
            ->addOption(
                'category-id',
                null,
                InputOption::VALUE_REQUIRED,
                'Optional root gradebook category ID. Required when the course has multiple eligible roots.'
            )
            ->addOption(
                'completion-mode',
                null,
                InputOption::VALUE_REQUIRED,
                'Completion source: course-rule or gradebook.',
                'course-rule'
            )
            ->addOption(
                'user-id',
                null,
                InputOption::VALUE_REQUIRED,
                'Optional single active subscribed student ID for a controlled test.'
            )
            ->addOption(
                'sender-id',
                null,
                InputOption::VALUE_REQUIRED,
                'Active platform administrator used as the internal-message sender.'
            )
            ->addOption(
                'limit',
                null,
                InputOption::VALUE_REQUIRED,
                'Maximum certificates to generate or report. Use 0 for no generation limit.',
                '100'
            )
            ->addOption(
                'scan-limit',
                null,
                InputOption::VALUE_REQUIRED,
                'Maximum pending users to evaluate. Use 0 for no scan limit.',
                '1000'
            )
            ->addOption(
                'batch-size',
                null,
                InputOption::VALUE_REQUIRED,
                'Pending users loaded per database batch.',
                '25'
            )
            ->addOption(
                'after-user-id',
                null,
                InputOption::VALUE_REQUIRED,
                'Start after this user ID. Useful for controlled incremental scans.',
                '0'
            )
            ->addOption(
                'send-notification',
                null,
                InputOption::VALUE_NONE,
                'Send the configured internal message and native e-mail notification after certificate creation.'
            )
            ->addOption(
                'dry-run',
                null,
                InputOption::VALUE_NONE,
                'Evaluate candidates without creating certificates or sending notifications.'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Process achievement certificates');

        try {
            $this->service->bootstrapLegacy($this->kernel);

            $courseId = $this->positiveOption($input, 'course-id');
            if (null === $courseId) {
                $io->error('A positive --course-id is required.');

                return Command::INVALID;
            }

            $categoryId = $this->optionalPositiveOption($input, 'category-id');
            $completionMode = strtolower(trim((string) $input->getOption('completion-mode')));
            $userId = $this->optionalPositiveOption($input, 'user-id');
            $senderId = $this->optionalPositiveOption($input, 'sender-id');
            $limit = $this->boundedNonNegativeOption($input, 'limit', 10000);
            $scanLimit = $this->boundedNonNegativeOption($input, 'scan-limit', 100000);
            $batchSize = $this->boundedPositiveOption($input, 'batch-size', 100);
            $afterUserId = $this->boundedNonNegativeOption($input, 'after-user-id', PHP_INT_MAX);
            $dryRun = (bool) $input->getOption('dry-run');
            $sendNotification = (bool) $input->getOption('send-notification');

            if (!\in_array($completionMode, ['course-rule', 'gradebook'], true)) {
                $io->error('--completion-mode must be course-rule or gradebook.');

                return Command::INVALID;
            }

            if ('gradebook' === $completionMode && null === $categoryId) {
                $io->error(
                    'Explicit --category-id is required with --completion-mode=gradebook.'
                );

                return Command::INVALID;
            }

            if (!$dryRun && !$sendNotification) {
                $io->error(
                    'Actual execution requires --send-notification. '
                    .'Use --dry-run for a read-only evaluation.'
                );

                return Command::INVALID;
            }

            if ($dryRun && $sendNotification) {
                $io->note('--send-notification is ignored during --dry-run.');
                $sendNotification = false;
            }

            if ($sendNotification) {
                if (null === $senderId) {
                    $io->error('--sender-id is required with --send-notification.');

                    return Command::INVALID;
                }

                $this->service->assertValidSender($senderId);
            }

            $course = $this->connection->fetchAssociative(
                'SELECT id, code, title FROM course WHERE id = :courseId LIMIT 1',
                ['courseId' => $courseId]
            );
            if (false === $course) {
                throw new RuntimeException(\sprintf('Course %d was not found.', $courseId));
            }

            if (
                'course-rule' === $completionMode
                && !$this->service->supports($courseId)
            ) {
                throw new RuntimeException(\sprintf('Course %d has no persisted completion rule. Use --completion-mode=gradebook only for a course whose legacy automatic process was verified to use the native gradebook result.', $courseId));
            }

            // Course-specific subject/message are optional: Certificate::generate() already
            // falls back to a translated default when either is empty, exactly like the
            // manual "Generate" button (LegacyGradebookCertificateBridge) does. Requiring
            // this course extra field to exist would block generation for every course that
            // never configured it — which is the common case, not an error.
            $notification = $this->service->resolveNotification($courseId) + ['subject' => '', 'message' => ''];

            $category = $this->resolveCategory($courseId, $categoryId);
            $resolvedCategoryId = (int) $category['id'];
            $minimumScore = (float) $category['certif_min_score'];

            /** @var GradebookCategory|null $categoryEntity */
            $categoryEntity = $this->entityManager->find(
                GradebookCategory::class,
                $resolvedCategoryId
            );
            if (!$categoryEntity instanceof GradebookCategory) {
                throw new RuntimeException(\sprintf('Gradebook category %d could not be loaded.', $resolvedCategoryId));
            }

            $notification['sender_id'] = $senderId ?? 0;

            $io->definitionList(
                ['Course' => \sprintf('%s — %s', $course['code'], $course['title'])],
                ['Course ID' => $courseId],
                ['Category ID' => $resolvedCategoryId],
                ['Category title' => (string) $category['title']],
                ['Minimum score' => $this->service->formatNumber($minimumScore)],
                ['Completion source' => 'course-rule' === $completionMode
                    ? 'persisted course completion rule'
                    : 'native gradebook (explicit)'],
                ['Mode' => $dryRun ? 'dry-run' : 'generate and notify'],
                ['Sender ID' => $sendNotification ? (string) $senderId : 'not used'],
                ['Generation limit' => 0 === $limit ? 'unlimited' : (string) $limit],
                ['Scan limit' => 0 === $scanLimit ? 'unlimited' : (string) $scanLimit],
                ['After user ID' => (string) $afterUserId]
            );

            $summary = [
                'scanned' => 0,
                'incomplete' => 0,
                'would_generate' => 0,
                'generated' => 0,
                'failed' => 0,
                'last_user_id' => $afterUserId,
            ];
            $previewRows = [];

            $lastUserId = $afterUserId;

            while (true) {
                if ($this->limitReached($summary, $limit, $scanLimit, $dryRun)) {
                    break;
                }

                $remainingScan = 0 === $scanLimit
                    ? $batchSize
                    : max(0, $scanLimit - $summary['scanned']);
                if (0 === $remainingScan) {
                    break;
                }

                $currentBatchSize = min($batchSize, $remainingScan);
                $candidateIds = $this->service->loadPendingUserIds(
                    $courseId,
                    $resolvedCategoryId,
                    0,
                    $lastUserId,
                    $currentBatchSize,
                    $userId
                );

                if ([] === $candidateIds) {
                    break;
                }

                foreach ($candidateIds as $candidateUserId) {
                    $lastUserId = $candidateUserId;
                    $summary['last_user_id'] = $candidateUserId;
                    ++$summary['scanned'];

                    $evaluation = $this->service->evaluateCandidate(
                        $completionMode,
                        $candidateUserId,
                        $courseId,
                        (string) $course['code'],
                        $minimumScore,
                        0,
                        $categoryEntity
                    );

                    $isComplete = !empty($evaluation['complete'])
                        && null !== $evaluation['score']
                        && (float) $evaluation['score'] >= $minimumScore;

                    if (!$isComplete) {
                        ++$summary['incomplete'];

                        if (null !== $userId) {
                            $previewRows[] = [
                                $candidateUserId,
                                'incomplete',
                                null === $evaluation['score']
                                    ? '-'
                                    : $this->service->formatNumber((float) $evaluation['score']),
                            ];
                        }

                        continue;
                    }

                    if ($dryRun) {
                        ++$summary['would_generate'];
                        $previewRows[] = [
                            $candidateUserId,
                            'would generate',
                            $this->service->formatNumber((float) $evaluation['score']),
                        ];
                    } else {
                        $created = $this->service->generateCertificate(
                            $categoryEntity,
                            $candidateUserId,
                            $sendNotification,
                            $notification
                        );

                        if ($created) {
                            ++$summary['generated'];
                            $previewRows[] = [
                                $candidateUserId,
                                'generated',
                                $this->service->formatNumber((float) $evaluation['score']),
                            ];
                        } else {
                            ++$summary['failed'];
                            $previewRows[] = [
                                $candidateUserId,
                                'failed',
                                $this->service->formatNumber((float) $evaluation['score']),
                            ];
                        }
                    }

                    if ($this->generationLimitReached($summary, $limit, $dryRun)) {
                        break 2;
                    }
                }

                if (null !== $userId) {
                    break;
                }
            }

            if ([] !== $previewRows) {
                $io->table(
                    ['User ID', 'Result', 'Calculated score'],
                    \array_slice($previewRows, 0, 100)
                );
            }

            $io->definitionList(
                ['Scanned pending users' => $summary['scanned']],
                ['Incomplete/not eligible' => $summary['incomplete']],
                ['Would generate' => $summary['would_generate']],
                ['Generated' => $summary['generated']],
                ['Failed' => $summary['failed']],
                ['Last scanned user ID' => $summary['last_user_id']]
            );

            if ($summary['failed'] > 0) {
                $io->error(
                    'One or more certificate generations failed. '
                    .'Review var/log/dev.log before re-running.'
                );

                return Command::FAILURE;
            }

            if ($dryRun) {
                $io->success('Read-only certificate evaluation completed.');
            } else {
                $io->success('Missing certificates were processed safely.');
            }

            if (
                null === $userId
                && 0 !== $scanLimit
                && $summary['scanned'] >= $scanLimit
                && !$this->generationLimitReached($summary, $limit, $dryRun)
            ) {
                $io->note(\sprintf(
                    'The scan limit was reached. Continue with --after-user-id=%d.',
                    $summary['last_user_id']
                ));
            }

            return Command::SUCCESS;
        } catch (Throwable $exception) {
            $io->error($exception->getMessage());

            return Command::FAILURE;
        }
    }

    /**
     * @return array{id: int|string, title: string, certif_min_score: float|string}
     */
    private function resolveCategory(int $courseId, ?int $requestedCategoryId): array
    {
        $categories = $this->service->loadEligibleCategories($courseId, 0, $requestedCategoryId);

        if ([] === $categories) {
            throw new RuntimeException(\sprintf('No eligible root certificate category was found for course %d.', $courseId));
        }

        if (\count($categories) > 1 && null === $requestedCategoryId) {
            $ids = array_map(
                static fn (array $category): string => (string) $category['id'],
                $categories
            );

            throw new RuntimeException(\sprintf('Course %d has multiple eligible root categories (%s). Re-run with --category-id.', $courseId, implode(', ', $ids)));
        }

        return $categories[0];
    }

    /**
     * @param array<string, int> $summary
     */
    private function generationLimitReached(array $summary, int $limit, bool $dryRun): bool
    {
        return $this->service->generationLimitReached($summary, $limit, $dryRun);
    }

    /**
     * @param array<string, int> $summary
     */
    private function limitReached(array $summary, int $limit, int $scanLimit, bool $dryRun): bool
    {
        if ($this->generationLimitReached($summary, $limit, $dryRun)) {
            return true;
        }

        return 0 !== $scanLimit && $summary['scanned'] >= $scanLimit;
    }
}
