<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Command;

use Category;
use Chamilo\CoreBundle\Component\Gradebook\CourseCompletionRuleEvaluator;
use Chamilo\CoreBundle\Entity\ExtraField;
use Chamilo\CoreBundle\Entity\GradebookCategory;
use Chamilo\CoreBundle\Entity\User;
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
use Throwable;
use UserManager;

use const PHP_INT_MAX;
use const STUDENT;

#[AsCommand(
    name: 'chamilo:migration:process-achievement-certificates',
    description: 'Generate missing achievement certificates safely for one configured course.'
)]
final class ProcessAchievementCertificatesCommand extends Command
{
    private const CERTIFICATE_SUBJECT_FIELD =
        'plugin_gradingelectronic_certificate_notification_subject';
    private const CERTIFICATE_MESSAGE_FIELD =
        'plugin_gradingelectronic_certificate_notification_message';

    private readonly CourseCompletionRuleEvaluator $evaluator;

    public function __construct(
        private readonly Connection $connection,
        private readonly EntityManagerInterface $entityManager,
        private readonly KernelInterface $kernel,
    ) {
        $this->evaluator = new CourseCompletionRuleEvaluator($connection);

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
            $this->bootstrapLegacy();

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

                $this->assertValidSender($senderId);
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
                && !$this->evaluator->supports($courseId)
            ) {
                throw new RuntimeException(\sprintf('Course %d has no persisted completion rule. Use --completion-mode=gradebook only for a course whose legacy automatic process was verified to use the native gradebook result.', $courseId));
            }

            $notification = $this->resolveNotification($courseId);
            if (!isset($notification['subject'], $notification['message'])) {
                throw new RuntimeException(\sprintf('Course %d has no unambiguous certificate notification subject/message configuration.', $courseId));
            }

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
                ['Minimum score' => $this->formatNumber($minimumScore)],
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
                $candidateIds = $this->loadPendingUserIds(
                    $courseId,
                    $resolvedCategoryId,
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

                    $evaluation = $this->evaluateCandidate(
                        $completionMode,
                        $candidateUserId,
                        $courseId,
                        (string) $course['code'],
                        $minimumScore,
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
                                    : $this->formatNumber((float) $evaluation['score']),
                            ];
                        }

                        continue;
                    }

                    if ($dryRun) {
                        ++$summary['would_generate'];
                        $previewRows[] = [
                            $candidateUserId,
                            'would generate',
                            $this->formatNumber((float) $evaluation['score']),
                        ];
                    } else {
                        $created = $this->generateCertificate(
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
                                $this->formatNumber((float) $evaluation['score']),
                            ];
                        } else {
                            ++$summary['failed'];
                            $previewRows[] = [
                                $candidateUserId,
                                'failed',
                                $this->formatNumber((float) $evaluation['score']),
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
     * @return array{complete: bool, score: float|int|null, ...}
     */
    private function evaluateCandidate(
        string $completionMode,
        int $userId,
        int $courseId,
        string $courseCode,
        float $minimumScore,
        GradebookCategory $category
    ): array {
        if ('course-rule' === $completionMode) {
            return $this->evaluator->evaluate(
                $userId,
                $courseId,
                $courseCode,
                $minimumScore,
                0
            );
        }

        $score = Category::getCurrentScore(
            $userId,
            $category,
            true,
            $courseId,
            0
        );

        return [
            'complete' => (float) $score >= $minimumScore,
            'score' => $score,
        ];
    }

    private function bootstrapLegacy(): void
    {
        $globalFile = $this->kernel->getProjectDir().'/public/main/inc/global.inc.php';
        if (!is_file($globalFile)) {
            throw new RuntimeException(\sprintf('Legacy bootstrap was not found: %s', $globalFile));
        }

        require_once $globalFile;
    }

    private function assertValidSender(int $senderId): void
    {
        /** @var User|null $sender */
        $sender = $this->entityManager->find(User::class, $senderId);

        if (!$sender instanceof User || !$sender->isActive()) {
            throw new RuntimeException(\sprintf('Sender %d was not found or is inactive.', $senderId));
        }

        if (!UserManager::is_admin($senderId)) {
            throw new RuntimeException(\sprintf('Sender %d must be a platform administrator.', $senderId));
        }
    }

    /**
     * @return array{subject?: string, message?: string}
     */
    private function resolveNotification(int $courseId): array
    {
        $fieldIds = [];

        foreach (
            [
                'subject' => self::CERTIFICATE_SUBJECT_FIELD,
                'message' => self::CERTIFICATE_MESSAGE_FIELD,
            ] as $key => $variable
        ) {
            $fieldId = $this->connection->fetchOne(
                'SELECT id
                 FROM extra_field
                 WHERE item_type = :itemType
                   AND variable = :variable
                 LIMIT 1',
                [
                    'itemType' => ExtraField::COURSE_FIELD_TYPE,
                    'variable' => $variable,
                ]
            );

            if (false === $fieldId || (int) $fieldId <= 0) {
                return [];
            }

            $fieldIds[$key] = (int) $fieldId;
        }

        $notification = [];

        foreach ($fieldIds as $key => $fieldId) {
            $rows = $this->connection->fetchFirstColumn(
                'SELECT field_value
                 FROM extra_field_values
                 WHERE field_id = :fieldId
                   AND item_id = :courseId
                 ORDER BY id',
                [
                    'fieldId' => $fieldId,
                    'courseId' => $courseId,
                ]
            );

            if (\count($rows) > 1) {
                throw new RuntimeException(\sprintf('Course %d has duplicate values for %s.', $courseId, 'subject' === $key ? self::CERTIFICATE_SUBJECT_FIELD : self::CERTIFICATE_MESSAGE_FIELD));
            }

            $notification[$key] = trim((string) ($rows[0] ?? ''));
        }

        if ('' === $notification['subject'] && '' === $notification['message']) {
            return [];
        }

        return $notification;
    }

    /**
     * @return array{id: int|string, title: string, certif_min_score: float|string}
     */
    private function resolveCategory(int $courseId, ?int $requestedCategoryId): array
    {
        $params = ['courseId' => $courseId];
        $categoryFilter = '';

        if (null !== $requestedCategoryId) {
            $categoryFilter = ' AND id = :categoryId';
            $params['categoryId'] = $requestedCategoryId;
        }

        $categories = $this->connection->fetchAllAssociative(
            'SELECT id, title, certif_min_score
             FROM gradebook_category
             WHERE c_id = :courseId
               AND (parent_id IS NULL OR parent_id = 0)
               AND COALESCE(session_id, 0) = 0
               AND generate_certificates = 1'
            .$categoryFilter.'
             ORDER BY id',
            $params
        );

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
     * @return list<int>
     */
    private function loadPendingUserIds(
        int $courseId,
        int $categoryId,
        int $afterUserId,
        int $batchSize,
        ?int $requestedUserId
    ): array {
        $userFilter = '';
        $params = [
            'courseId' => $courseId,
            'categoryId' => $categoryId,
            'studentStatus' => STUDENT,
            'afterUserId' => $afterUserId,
        ];

        if (null !== $requestedUserId) {
            $userFilter = ' AND course_user.user_id = :requestedUserId';
            $params['requestedUserId'] = $requestedUserId;
        }

        $rows = $this->connection->fetchFirstColumn(
            'SELECT DISTINCT course_user.user_id
             FROM course_rel_user course_user
             INNER JOIN user selected_user
                ON selected_user.id = course_user.user_id
               AND selected_user.active = 1
             LEFT JOIN gradebook_certificate certificate
                ON certificate.cat_id = :categoryId
               AND certificate.user_id = course_user.user_id
             WHERE course_user.c_id = :courseId
               AND course_user.status = :studentStatus
               AND course_user.user_id > :afterUserId
               AND certificate.id IS NULL'
            .$userFilter.'
             ORDER BY course_user.user_id ASC
             LIMIT '.$batchSize,
            $params
        );

        return array_map('intval', $rows);
    }

    /**
     * @param array{subject: string, message: string, sender_id: int} $notification
     */
    private function generateCertificate(
        GradebookCategory $category,
        int $userId,
        bool $sendNotification,
        array $notification
    ): bool {
        Category::generateUserCertificate(
            $category,
            $userId,
            $sendNotification,
            true,
            $notification
        );

        $certificate = $this->connection->fetchAssociative(
            'SELECT id, resource_node_id
             FROM gradebook_certificate
             WHERE cat_id = :categoryId
               AND user_id = :userId
             ORDER BY id DESC
             LIMIT 1',
            [
                'categoryId' => (int) $category->getId(),
                'userId' => $userId,
            ]
        );

        return false !== $certificate
            && (int) ($certificate['id'] ?? 0) > 0
            && (int) ($certificate['resource_node_id'] ?? 0) > 0;
    }

    /**
     * @param array<string, int> $summary
     */
    private function generationLimitReached(array $summary, int $limit, bool $dryRun): bool
    {
        if (0 === $limit) {
            return false;
        }

        $processed = $dryRun
            ? $summary['would_generate']
            : $summary['generated'];

        return $processed >= $limit;
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

    private function positiveOption(InputInterface $input, string $name): ?int
    {
        $rawValue = trim((string) $input->getOption($name));

        if ('' === $rawValue || !ctype_digit($rawValue) || (int) $rawValue <= 0) {
            return null;
        }

        return (int) $rawValue;
    }

    private function optionalPositiveOption(InputInterface $input, string $name): ?int
    {
        $rawValue = trim((string) $input->getOption($name));

        if ('' === $rawValue) {
            return null;
        }

        if (!ctype_digit($rawValue) || (int) $rawValue <= 0) {
            throw new RuntimeException(\sprintf('--%s must be a positive integer.', $name));
        }

        return (int) $rawValue;
    }

    private function boundedPositiveOption(
        InputInterface $input,
        string $name,
        int $maximum
    ): int {
        $value = $this->optionalPositiveOption($input, $name);

        if (null === $value || $value > $maximum) {
            throw new RuntimeException(\sprintf('--%s must be between 1 and %d.', $name, $maximum));
        }

        return $value;
    }

    private function boundedNonNegativeOption(
        InputInterface $input,
        string $name,
        int $maximum
    ): int {
        $rawValue = trim((string) $input->getOption($name));

        if ('' === $rawValue || !ctype_digit($rawValue)) {
            throw new RuntimeException(\sprintf('--%s must be a non-negative integer.', $name));
        }

        $value = (int) $rawValue;

        if ($value > $maximum) {
            throw new RuntimeException(\sprintf('--%s must be between 0 and %d.', $name, $maximum));
        }

        return $value;
    }

    private function formatNumber(float $value): string
    {
        return rtrim(rtrim(number_format($value, 4, '.', ''), '0'), '.');
    }
}
