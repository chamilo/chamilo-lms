<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

use Chamilo\CoreBundle\Event\AbstractEvent;
use Chamilo\CoreBundle\Event\CourseDeletedEvent;
use Chamilo\CoreBundle\Event\Events;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Marks the BuyCourses course relation as deleted when Chamilo removes a course.
 * The service sale is intentionally preserved as purchase history.
 */
final class BuyCoursesCourseDeletedEventSubscriber implements EventSubscriberInterface
{
    private BuyCoursesPlugin $plugin;

    public function __construct()
    {
        $this->plugin = BuyCoursesPlugin::create();
    }

    public static function getSubscribedEvents(): array
    {
        return [
            Events::COURSE_DELETED => 'onCourseDeleted',
        ];
    }

    public function onCourseDeleted(CourseDeletedEvent $event): void
    {
        if (AbstractEvent::TYPE_PRE !== $event->getType()) {
            return;
        }

        $courseId = $event->getCourseId();

        // Guarded on installed rather than isEnabled(): the purchase rows outlive
        // the plugin being disabled, or enabled only on another access URL.
        if (empty($courseId) || !AppPlugin::getInstance()->isInstalled($this->plugin->get_name())) {
            return;
        }

        if (!$this->plugin->hasSubscriptionCourseInfrastructure()) {
            return;
        }

        $now = api_get_utc_datetime();

        Database::update(
            Database::get_main_table(BuyCoursesPlugin::TABLE_SUBSCRIPTION_COURSE),
            [
                'status' => 'deleted',
                'updated_at' => $now,
                'deleted_at' => $now,
                'last_action' => 'course_deleted',
            ],
            ['course_id = ?' => [$courseId]]
        );

        $this->plugin->clearFrozenEnrollmentsForCourse($courseId);
    }
}
