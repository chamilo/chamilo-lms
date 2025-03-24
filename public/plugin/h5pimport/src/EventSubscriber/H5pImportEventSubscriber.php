<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

use Chamilo\CoreBundle\Event\CourseCreatedEvent;
use Chamilo\CoreBundle\Event\AbstractEvent;
use Chamilo\CoreBundle\Event\Events;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class H5pImportEventSubscriber implements EventSubscriberInterface
{
    private H5pImportPlugin $plugin;

    public function __construct()
    {
        $this->plugin = H5pImportPlugin::create();
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

        $course = $event->getCourse();

        if (AbstractEvent::TYPE_POST === $event->getType() && $course) {
            $this->plugin->addCourseTool($course->getId());
        }
    }
}
