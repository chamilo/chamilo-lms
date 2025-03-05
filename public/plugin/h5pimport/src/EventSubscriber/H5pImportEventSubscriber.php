<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

use Chamilo\CoreBundle\HookEvent\CourseCreatedHookEvent;
use Chamilo\CoreBundle\HookEvent\HookEvent;
use Chamilo\CoreBundle\HookEvent\HookEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class H5pImportEventSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            HookEvents::COURSE_CREATED => 'onCreateCourse',
        ];
    }

    public function onCreateCourse(CourseCreatedHookEvent $event): void
    {
        if (HookEvent::TYPE_POST === $event->getType()) {
            H5pImportPlugin::create()
                ->addCourseTool($event->getCourseInfo()['id'])
            ;
        }
    }
}
