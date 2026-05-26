<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

use Chamilo\CoreBundle\Event\CourseUserSubscriptionCheckEvent;
use Chamilo\CoreBundle\Event\Events;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class BuyCoursesCourseUserSubscriptionEventSubscriber implements EventSubscriberInterface
{
    private BuyCoursesPlugin $plugin;

    public function __construct()
    {
        $this->plugin = BuyCoursesPlugin::create();
    }

    public static function getSubscribedEvents(): array
    {
        return [
            Events::COURSE_USER_SUBSCRIPTION_CHECK => 'onCourseUserSubscriptionCheck',
        ];
    }

    public function onCourseUserSubscriptionCheck(CourseUserSubscriptionCheckEvent $event): void
    {
        if (!$this->plugin->isEnabled(true)) {
            return;
        }

        if (api_is_platform_admin()) {
            return;
        }

        if (STUDENT !== $event->getStatus()) {
            return;
        }

        $courseId = $event->getCourseId();
        $userIds = $event->getUserIds();

        if ($courseId <= 0 || empty($userIds)) {
            return;
        }

        if (!$this->plugin->wouldCourseUserSubscriptionExceedHostingLimit($courseId, $userIds)) {
            return;
        }

        $event->deny($this->plugin->getUsersPerCourseLimitMessage($courseId));
    }
}
