<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Helpers;

use DateTimeImmutable;
use DateTimeZone;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;

final class BuyCoursesExpiryHelper
{
    private const TABLE_SETTINGS = 'settings';
    private const TABLE_COURSE = 'course';
    private const TABLE_COURSE_REL_USER = 'course_rel_user';
    private const TABLE_EXTRA_FIELD = 'extra_field';
    private const TABLE_SERVICES_SALE = 'plugin_buycourses_service_sale';
    private const TABLE_SERVICE_REL_EXTRA_FIELD = 'plugin_buycourses_service_rel_extra_field';
    private const TABLE_FROZEN_ENROLLMENT = 'plugin_buycourses_frozen_enrollment';

    private const SERVICE_STATUS_COMPLETED = 1;
    private const COURSE_USER_STATUS_TEACHER = 1;
    private const COURSE_USER_STATUS_STUDENT = 5;
    private const COURSE_RELATION_TYPE_RRHH = 1;
    private const COURSE_VISIBILITY_CLOSED = 0;

    private ?bool $buyCoursesAvailable = null;

    public function __construct(
        private readonly Connection $connection,
        private readonly PluginHelper $pluginHelper,
    ) {}

    public function processExpiredServiceBenefits(): int
    {
        if (!$this->isBuyCoursesAvailable()) {
            return 0;
        }

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

    public function isFrozenEnrollment(int $courseId, int $userId): bool
    {
        if ($courseId <= 0 || $userId <= 0) {
            return false;
        }

        if (!$this->isBuyCoursesAvailable()) {
            return false;
        }

        $sql = 'SELECT 1
                FROM '.self::TABLE_FROZEN_ENROLLMENT.'
                WHERE course_id = :courseId
                  AND user_id = :userId
                LIMIT 1';

        $result = $this->connection->fetchOne(
            $sql,
            [
                'courseId' => $courseId,
                'userId' => $userId,
            ]
        );

        return false !== $result && null !== $result;
    }

    private function isBuyCoursesAvailable(): bool
    {
        if (null !== $this->buyCoursesAvailable) {
            return $this->buyCoursesAvailable;
        }

        if (!$this->pluginHelper->isPluginEnabled('BuyCourses')) {
            return $this->buyCoursesAvailable = false;
        }

        try {
            $schemaManager = $this->connection->createSchemaManager();

            foreach ([
                         self::TABLE_SERVICES_SALE,
                         self::TABLE_SERVICE_REL_EXTRA_FIELD,
                         self::TABLE_FROZEN_ENROLLMENT,
                     ] as $tableName) {
                if (!$schemaManager->tablesExist([$tableName])) {
                    return $this->buyCoursesAvailable = false;
                }
            }
        } catch (Exception) {
            return $this->buyCoursesAvailable = false;
        }

        return $this->buyCoursesAvailable = true;
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

        foreach ($excessCourseIds as $courseId) {
            $this->connection->update(
                self::TABLE_COURSE,
                ['visibility' => self::COURSE_VISIBILITY_CLOSED],
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
        $sql = 'SELECT DISTINCT buyer_id
                FROM '.self::TABLE_SERVICES_SALE.'
                WHERE status = :status
                  AND date_end IS NOT NULL
                  AND date_end < :now
                ORDER BY buyer_id ASC';

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

        $sql = 'SELECT DISTINCT c_id
                FROM '.self::TABLE_COURSE_REL_USER.'
                WHERE user_id = :userId
                  AND status = :status
                  AND (relation_type IS NULL OR relation_type <> :rrhhRelationType)
                ORDER BY c_id ASC';

        $result = $this->connection->executeQuery(
            $sql,
            [
                'userId' => $userId,
                'status' => self::COURSE_USER_STATUS_TEACHER,
                'rrhhRelationType' => self::COURSE_RELATION_TYPE_RRHH,
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

        $sql = 'SELECT DISTINCT user_id
                FROM '.self::TABLE_COURSE_REL_USER.'
                WHERE c_id = :courseId
                  AND status = :status
                  AND (relation_type IS NULL OR relation_type <> :rrhhRelationType)
                ORDER BY user_id ASC';

        $result = $this->connection->executeQuery(
            $sql,
            [
                'courseId' => $courseId,
                'status' => self::COURSE_USER_STATUS_TEACHER,
                'rrhhRelationType' => self::COURSE_RELATION_TYPE_RRHH,
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

        return $this->getSettingAsPositiveInt(
            'max_courses_per_user',
            'platform.max_courses_per_user'
        );
    }

    private function getEffectiveUsersPerCourseLimitForCourse(int $courseId): int
    {
        $limit = $this->getSettingAsPositiveInt(
            'hosting_limit_users_per_course',
            'platform.hosting_limit_users_per_course'
        );

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

        $sql = 'SELECT rel.granted_value
                FROM '.self::TABLE_SERVICE_REL_EXTRA_FIELD.' rel
                INNER JOIN '.self::TABLE_EXTRA_FIELD.' ef
                    ON ef.id = rel.extra_field_id
                INNER JOIN '.self::TABLE_SERVICES_SALE.' ss
                    ON ss.service_id = rel.service_id
                WHERE ef.variable = :variable
                  AND ss.buyer_id = :userId
                  AND ss.status = :status
                  AND ss.date_end >= :now
                ORDER BY ss.date_end DESC, ss.id DESC
                LIMIT 1';

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

        $this->connection->delete(
            self::TABLE_FROZEN_ENROLLMENT,
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

        $sql = 'SELECT user_id
                FROM '.self::TABLE_COURSE_REL_USER.'
                WHERE c_id = :courseId
                  AND status = :status
                  AND (relation_type IS NULL OR relation_type <> :rrhhRelationType)
                ORDER BY user_id ASC';

        $result = $this->connection->executeQuery(
            $sql,
            [
                'courseId' => $courseId,
                'status' => self::COURSE_USER_STATUS_STUDENT,
                'rrhhRelationType' => self::COURSE_RELATION_TYPE_RRHH,
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
                self::TABLE_FROZEN_ENROLLMENT,
                [
                    'course_id' => $courseId,
                    'user_id' => $studentId,
                    'frozen_since' => $this->getUtcNow(),
                ]
            );
        }
    }

    private function getSettingAsPositiveInt(string ...$variables): int
    {
        foreach ($variables as $variable) {
            $value = $this->connection->fetchOne(
                'SELECT selected_value
                 FROM '.self::TABLE_SETTINGS.'
                 WHERE variable = :variable
                 LIMIT 1',
                [
                    'variable' => $variable,
                ]
            );

            if (false === $value || null === $value || '' === (string) $value) {
                continue;
            }

            return max(0, (int) $value);
        }

        return 0;
    }

    private function getUtcNow(): string
    {
        return (new DateTimeImmutable('now', new DateTimeZone('UTC')))
            ->format('Y-m-d H:i:s');
    }
}
