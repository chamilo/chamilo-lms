<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Helpers;

use Chamilo\CoreBundle\Settings\SettingsManager;
use DateTimeImmutable;
use DateTimeZone;
use Doctrine\DBAL\Connection;

final class BuyCoursesExpiryHelper
{
    private const TABLE_SERVICES_SALE = 'plugin_buycourses_service_sale';
    private const TABLE_SERVICE_REL_EXTRA_FIELD = 'plugin_buycourses_service_rel_extra_field';
    private const TABLE_FROZEN_ENROLLMENT = 'plugin_buycourses_frozen_enrollment';

    private const SERVICE_STATUS_COMPLETED = 1;

    public function __construct(
        private readonly Connection $connection,
        private readonly SettingsManager $settingsManager,
    ) {}

    public function processExpiredServiceBenefits(): int
    {
        $processedUsers = 0;

        foreach ($this->getExpiredBuyerIds() as $userId) {
            if ($userId <= 0) {
                continue;
            }

            $this->processExpiredCourseCreationForUser($userId);
            $this->processExpiredHostingLimitForUser($userId);

            $processedUsers++;
        }

        return $processedUsers;
    }

    private function processExpiredCourseCreationForUser(int $userId): void
    {
        $limit = $this->getEffectiveMaxCoursesLimitForUser($userId);

        if ($limit <= 0) {
            return;
        }

        $courseIds = $this->getManagedCourseIdsByUser($userId);

        if (\count($courseIds) <= $limit) {
            return;
        }

        $excessCourseIds = \array_slice($courseIds, $limit);
        $courseTable = $this->mainTable(TABLE_MAIN_COURSE);

        foreach ($excessCourseIds as $courseId) {
            $this->connection->update(
                $courseTable,
                ['visibility' => 0],
                ['id' => (int) $courseId]
            );
        }
    }

    private function processExpiredHostingLimitForUser(int $userId): void
    {
        $courseIds = $this->getManagedCourseIdsByUser($userId);

        foreach ($courseIds as $courseId) {
            $limit = $this->getEffectiveUsersPerCourseLimitForCourse($courseId);
            $this->freezeExcessEnrollmentsForCourse($courseId, $limit);
        }
    }

    /**
     * @return int[]
     */
    private function getExpiredBuyerIds(): array
    {
        $table = $this->mainTable(self::TABLE_SERVICES_SALE);

        $sql = "SELECT DISTINCT buyer_id
                FROM $table
                WHERE status = :status
                  AND date_end IS NOT NULL
                  AND date_end < :now
                ORDER BY buyer_id ASC";

        $result = $this->connection->executeQuery(
            $sql,
            [
                'status' => self::SERVICE_STATUS_COMPLETED,
                'now' => $this->getUtcNow(),
            ]
        )->fetchFirstColumn();

        return \array_values(
            \array_filter(
                \array_map('intval', $result),
                static fn (int $userId): bool => $userId > 0
            )
        );
    }

    /**
     * @return int[]
     */
    private function getManagedCourseIdsByUser(int $userId): array
    {
        if ($userId <= 0) {
            return [];
        }

        $table = $this->mainTable(TABLE_MAIN_COURSE_USER);

        $sql = "SELECT DISTINCT c_id
                FROM $table
                WHERE user_id = :userId
                  AND status = :status
                  AND (relation_type IS NULL OR relation_type <> :rrhhRelationType)
                ORDER BY c_id ASC";

        $result = $this->connection->executeQuery(
            $sql,
            [
                'userId' => $userId,
                'status' => COURSEMANAGER,
                'rrhhRelationType' => COURSE_RELATION_TYPE_RRHH,
            ]
        )->fetchFirstColumn();

        return \array_values(
            \array_filter(
                \array_map('intval', $result),
                static fn (int $courseId): bool => $courseId > 0
            )
        );
    }

    /**
     * @return int[]
     */
    private function getCourseManagerIdsForCourse(int $courseId): array
    {
        if ($courseId <= 0) {
            return [];
        }

        $table = $this->mainTable(TABLE_MAIN_COURSE_USER);

        $sql = "SELECT DISTINCT user_id
                FROM $table
                WHERE c_id = :courseId
                  AND status = :status
                  AND (relation_type IS NULL OR relation_type <> :rrhhRelationType)
                ORDER BY user_id ASC";

        $result = $this->connection->executeQuery(
            $sql,
            [
                'courseId' => $courseId,
                'status' => COURSEMANAGER,
                'rrhhRelationType' => COURSE_RELATION_TYPE_RRHH,
            ]
        )->fetchFirstColumn();

        return \array_values(
            \array_filter(
                \array_map('intval', $result),
                static fn (int $userId): bool => $userId > 0
            )
        );
    }

    private function getEffectiveMaxCoursesLimitForUser(int $userId): int
    {
        $serviceLimit = $this->getLatestActiveGrantedValueFromSales($userId, 'buycourses_max_courses');
        if (null !== $serviceLimit && $serviceLimit > 0) {
            return $serviceLimit;
        }

        return $this->getSettingAsPositiveInt('platform.max_courses_per_user');
    }

    private function getEffectiveUsersPerCourseLimitForCourse(int $courseId): int
    {
        $limit = $this->getSettingAsPositiveInt('platform.hosting_limit_users_per_course');

        foreach ($this->getCourseManagerIdsForCourse($courseId) as $managerId) {
            $serviceLimit = $this->getLatestActiveGrantedValueFromSales($managerId, 'buycourses_hosting_limit');

            if (null !== $serviceLimit && $serviceLimit > $limit) {
                $limit = $serviceLimit;
            }
        }

        return $limit;
    }

    private function getLatestActiveGrantedValueFromSales(int $userId, string $variable): ?int
    {
        if ($userId <= 0 || '' === $variable) {
            return null;
        }

        $serviceRelTable = $this->mainTable(self::TABLE_SERVICE_REL_EXTRA_FIELD);
        $serviceSaleTable = $this->mainTable(self::TABLE_SERVICES_SALE);
        $extraFieldTable = $this->mainTable(TABLE_EXTRA_FIELD);

        $sql = "SELECT rel.granted_value
                FROM $serviceRelTable rel
                INNER JOIN $extraFieldTable ef
                    ON ef.id = rel.extra_field_id
                INNER JOIN $serviceSaleTable ss
                    ON ss.service_id = rel.service_id
                WHERE ef.variable = :variable
                  AND ss.buyer_id = :userId
                  AND ss.status = :status
                  AND ss.date_end >= :now
                ORDER BY ss.date_end DESC, ss.id DESC
                LIMIT 1";

        $value = $this->connection->fetchOne(
            $sql,
            [
                'variable' => $variable,
                'userId' => $userId,
                'status' => self::SERVICE_STATUS_COMPLETED,
                'now' => $this->getUtcNow(),
            ]
        );

        if (false === $value || null === $value) {
            return null;
        }

        $value = (int) $value;

        return $value > 0 ? $value : null;
    }

    private function clearFrozenEnrollmentsForCourse(int $courseId): void
    {
        if ($courseId <= 0) {
            return;
        }

        $table = $this->mainTable(self::TABLE_FROZEN_ENROLLMENT);

        $this->connection->delete(
            $table,
            ['course_id' => $courseId]
        );
    }

    private function freezeExcessEnrollmentsForCourse(int $courseId, int $limit): void
    {
        if ($courseId <= 0) {
            return;
        }

        if ($limit <= 0) {
            $this->clearFrozenEnrollmentsForCourse($courseId);

            return;
        }

        $courseUserTable = $this->mainTable(TABLE_MAIN_COURSE_USER);
        $frozenTable = $this->mainTable(self::TABLE_FROZEN_ENROLLMENT);

        $sql = "SELECT user_id
                FROM $courseUserTable
                WHERE c_id = :courseId
                  AND status = :status
                  AND (relation_type IS NULL OR relation_type <> :rrhhRelationType)
                ORDER BY user_id ASC";

        $result = $this->connection->executeQuery(
            $sql,
            [
                'courseId' => $courseId,
                'status' => STUDENT,
                'rrhhRelationType' => COURSE_RELATION_TYPE_RRHH,
            ]
        )->fetchFirstColumn();

        $studentIds = \array_values(
            \array_filter(
                \array_map('intval', $result),
                static fn (int $studentId): bool => $studentId > 0
            )
        );

        $this->clearFrozenEnrollmentsForCourse($courseId);

        if (\count($studentIds) <= $limit) {
            return;
        }

        $excessIds = \array_slice($studentIds, $limit);

        foreach ($excessIds as $studentId) {
            $this->connection->insert(
                $frozenTable,
                [
                    'course_id' => $courseId,
                    'user_id' => $studentId,
                    'frozen_since' => $this->getUtcNow(),
                ]
            );
        }
    }

    private function getSettingAsPositiveInt(string $setting): int
    {
        $raw = $this->settingsManager->getSetting($setting, true);

        return max(0, (int) ($raw ?? 0));
    }

    private function mainTable(string $table): string
    {
        return \Database::get_main_table($table);
    }

    private function getUtcNow(): string
    {
        return (new DateTimeImmutable('now', new DateTimeZone('UTC')))
            ->format('Y-m-d H:i:s');
    }
}
