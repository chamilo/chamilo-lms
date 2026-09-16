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

/**
 * Platform-wide replacement for the legacy cron script
 * public/main/cron/add_gradebook_certificates.php.
 *
 * That script loads every (gradebook category, user) pair with a result in a
 * single unbounded query, never clears the Doctrine identity map between
 * iterations, never filters out non-root/non-certificate categories, and
 * never sends the achievement notification. On a platform with a non-trivial
 * amount of gradebook results, this reproduces the same out-of-memory failure
 * already seen on a real migration run (see Version20240112191200): loading
 * an unbounded result set and never releasing entities during the loop.
 *
 * This command reuses the exact same safe, batched, dry-run-capable building
 * blocks as ProcessAchievementCertificatesCommand (chamilo:migration:process-
 * achievement-certificates) — both share AchievementCertificateBatchService —
 * but loops over every eligible course, and within each course over every
 * eligible context (the base course itself, plus one per session it hosts),
 * instead of requiring one --course-id per run. This lets it be scheduled as
 * a single recurring cron entry.
 *
 * A course, or one of its session contexts, is only skipped for structural
 * reasons (no configured completion rule, or an ambiguous root category for
 * that context) — never aborts the whole run, so one misconfigured course
 * cannot block certificate generation for the
 * rest of the platform.
 */
#[AsCommand(
    name: 'chamilo:gradebook:process-achievement-certificates',
    description: 'Generate missing achievement certificates safely across every eligible course and session on the platform. Intended to run as a recurring cron entry, replacing the legacy public/main/cron/add_gradebook_certificates.php script.'
)]
final class ProcessAllAchievementCertificatesCommand extends Command
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
                'completion-mode',
                null,
                InputOption::VALUE_REQUIRED,
                'Completion source: course-rule or gradebook. In gradebook mode, a context (the base course, or one of its sessions) is skipped when it has more than one eligible root category (re-run it individually with chamilo:migration:process-achievement-certificates --category-id).',
                'course-rule'
            )
            ->addOption(
                'user-id',
                null,
                InputOption::VALUE_REQUIRED,
                'Optional single active subscribed student ID, checked across every eligible course and session, for a controlled test.'
            )
            ->addOption(
                'sender-id',
                null,
                InputOption::VALUE_REQUIRED,
                'Active platform administrator. Required for any real (non-dry-run) execution: stamped as the resource creator on every certificate generated, and also used as the internal-message sender when --send-notification is set.'
            )
            ->addOption(
                'limit',
                null,
                InputOption::VALUE_REQUIRED,
                'Maximum certificates to generate or report across the whole run. Use 0 for no generation limit.',
                '100'
            )
            ->addOption(
                'scan-limit',
                null,
                InputOption::VALUE_REQUIRED,
                'Maximum pending users to evaluate across the whole run. Use 0 for no scan limit.',
                '1000'
            )
            ->addOption(
                'batch-size',
                null,
                InputOption::VALUE_REQUIRED,
                'Pending users loaded per database batch, within one course/session context.',
                '25'
            )
            ->addOption(
                'course-batch-size',
                null,
                InputOption::VALUE_REQUIRED,
                'Eligible courses loaded per database batch.',
                '50'
            )
            ->addOption(
                'after-course-id',
                null,
                InputOption::VALUE_REQUIRED,
                'Resume scanning after this course ID. Safe to reuse after an interrupted run: a course never regenerates a certificate for an already-certified user.',
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
        $io->title('Process achievement certificates (platform-wide)');

        try {
            $this->service->bootstrapLegacy($this->kernel);

            $completionMode = strtolower(trim((string) $input->getOption('completion-mode')));
            if (!\in_array($completionMode, ['course-rule', 'gradebook'], true)) {
                $io->error('--completion-mode must be course-rule or gradebook.');

                return Command::INVALID;
            }

            $userId = $this->optionalPositiveOption($input, 'user-id');
            $senderId = $this->optionalPositiveOption($input, 'sender-id');
            $limit = $this->boundedNonNegativeOption($input, 'limit', 100000);
            $scanLimit = $this->boundedNonNegativeOption($input, 'scan-limit', 1000000);
            $batchSize = $this->boundedPositiveOption($input, 'batch-size', 500);
            $courseBatchSize = $this->boundedPositiveOption($input, 'course-batch-size', 500);
            $afterCourseId = $this->boundedNonNegativeOption($input, 'after-course-id', PHP_INT_MAX);
            $dryRun = (bool) $input->getOption('dry-run');
            $sendNotification = (bool) $input->getOption('send-notification');

            if ($dryRun && $sendNotification) {
                $io->note('--send-notification is ignored during --dry-run.');
                $sendNotification = false;
            }

            if (!$dryRun) {
                // --sender-id is required for any real execution, not just --send-notification:
                // it is also the resource creator stamped on every certificate this run
                // generates (console commands run with no Security token to fall back on —
                // see AchievementCertificateBatchService::assertValidSender()).
                if (null === $senderId) {
                    $io->error(
                        '--sender-id is required for a real (non-dry-run) execution — '
                        .'used as the resource creator, and as the notification sender when --send-notification is set. '
                        .'Use --dry-run for a read-only evaluation.'
                    );

                    return Command::INVALID;
                }

                $this->service->assertValidSender($senderId);
            }

            $io->definitionList(
                ['Completion source' => 'course-rule' === $completionMode
                    ? 'persisted course completion rule'
                    : 'native gradebook (contexts with exactly one eligible root category)'],
                ['Mode' => $dryRun ? 'dry-run' : ($sendNotification ? 'generate and notify' : 'generate only, no notification')],
                ['Sender ID (resource creator)' => $dryRun ? 'not used' : (string) $senderId],
                ['Generation limit' => 0 === $limit ? 'unlimited' : (string) $limit],
                ['Scan limit' => 0 === $scanLimit ? 'unlimited' : (string) $scanLimit],
                ['After course ID' => (string) $afterCourseId]
            );

            $summary = [
                'courses_scanned' => 0,
                'courses_skipped_no_rule' => 0,
                'sessions_skipped_ambiguous_category' => 0,
                'contexts_processed' => 0,
                'scanned' => 0,
                'incomplete' => 0,
                'would_generate' => 0,
                'generated' => 0,
                'failed' => 0,
                'last_course_id' => $afterCourseId,
            ];
            $previewRows = [];
            $lastCourseId = $afterCourseId;

            while (true) {
                if ($this->runLimitsReached($summary, $limit, $scanLimit, $dryRun)) {
                    break;
                }

                $courseIds = $this->loadEligibleCourseIds($lastCourseId, $courseBatchSize);
                if ([] === $courseIds) {
                    break;
                }

                foreach ($courseIds as $courseId) {
                    $lastCourseId = $courseId;
                    $summary['last_course_id'] = $courseId;

                    if ($this->runLimitsReached($summary, $limit, $scanLimit, $dryRun)) {
                        break 2;
                    }

                    ++$summary['courses_scanned'];

                    $this->processCourse(
                        $courseId,
                        $completionMode,
                        $userId,
                        $senderId,
                        $limit,
                        $scanLimit,
                        $batchSize,
                        $dryRun,
                        $sendNotification,
                        $io,
                        $summary,
                        $previewRows
                    );

                    // Release every entity hydrated for this course before moving to
                    // the next one — the unbounded identity-map growth that caused
                    // the legacy cron script's OOM-prone equivalent (see class docblock).
                    $this->entityManager->clear();
                }
            }

            if ([] !== $previewRows) {
                $io->table(
                    ['Course ID', 'Session ID', 'User ID', 'Result', 'Calculated score'],
                    \array_slice($previewRows, 0, 100)
                );
            }

            $io->definitionList(
                ['Courses scanned' => $summary['courses_scanned']],
                ['Courses skipped (no completion rule)' => $summary['courses_skipped_no_rule']],
                ['Contexts skipped (ambiguous root category)' => $summary['sessions_skipped_ambiguous_category']],
                ['Contexts processed (base course + sessions)' => $summary['contexts_processed']],
                ['Scanned pending users' => $summary['scanned']],
                ['Incomplete/not eligible' => $summary['incomplete']],
                ['Would generate' => $summary['would_generate']],
                ['Generated' => $summary['generated']],
                ['Failed' => $summary['failed']],
                ['Last scanned course ID' => $summary['last_course_id']]
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

            if (!$this->service->generationLimitReached($summary, $limit, $dryRun)
                && 0 !== $scanLimit
                && $summary['scanned'] >= $scanLimit
            ) {
                $io->note(\sprintf(
                    'The scan limit was reached. Continue with --after-course-id=%d.',
                    $summary['last_course_id']
                ));
            }

            return Command::SUCCESS;
        } catch (Throwable $exception) {
            $io->error($exception->getMessage());

            return Command::FAILURE;
        }
    }

    /**
     * Processes one course: resolves every eligible root category it has —
     * the base course itself (session_id 0) plus one per session it hosts —
     * and, for each unambiguous one, evaluates every pending student in
     * batches and generates/reports certificates. Any structural reason to
     * skip a course or one of its session contexts (no rule, ambiguous
     * category) is recorded in $summary and never thrown — one bad course or
     * session must not abort the platform-wide run.
     *
     * @param array<string, int>                                        $summary
     * @param list<array{0: int, 1: int, 2: int, 3: string, 4: string}> $previewRows
     */
    private function processCourse(
        int $courseId,
        string $completionMode,
        ?int $userId,
        ?int $senderId,
        int $limit,
        int $scanLimit,
        int $batchSize,
        bool $dryRun,
        bool $sendNotification,
        SymfonyStyle $io,
        array &$summary,
        array &$previewRows
    ): void {
        if ('course-rule' === $completionMode && !$this->service->supports($courseId)) {
            ++$summary['courses_skipped_no_rule'];

            return;
        }

        $categories = $this->service->loadEligibleCategories($courseId, null);
        if ([] === $categories) {
            ++$summary['courses_skipped_no_rule'];

            return;
        }

        // Course-specific subject/message are optional: Certificate::generate() already
        // falls back to a translated default when either is empty, exactly like the manual
        // "Generate" button (LegacyGradebookCertificateBridge) does. Requiring this course
        // extra field to exist would block generation for every course that never
        // configured it — which is the common case, not an error.
        $notification = $this->service->resolveNotification($courseId) + ['subject' => '', 'message' => ''];
        $notification['sender_id'] = $senderId ?? 0;

        $courseRow = $this->connection->fetchAssociative(
            'SELECT code FROM course WHERE id = :courseId LIMIT 1',
            ['courseId' => $courseId]
        );
        $courseCode = false !== $courseRow ? (string) $courseRow['code'] : '';

        $categoriesBySession = [];
        foreach ($categories as $category) {
            $categoriesBySession[(int) $category['session_id']][] = $category;
        }

        foreach ($categoriesBySession as $sessionId => $sessionCategories) {
            if ($this->runLimitsReached($summary, $limit, $scanLimit, $dryRun)) {
                return;
            }

            if (\count($sessionCategories) > 1) {
                ++$summary['sessions_skipped_ambiguous_category'];
                $io->note(\sprintf(
                    'Course %d, session %d skipped: multiple eligible root categories (%s). Process it individually with chamilo:migration:process-achievement-certificates --category-id.',
                    $courseId,
                    $sessionId,
                    implode(', ', array_map(static fn (array $c): string => (string) $c['id'], $sessionCategories))
                ));

                continue;
            }

            ++$summary['contexts_processed'];

            $this->processContext(
                $courseId,
                $courseCode,
                $sessionId,
                $sessionCategories[0],
                $completionMode,
                $userId,
                $limit,
                $scanLimit,
                $batchSize,
                $dryRun,
                $sendNotification,
                $notification,
                $summary,
                $previewRows
            );
        }
    }

    /**
     * @param array{id: int|string, title: string, certif_min_score: float|string, session_id: int|string} $category
     * @param array{subject: string, message: string, sender_id: int}                                      $notification
     * @param array<string, int>                                                                           $summary
     * @param list<array{0: int, 1: int, 2: int, 3: string, 4: string}>                                    $previewRows
     */
    private function processContext(
        int $courseId,
        string $courseCode,
        int $sessionId,
        array $category,
        string $completionMode,
        ?int $userId,
        int $limit,
        int $scanLimit,
        int $batchSize,
        bool $dryRun,
        bool $sendNotification,
        array $notification,
        array &$summary,
        array &$previewRows
    ): void {
        $resolvedCategoryId = (int) $category['id'];
        $minimumScore = (float) $category['certif_min_score'];

        /** @var GradebookCategory|null $categoryEntity */
        $categoryEntity = $this->entityManager->find(GradebookCategory::class, $resolvedCategoryId);
        if (!$categoryEntity instanceof GradebookCategory) {
            // Defensive only: the row was read from the DB a moment ago.
            return;
        }

        $lastUserId = 0;

        while (true) {
            if ($this->runLimitsReached($summary, $limit, $scanLimit, $dryRun)) {
                return;
            }

            $remainingScan = 0 === $scanLimit ? $batchSize : max(0, $scanLimit - $summary['scanned']);
            if (0 === $remainingScan) {
                return;
            }

            $candidateIds = $this->service->loadPendingUserIds(
                $courseId,
                $resolvedCategoryId,
                $sessionId,
                $lastUserId,
                min($batchSize, $remainingScan),
                $userId
            );
            if ([] === $candidateIds) {
                return;
            }

            foreach ($candidateIds as $candidateUserId) {
                $lastUserId = $candidateUserId;
                ++$summary['scanned'];

                $evaluation = $this->service->evaluateCandidate(
                    $completionMode,
                    $candidateUserId,
                    $courseId,
                    $courseCode,
                    $minimumScore,
                    $sessionId,
                    $categoryEntity
                );

                $isComplete = !empty($evaluation['complete'])
                    && null !== $evaluation['score']
                    && (float) $evaluation['score'] >= $minimumScore;

                if (!$isComplete) {
                    ++$summary['incomplete'];

                    continue;
                }

                if ($dryRun) {
                    ++$summary['would_generate'];
                    $previewRows[] = [
                        $courseId,
                        $sessionId,
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
                            $courseId,
                            $sessionId,
                            $candidateUserId,
                            'generated',
                            $this->service->formatNumber((float) $evaluation['score']),
                        ];
                    } else {
                        ++$summary['failed'];
                        $previewRows[] = [
                            $courseId,
                            $sessionId,
                            $candidateUserId,
                            'failed',
                            $this->service->formatNumber((float) $evaluation['score']),
                        ];
                    }
                }

                if ($this->service->generationLimitReached($summary, $limit, $dryRun)) {
                    return;
                }
            }
        }
    }

    /**
     * @return list<int>
     */
    private function loadEligibleCourseIds(int $afterCourseId, int $batchSize): array
    {
        $rows = $this->connection->fetchFirstColumn(
            'SELECT DISTINCT course.id
             FROM course
             INNER JOIN gradebook_category gc
                ON gc.c_id = course.id
               AND (gc.parent_id IS NULL OR gc.parent_id = 0)
               AND gc.generate_certificates = 1
             WHERE course.id > :afterCourseId
             ORDER BY course.id ASC
             LIMIT '.$batchSize,
            ['afterCourseId' => $afterCourseId]
        );

        return array_map('intval', $rows);
    }

    /**
     * @param array<string, int> $summary
     */
    private function runLimitsReached(array $summary, int $limit, int $scanLimit, bool $dryRun): bool
    {
        if ($this->service->generationLimitReached($summary, $limit, $dryRun)) {
            return true;
        }

        return 0 !== $scanLimit && $summary['scanned'] >= $scanLimit;
    }
}
