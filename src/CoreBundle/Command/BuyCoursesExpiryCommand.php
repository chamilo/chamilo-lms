<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Command;

use BuyCoursesPlugin;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\CourseRelUser;
use Chamilo\CoreBundle\Settings\SettingsManager;
use DateTime;
use DateTimeZone;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'buycourses:process-expiry',
    description: 'Process expired BuyCourses service sales: close excess courses (R1) and freeze excess enrollments (R2).',
)]
class BuyCoursesExpiryCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly SettingsManager $settingsManager,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Simulate changes without writing to the database')
            ->setHelp(
                'Reads expired service sales and enforces limits: closes excess courses (R1) and inserts frozen enrollment rows (R2).'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $dryRun = (bool) $input->getOption('dry-run');
        $now = new DateTime('now', new DateTimeZone('UTC'));
        $today = $now->format('Y-m-d');

        if ($dryRun) {
            $io->note('DRY-RUN mode — no changes will be written.');
        }

        $conn = $this->entityManager->getConnection();

        $expiredSales = $this->fetchExpiredServiceSales($conn, $today);

        if (empty($expiredSales)) {
            $io->success('No expired service sales found.');

            return Command::SUCCESS;
        }

        $io->info(\sprintf('Found %d expired service sale(s) to process.', \count($expiredSales)));

        foreach ($expiredSales as $sale) {
            $buyerId = (int) $sale['buyer_id'];
            $io->section(\sprintf('Processing sale ID %d for user %d', (int) $sale['id'], $buyerId));

            $this->processR1($conn, $io, $buyerId, $dryRun);
            $this->processR2($conn, $io, $buyerId, $today, $dryRun);
        }

        $io->success('BuyCourses expiry processing complete.');

        return Command::SUCCESS;
    }

    /**
     * R1: Close excess courses for the user beyond the global platform limit.
     * Newest courses (by id DESC) are closed first.
     */
    private function processR1(Connection $conn, SymfonyStyle $io, int $userId, bool $dryRun): void
    {
        $globalLimit = $this->resolveGlobalMaxCoursesPerUser();
        if (0 === $globalLimit) {
            return; // unlimited — nothing to close
        }

        // Check if user still has an active service purchase for buycourses_max_courses
        if ($this->userHasActiveServiceSale($conn, $userId)) {
            $io->comment(\sprintf('  R1: user %d has an active service sale — skipping course close.', $userId));

            return;
        }

        $teacherCourseIds = $this->getTeacherCourseIds($conn, $userId);
        $excess = \count($teacherCourseIds) - $globalLimit;

        if ($excess <= 0) {
            $io->comment(\sprintf('  R1: user %d has %d courses, limit=%d — no action.', $userId, \count($teacherCourseIds), $globalLimit));

            return;
        }

        // Close the newest $excess courses (sorted desc by id = newest first)
        rsort($teacherCourseIds);
        $toClose = \array_slice($teacherCourseIds, 0, $excess);

        foreach ($toClose as $courseId) {
            $io->comment(\sprintf('  R1: closing course ID %d for user %d', $courseId, $userId));
            if (!$dryRun) {
                $conn->update(
                    'course',
                    ['visibility' => Course::CLOSED],
                    ['id' => $courseId],
                    [Types::INTEGER, Types::INTEGER]
                );
            }
        }
    }

    /**
     * R2: Freeze enrollments beyond the global users-per-course limit for courses owned by this user.
     */
    private function processR2(Connection $conn, SymfonyStyle $io, int $userId, string $today, bool $dryRun): void
    {
        $globalLimit = $this->resolveGlobalUsersPerCourse();
        if (0 === $globalLimit) {
            return; // unlimited — nothing to freeze
        }

        // Check if user still has an active hosting_limit service purchase
        if ($this->userHasActiveServiceSale($conn, $userId)) {
            $io->comment(\sprintf('  R2: user %d has an active service sale — skipping enrollment freeze.', $userId));

            return;
        }

        $frozenTable = BuyCoursesPlugin::TABLE_FROZEN_ENROLLMENT;
        $teacherCourseIds = $this->getTeacherCourseIds($conn, $userId);

        foreach ($teacherCourseIds as $courseId) {
            $enrolledUserIds = $this->getStudentUserIdsForCourse($conn, $courseId);
            $excess = \count($enrolledUserIds) - $globalLimit;

            if ($excess <= 0) {
                continue;
            }

            // Freeze the last $excess students (keep oldest enrollments, freeze newest)
            $toFreeze = \array_slice($enrolledUserIds, $globalLimit);

            foreach ($toFreeze as $studentId) {
                $io->comment(\sprintf('  R2: freezing user %d in course %d', $studentId, $courseId));
                if (!$dryRun) {
                    $existing = $conn->fetchOne(
                        "SELECT id FROM {$frozenTable} WHERE course_id = ? AND user_id = ?",
                        [$courseId, $studentId],
                        [ParameterType::INTEGER, ParameterType::INTEGER]
                    );
                    if (!$existing) {
                        $conn->insert(
                            $frozenTable,
                            ['course_id' => $courseId, 'user_id' => $studentId, 'frozen_since' => $today],
                            [Types::INTEGER, Types::INTEGER, Types::STRING]
                        );
                    }
                }
            }
        }
    }

    /**
     * Returns expired service sales: date_end < today AND status = SERVICE_STATUS_COMPLETED.
     *
     * @return array<int, array<string, mixed>>
     */
    private function fetchExpiredServiceSales(Connection $conn, string $today): array
    {
        $table = BuyCoursesPlugin::TABLE_SERVICES_SALE;

        return $conn->fetchAllAssociative(
            "SELECT id, buyer_id, date_end FROM {$table} WHERE status = ? AND date_end < ?",
            [BuyCoursesPlugin::SERVICE_STATUS_COMPLETED, $today],
            [ParameterType::INTEGER, Types::STRING]
        );
    }

    /**
     * Returns true if the user still has at least one non-expired completed service sale.
     */
    private function userHasActiveServiceSale(Connection $conn, int $userId): bool
    {
        $table = BuyCoursesPlugin::TABLE_SERVICES_SALE;
        $count = (int) $conn->fetchOne(
            "SELECT COUNT(id) FROM {$table} WHERE buyer_id = ? AND status = ? AND (date_end IS NULL OR date_end >= CURDATE())",
            [$userId, BuyCoursesPlugin::SERVICE_STATUS_COMPLETED],
            [ParameterType::INTEGER, ParameterType::INTEGER]
        );

        return $count > 0;
    }

    /**
     * Returns an array of course IDs where the user is teacher, ordered by id ASC (oldest first).
     *
     * @return int[]
     */
    private function getTeacherCourseIds(Connection $conn, int $userId): array
    {
        $rows = $conn->fetchAllAssociative(
            'SELECT c_id FROM course_rel_user WHERE user_id = ? AND status = ? ORDER BY c_id ASC',
            [$userId, CourseRelUser::TEACHER],
            [ParameterType::INTEGER, ParameterType::INTEGER]
        );

        return array_column($rows, 'c_id');
    }

    /**
     * Returns student user IDs for a course, ordered by relation ID ASC (oldest subscription first).
     *
     * @return int[]
     */
    private function getStudentUserIdsForCourse(Connection $conn, int $courseId): array
    {
        $rows = $conn->fetchAllAssociative(
            'SELECT user_id FROM course_rel_user WHERE c_id = ? AND status = ? ORDER BY id ASC',
            [$courseId, CourseRelUser::STUDENT],
            [ParameterType::INTEGER, ParameterType::INTEGER]
        );

        return array_column($rows, 'user_id');
    }

    private function resolveGlobalMaxCoursesPerUser(): int
    {
        $raw = $this->settingsManager->getSetting('platform.max_courses_per_user', true);

        return max(0, (int) ($raw ?? 0));
    }

    private function resolveGlobalUsersPerCourse(): int
    {
        $raw = $this->settingsManager->getSetting('platform.hosting_limit_users_per_course', true);

        return max(0, (int) ($raw ?? 0));
    }
}
