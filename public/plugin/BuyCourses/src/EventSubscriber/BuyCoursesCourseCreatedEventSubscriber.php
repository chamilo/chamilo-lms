<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

use Chamilo\CoreBundle\Event\AbstractEvent;
use Chamilo\CoreBundle\Event\CourseCreatedEvent;
use Chamilo\CoreBundle\Event\Events;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\ParameterType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class BuyCoursesCourseCreatedEventSubscriber implements EventSubscriberInterface
{
    private BuyCoursesPlugin $plugin;
    private Connection $connection;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->plugin = BuyCoursesPlugin::create();
        $this->connection = $entityManager->getConnection();
    }

    public static function getSubscribedEvents(): array
    {
        return [
            Events::COURSE_CREATED => 'onCreateCourse',
        ];
    }

    public function onCreateCourse(CourseCreatedEvent $event): void
    {
        if (!$this->plugin->isEnabled(true)) {
            return;
        }

        if ('true' !== $this->plugin->get('include_services')) {
            return;
        }

        if (AbstractEvent::TYPE_POST !== $event->getType()) {
            return;
        }

        $course = $event->getCourse();
        $selectedServiceSaleId = $event->getBuyCoursesServiceSaleId();

        if (null === $course || null === $course->getId() || null === $selectedServiceSaleId) {
            return;
        }

        $userId = api_get_user_id();

        if ($userId <= 0 || api_is_platform_admin()) {
            return;
        }

        try {
            $selectionStatus = $this->plugin->getCourseCreationServiceSaleSelectionStatus(
                $userId,
                $selectedServiceSaleId
            );

            if (empty($selectionStatus['valid']) || empty($selectionStatus['sale']) || !is_array($selectionStatus['sale'])) {
                error_log(
                    '[BuyCourses][CourseCreated] Selected service sale is not valid for course registration. course_id='.
                    $course->getId().
                    ' user_id='.
                    $userId.
                    ' service_sale_id='.
                    $selectedServiceSaleId.
                    ' reason='.
                    (string) ($selectionStatus['reason'] ?? 'unknown')
                );

                return;
            }

            $courseId = (int) $course->getId();

            if ($this->courseIsAlreadyRegistered($courseId)) {
                return;
            }

            $this->registerCourseUnderSubscription(
                $courseId,
                $userId,
                $selectionStatus['sale'],
                is_array($selectionStatus['benefits'] ?? null) ? $selectionStatus['benefits'] : []
            );
        } catch (Throwable $exception) {
            error_log(
                '[BuyCourses][CourseCreated] Failed to process selected BuyCourses service. course_id='.
                $course->getId().
                ' user_id='.
                $userId.
                ' service_sale_id='.
                $selectedServiceSaleId.
                ' error='.
                $exception->getMessage()
            );
        }
    }

    /**
     * @throws Exception
     */
    private function courseIsAlreadyRegistered(int $courseId): bool
    {
        if ($courseId <= 0) {
            return true;
        }

        $registeredId = $this->connection->fetchOne(
            '
                SELECT id
                FROM '.BuyCoursesPlugin::TABLE_SUBSCRIPTION_COURSE.'
                WHERE course_id = :course_id
                LIMIT 1
            ',
            [
                'course_id' => $courseId,
            ],
            [
                'course_id' => ParameterType::INTEGER,
            ]
        );

        return false !== $registeredId;
    }

    /**
     * @throws Exception
     */
    private function registerCourseUnderSubscription(int $courseId, int $userId, array $serviceSale, array $benefits = []): void
    {
        $serviceSaleId = (int) ($serviceSale['id'] ?? 0);
        $serviceId = (int) ($serviceSale['service_id'] ?? 0);

        if ($courseId <= 0 || $userId <= 0 || $serviceSaleId <= 0 || $serviceId <= 0) {
            return;
        }

        $now = api_get_utc_datetime();

        $context = [
            'service_sale_id' => $serviceSaleId,
            'service_id' => $serviceId,
            'user_id' => $userId,
            'course_id' => $courseId,
            'service_name' => (string) ($serviceSale['service_name'] ?? ''),
            'date_start' => (string) ($serviceSale['date_start'] ?? ''),
            'date_end' => (string) ($serviceSale['date_end'] ?? ''),
            'recurring_profile_id' => (string) ($serviceSale['recurring_profile_id'] ?? ''),
            'max_courses_with_benefits' => max(0, (int) ($benefits['maxCourses'] ?? 0)),
            'hosting_limit' => max(0, (int) ($benefits['hostingLimit'] ?? 0)),
            'document_quota_mb' => max(0, (int) ($benefits['documentQuotaMb'] ?? 0)),
            'status' => 'active',
            'created_at' => $now,
        ];

        $this->connection->insert(
            BuyCoursesPlugin::TABLE_SUBSCRIPTION_COURSE,
            [
                'service_sale_id' => $serviceSaleId,
                'service_id' => $serviceId,
                'course_id' => $courseId,
                'user_id' => $userId,
                'status' => 'active',
                'context_json' => json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'created_at' => $now,
                'updated_at' => $now,
                'last_action' => 'created',
            ],
            [
                'service_sale_id' => ParameterType::INTEGER,
                'service_id' => ParameterType::INTEGER,
                'course_id' => ParameterType::INTEGER,
                'user_id' => ParameterType::INTEGER,
                'status' => ParameterType::STRING,
                'context_json' => ParameterType::STRING,
                'created_at' => ParameterType::STRING,
                'updated_at' => ParameterType::STRING,
                'last_action' => ParameterType::STRING,
            ]
        );

        error_log(
            '[BuyCourses][CourseCreated] Course registered under selected service. course_id='.
            $courseId.
            ' user_id='.
            $userId.
            ' service_sale_id='.
            $serviceSaleId
        );
    }
}
