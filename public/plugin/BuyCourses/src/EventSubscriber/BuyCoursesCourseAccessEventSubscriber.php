<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Event\CourseAccessCheckEvent;
use Chamilo\CoreBundle\Event\Events;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class BuyCoursesCourseAccessEventSubscriber implements EventSubscriberInterface
{
    private BuyCoursesPlugin $plugin;

    public function __construct()
    {
        $this->plugin = BuyCoursesPlugin::create();
    }

    public static function getSubscribedEvents(): array
    {
        return [
            Events::COURSE_ACCESS_CHECK => 'onCourseAccessCheck',
        ];
    }

    public function onCourseAccessCheck(CourseAccessCheckEvent $event): void
    {
        if (!$this->plugin->isEnabled(true)) {
            return;
        }

        $user = $event->getUser();
        $course = $event->getCourse();

        if (!$user || !$course) {
            return;
        }

        $isFrozen = $this->plugin->isFrozenEnrollment((int) $course->getId(), (int) $user->getId());
        if ($isFrozen) {
            $event->deny('Your access to this course is temporarily suspended. Please contact the course administrator.');
        }
    }
}
