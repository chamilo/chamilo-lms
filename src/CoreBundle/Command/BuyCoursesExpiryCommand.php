<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Command;

use BuyCoursesPlugin;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\CourseRelUser;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Database;
use DateTimeImmutable;
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
    description: 'Refresh BuyCourses benefits and enforce service-based limits after expiry.'
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
            ->setHelp('Refreshes benefit payloads from BuyCourses service sales, closes excess courses when the extra limit has expired and reconciles frozen enrollments against the effective users-per-course limit.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $dryRun = (bool) $input->getOption('dry-run');
        $today = (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format('Y-m-d');
        $plugin = BuyCoursesPlugin::create();
        $connection = $this->entityManager->getConnection();

        if ($dryRun) {
            $io->note('DRY-RUN mode enabled. No database writes will be performed.');
        }

        $ownerIds = $this->getCandidateBuyerIds($connection);
        if ([] === $ownerIds) {
            $io->success('No completed BuyCourses service sales found to process.');

            return Command::SUCCESS;
        }

        $io->info(sprintf('Processing %d owner(s) with completed service sales.', count($ownerIds)));

        foreach ($ownerIds as $ownerId) {
            $io->section(sprintf('Owner %d', $ownerId));

            $plugin->refreshBenefitPayloadFromSales($ownerId, BuyCoursesPlugin::EXTRA_FIELD_MAX_COURSES);
            $plugin->refreshBenefitPayloadFromSales($ownerId, BuyCoursesPlugin::EXTRA_FIELD_HOSTING_LIMIT);

            $this->processCourseCreationLimit($connection, $plugin, $ownerId, $dryRun, $io);
            $this->processUsersPerCourseLimit($connection, $plugin, $ownerId, $today, $dryRun, $io);
        }

        $io->success('BuyCourses expiry processing finished.');

        return Command::SUCCESS;
    }

    /**
     * @return int[]
     */
    private function getCandidateBuyerIds(Connection $connection): array
    {
        $serviceSaleTable = Database::get_main_table(BuyCoursesPlugin::TABLE_SERVICES_SALE);
        $rows = $connection->fetchAllAssociative(
            "SELECT DISTINCT buyer_id FROM {$serviceSaleTable} WHERE status = ? AND buyer_id IS NOT NULL ORDER BY buyer_id ASC",
            [BuyCoursesPlugin::SERVICE_STATUS_COMPLETED],
            [ParameterType::INTEGER]
        );

        return array_map(static fn (array $row): int => (int) $row['buyer_id'], $rows);
    }

    private function processCourseCreationLimit(
        Connection $connection,
        BuyCoursesPlugin $plugin,
        int $ownerId,
        bool $dryRun,
        SymfonyStyle $io,
    ): void {
        $activePayload = $plugin->getActiveBenefitPayload($ownerId, BuyCoursesPlugin::EXTRA_FIELD_MAX_COURSES);
        $effectiveLimit = $this->resolveEffectiveMaxCoursesPerUser($activePayload);

        if ($effectiveLimit <= 0) {
            $io->comment(' R1: unlimited effective course creation limit.');

            return;
        }

        $teacherCourseIds = $this->getTeacherCourseIds($connection, $ownerId);
        $excess = count($teacherCourseIds) - $effectiveLimit;

        if ($excess <= 0) {
            $io->comment(sprintf(' R1: %d course(s), effective limit %d, nothing to close.', count($teacherCourseIds), $effectiveLimit));

            return;
        }

        rsort($teacherCourseIds);
        $courseIdsToClose = array_slice($teacherCourseIds, 0, $excess);

        foreach ($courseIdsToClose as $courseId) {
            $io->comment(sprintf(' R1: closing course %d for owner %d.', $courseId, $ownerId));

            if ($dryRun) {
                continue;
            }

            $connection->update(
                Database::get_main_table(TABLE_MAIN_COURSE),
                ['visibility' => Course::CLOSED],
                ['id' => $courseId],
                [Types::INTEGER, Types::INTEGER]
            );
        }
    }

    private function processUsersPerCourseLimit(
        Connection $connection,
        BuyCoursesPlugin $plugin,
        int $ownerId,
        string $today,
        bool $dryRun,
        SymfonyStyle $io,
    ): void {
        $activePayload = $plugin->getActiveBenefitPayload($ownerId, BuyCoursesPlugin::EXTRA_FIELD_HOSTING_LIMIT);
        $effectiveLimit = $this->resolveEffectiveUsersPerCourseLimit($activePayload);
        $teacherCourseIds = $this->getTeacherCourseIds($connection, $ownerId);

        if ([] === $teacherCourseIds) {
            $io->comment(' R2: owner has no teacher courses.');

            return;
        }

        foreach ($teacherCourseIds as $courseId) {
            $studentIds = $this->getStudentUserIdsForCourse($connection, $courseId);

            if ($effectiveLimit <= 0) {
                $io->comment(sprintf(' R2: course %d unlimited, removing frozen rows if any.', $courseId));

                if (!$dryRun) {
                    $this->deleteFrozenRowsForCourse($connection, $courseId);
                }

                continue;
            }

            $allowedStudentIds = array_slice($studentIds, 0, $effectiveLimit);
            $frozenStudentIds = array_slice($studentIds, $effectiveLimit);

            $io->comment(sprintf(
                ' R2: course %d has %d student(s), effective limit %d, freezing %d.',
                $courseId,
                count($studentIds),
                $effectiveLimit,
                count($frozenStudentIds)
            ));

            if ($dryRun) {
                continue;
            }

            $this->deleteFrozenRowsNotInList($connection, $courseId, $frozenStudentIds);

            foreach ($allowedStudentIds as $studentId) {
                $this->deleteFrozenRow($connection, $courseId, $studentId);
            }

            foreach ($frozenStudentIds as $studentId) {
                $this->insertFrozenRowIfMissing($connection, $courseId, $studentId, $today);
            }
        }
    }

    private function resolveEffectiveMaxCoursesPerUser(?array $activePayload): int
    {
        $activeLimit = isset($activePayload['limit']) ? (int) $activePayload['limit'] : 0;
        if ($activeLimit > 0) {
            return $activeLimit;
        }

        return max(0, (int) ($this->settingsManager->getSetting('platform.max_courses_per_user', true) ?? 0));
    }

    private function resolveEffectiveUsersPerCourseLimit(?array $activePayload): int
    {
        $activeLimit = isset($activePayload['limit']) ? (int) $activePayload['limit'] : 0;
        if ($activeLimit > 0) {
            return $activeLimit;
        }

        return max(0, (int) ($this->settingsManager->getSetting('platform.hosting_limit_users_per_course', true) ?? 0));
    }

    /**
     * @return int[]
     */
    private function getTeacherCourseIds(Connection $connection, int $ownerId): array
    {
        $courseRelUserTable = Database::get_main_table(TABLE_MAIN_COURSE_USER);
        $rows = $connection->fetchAllAssociative(
            "SELECT c_id FROM {$courseRelUserTable} WHERE user_id = ? AND status = ? ORDER BY c_id ASC",
            [$ownerId, CourseRelUser::TEACHER],
            [ParameterType::INTEGER, ParameterType::INTEGER]
        );

        return array_map(static fn (array $row): int => (int) $row['c_id'], $rows);
    }

    /**
     * @return int[]
     */
    private function getStudentUserIdsForCourse(Connection $connection, int $courseId): array
    {
        $courseRelUserTable = Database::get_main_table(TABLE_MAIN_COURSE_USER);
        $rows = $connection->fetchAllAssociative(
            "SELECT user_id FROM {$courseRelUserTable} WHERE c_id = ? AND status = ? ORDER BY id ASC",
            [$courseId, CourseRelUser::STUDENT],
            [ParameterType::INTEGER, ParameterType::INTEGER]
        );

        return array_map(static fn (array $row): int => (int) $row['user_id'], $rows);
    }

    private function deleteFrozenRowsForCourse(Connection $connection, int $courseId): void
    {
        $frozenTable = Database::get_main_table(BuyCoursesPlugin::TABLE_FROZEN_ENROLLMENT);
        $connection->delete($frozenTable, ['course_id' => $courseId], [Types::INTEGER]);
    }

    /**
     * @param int[] $frozenStudentIds
     */
    private function deleteFrozenRowsNotInList(Connection $connection, int $courseId, array $frozenStudentIds): void
    {
        $frozenTable = Database::get_main_table(BuyCoursesPlugin::TABLE_FROZEN_ENROLLMENT);

        if ([] === $frozenStudentIds) {
            $this->deleteFrozenRowsForCourse($connection, $courseId);

            return;
        }

        $placeholders = implode(',', array_fill(0, count($frozenStudentIds), '?'));
        $params = array_merge([$courseId], $frozenStudentIds);
        $types = array_fill(0, count($params), ParameterType::INTEGER);

        $connection->executeStatement(
            "DELETE FROM {$frozenTable} WHERE course_id = ? AND user_id NOT IN ({$placeholders})",
            $params,
            $types
        );
    }

    private function deleteFrozenRow(Connection $connection, int $courseId, int $studentId): void
    {
        $frozenTable = Database::get_main_table(BuyCoursesPlugin::TABLE_FROZEN_ENROLLMENT);
        $connection->delete(
            $frozenTable,
            [
                'course_id' => $courseId,
                'user_id' => $studentId,
            ],
            [Types::INTEGER, Types::INTEGER]
        );
    }

    private function insertFrozenRowIfMissing(Connection $connection, int $courseId, int $studentId, string $today): void
    {
        $frozenTable = Database::get_main_table(BuyCoursesPlugin::TABLE_FROZEN_ENROLLMENT);
        $existingId = $connection->fetchOne(
            "SELECT id FROM {$frozenTable} WHERE course_id = ? AND user_id = ?",
            [$courseId, $studentId],
            [ParameterType::INTEGER, ParameterType::INTEGER]
        );

        if ($existingId) {
            return;
        }

        $connection->insert(
            $frozenTable,
            [
                'course_id' => $courseId,
                'user_id' => $studentId,
                'frozen_since' => $today,
            ],
            [Types::INTEGER, Types::INTEGER, Types::STRING]
        );
    }
}
