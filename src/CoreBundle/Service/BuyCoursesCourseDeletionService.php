<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Service;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Helpers\AccessUrlHelper;
use Chamilo\CoreBundle\Helpers\CourseHelper;
use Database;
use DateTimeImmutable;
use DateTimeZone;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use Throwable;

/**
 * Final, opt-in retention stage for courses created under BuyCourses subscriptions.
 * Existing closing, hiding and reactivation are handled by the recurring processor.
 */
final class BuyCoursesCourseDeletionService
{
    private const int MAX_DELETIONS_PER_RUN = 25;
    private const int HIDDEN_VISIBILITY = 4;

    public function __construct(
        private readonly Connection $connection,
        private readonly EntityManagerInterface $entityManager,
        private readonly CourseHelper $courseHelper,
        private readonly AccessUrlHelper $accessUrlHelper,
    ) {}

    /**
     * @return array<int, array<string, mixed>>
     */
    public function preview(?int $subscriptionCourseId = null, ?int $courseId = null): array
    {
        $now = new DateTimeImmutable('now', new DateTimeZone('UTC'));
        $subscriptionCourseTable = Database::get_main_table('plugin_buycourses_subscription_course');
        $serviceSaleTable = Database::get_main_table('plugin_buycourses_service_sale');
        $serviceTable = Database::get_main_table('plugin_buycourses_services');
        $courseTable = Database::get_main_table('course');
        $sessionCourseTable = Database::get_main_table('session_rel_course');

        $sql = "SELECT sc.id AS subscription_course_id, sc.course_id, sc.service_sale_id,
                       sc.service_id, sc.user_id, sc.hidden_at,
                       ss.buyer_id, ss.date_end, ss.recurring_payment,
                       s.subscription_behavior_json, c.code AS course_code,
                       c.title AS course_title, c.visibility AS course_visibility,
                       EXISTS (
                           SELECT 1 FROM $serviceSaleTable active_sale
                           WHERE active_sale.buyer_id = ss.buyer_id
                             AND active_sale.service_id = ss.service_id
                             AND active_sale.status = :activeCompletedStatus
                             AND active_sale.id <> ss.id
                             AND (active_sale.date_start IS NULL OR active_sale.date_start <= :activeAt)
                             AND active_sale.date_end >= :validUntil
                       ) AS has_other_active_sale,
                       EXISTS (
                           SELECT 1 FROM $sessionCourseTable session_course
                           WHERE session_course.c_id = sc.course_id
                       ) AS has_session_course
                FROM $subscriptionCourseTable sc
                INNER JOIN $serviceSaleTable ss ON ss.id = sc.service_sale_id
                INNER JOIN $serviceTable s ON s.id = sc.service_id AND s.id = ss.service_id
                INNER JOIN $courseTable c ON c.id = sc.course_id
                WHERE sc.status = :hiddenStatus
                  AND sc.hidden_at IS NOT NULL
                  AND sc.deleted_at IS NULL
                  AND ss.status = :expiredCompletedStatus
                  AND s.renewable = 1
                  AND ss.recurring_payment IN (:recurringEnabled, :recurringSuspended, :recurringCancelled)
                  AND ss.date_end < :expiredAt"
            .(null === $subscriptionCourseId ? '' : ' AND sc.id = :subscriptionCourseId')
            .(null === $courseId ? '' : ' AND sc.course_id = :courseId')
            .' ORDER BY sc.hidden_at ASC, sc.id ASC';

        $params = [
            'activeCompletedStatus' => 1,
            'expiredCompletedStatus' => 1,
            'hiddenStatus' => 'hidden',
            'recurringEnabled' => 1,
            'recurringSuspended' => 2,
            'recurringCancelled' => -1,
            'activeAt' => $now->format('Y-m-d H:i:s'),
            'validUntil' => $now->format('Y-m-d H:i:s'),
            'expiredAt' => $now->format('Y-m-d H:i:s'),
        ];
        if (null !== $subscriptionCourseId) {
            $params['subscriptionCourseId'] = $subscriptionCourseId;
        }
        if (null !== $courseId) {
            $params['courseId'] = $courseId;
        }

        $rows = $this->connection->fetchAllAssociative($sql, $params);

        $multiUrl = $this->accessUrlHelper->isMultiple();
        foreach ($rows as &$row) {
            $row['decision'] = self::decisionForRow($row, $now, $multiUrl);
        }
        unset($row);

        return $rows;
    }

    /**
     * @param array<string, mixed> $row
     *
     * @return array{action: string, reason: string, due_at: ?string}
     */
    public static function decisionForRow(array $row, DateTimeImmutable $now, bool $multiUrl = false): array
    {
        $behavior = json_decode((string) ($row['subscription_behavior_json'] ?? ''), true);
        $days = \is_array($behavior) ? ($behavior['delete_after_days'] ?? null) : null;

        if (!\is_int($days) || $days < 1 || $days > 36500) {
            return ['action' => 'disabled', 'reason' => 'Deletion requires a positive integer delete_after_days.', 'due_at' => null];
        }

        try {
            if ('' === trim((string) ($row['hidden_at'] ?? ''))) {
                throw new InvalidArgumentException('Missing hidden_at.');
            }
            $hiddenAt = new DateTimeImmutable((string) $row['hidden_at'], new DateTimeZone('UTC'));
            $dueAt = $hiddenAt->modify('+'.$days.' days');
        } catch (Throwable) {
            return ['action' => 'blocked', 'reason' => 'Invalid hidden_at timestamp.', 'due_at' => null];
        }

        $due = $dueAt->format('Y-m-d H:i:s');
        if ($dueAt > $now) {
            return ['action' => 'pending', 'reason' => 'Retention period has not ended.', 'due_at' => $due];
        }

        if ($multiUrl) {
            return ['action' => 'blocked', 'reason' => 'Automatic deletion is disabled on multi-URL portals.', 'due_at' => $due];
        }

        if ((int) ($row['user_id'] ?? 0) <= 0
            || (int) ($row['user_id'] ?? 0) !== (int) ($row['buyer_id'] ?? 0)
            || (int) ($row['course_id'] ?? 0) <= 0
            || self::HIDDEN_VISIBILITY !== (int) ($row['course_visibility'] ?? -1)
        ) {
            return ['action' => 'blocked', 'reason' => 'The subscription owner or course visibility no longer matches.', 'due_at' => $due];
        }

        if ((bool) ($row['has_other_active_sale'] ?? false)) {
            return ['action' => 'blocked', 'reason' => 'The buyer has another active sale of this service.', 'due_at' => $due];
        }

        if ((bool) ($row['has_session_course'] ?? false)) {
            return ['action' => 'blocked', 'reason' => 'The course is used by a session.', 'due_at' => $due];
        }

        // A delayed payment may renew an enabled or suspended subscription.
        // Only explicitly cancelled renewals can enter permanent deletion.
        if (-1 !== (int) ($row['recurring_payment'] ?? 0)) {
            return ['action' => 'blocked', 'reason' => 'Recurring renewal must be cancelled before permanent deletion.', 'due_at' => $due];
        }

        return ['action' => 'delete', 'reason' => 'Retention period completed.', 'due_at' => $due];
    }

    /**
     * @return array{eligible: int, deleted: int, errors: int, messages: string[]}
     */
    public function process(bool $dryRun, bool $allowDeletion = false, int $limit = self::MAX_DELETIONS_PER_RUN, ?int $courseId = null): array
    {
        $limit = max(1, min(self::MAX_DELETIONS_PER_RUN, $limit));
        $entries = $this->preview(null, $courseId);
        $eligible = \count(array_filter($entries, static fn (array $row): bool => 'delete' === $row['decision']['action']));
        $deleted = 0;
        $errors = 0;
        $messages = [];

        foreach ($entries as $row) {
            $decision = $row['decision'];
            if ('delete' !== $decision['action']) {
                if ('blocked' === $decision['action']) {
                    $messages[] = \sprintf(
                        'SKIP course_id=%d service_sale_id=%d reason=%s',
                        (int) $row['course_id'],
                        (int) $row['service_sale_id'],
                        $decision['reason']
                    );
                }

                continue;
            }

            $courseId = (int) $row['course_id'];
            $saleId = (int) $row['service_sale_id'];

            if ($dryRun || !$allowDeletion) {
                $messages[] = \sprintf(
                    'PLAN delete course_id=%d service_sale_id=%d course_code=%s due_at=%s',
                    $courseId,
                    $saleId,
                    $row['course_code'],
                    $decision['due_at']
                );

                continue;
            }

            if ($deleted >= $limit) {
                $messages[] = 'PENDING deletion batch limit reached; remaining courses will be handled by the next run.';

                break;
            }

            try {
                // Re-read the current subscription and active sales immediately before deletion.
                $current = null;
                foreach ($this->preview((int) $row['subscription_course_id']) as $latest) {
                    if ((int) $latest['subscription_course_id'] === (int) $row['subscription_course_id']) {
                        $current = $latest;

                        break;
                    }
                }

                if (null === $current || 'delete' !== $current['decision']['action']) {
                    $messages[] = \sprintf('SKIP course_id=%d subscription changed before deletion.', $courseId);

                    continue;
                }

                $course = $this->entityManager->getRepository(Course::class)->find($courseId);
                if (!$course instanceof Course) {
                    $messages[] = \sprintf('SKIP course_id=%d course no longer exists.', $courseId);

                    continue;
                }

                if (!$this->courseHelper->deleteCourse($course)) {
                    ++$errors;
                    $messages[] = \sprintf('ERROR course_id=%d course could not be fully deleted.', $courseId);

                    continue;
                }

                // Keep the purchase history even if a plugin event subscriber was not loaded.
                $this->connection->update(
                    Database::get_main_table('plugin_buycourses_subscription_course'),
                    [
                        'status' => 'deleted',
                        'deleted_at' => $this->connection->fetchOne('SELECT UTC_TIMESTAMP()'),
                        'updated_at' => $this->connection->fetchOne('SELECT UTC_TIMESTAMP()'),
                        'last_action' => 'course_deleted',
                    ],
                    ['id' => (int) $row['subscription_course_id']]
                );

                ++$deleted;
                $messages[] = \sprintf('DELETED course_id=%d service_sale_id=%d', $courseId, $saleId);
            } catch (Throwable $exception) {
                ++$errors;
                $messages[] = \sprintf('ERROR course_id=%d %s', $courseId, $exception->getMessage());
            }
        }

        return compact('eligible', 'deleted', 'errors', 'messages');
    }
}
