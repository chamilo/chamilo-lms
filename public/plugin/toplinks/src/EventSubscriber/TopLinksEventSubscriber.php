<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

use Chamilo\CoreBundle\HookEvent\CourseCreatedHookEvent;
use Chamilo\CoreBundle\HookEvent\HookEvent;
use Chamilo\CoreBundle\HookEvent\HookEvents;
use Chamilo\PluginBundle\Entity\TopLinks\TopLink;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class TopLinksEventSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            HookEvents::COURSE_CREATED => 'onCreateCourse',
        ];
    }

    public function onCreateCourse(CourseCreatedHookEvent $event): void
    {
        $plugin = TopLinksPlugin::create();

        $linkRepo = $this->entityManager->getRepository(TopLink::class);

        $courseId = $event->getCourseInfo()['id'];

        if (HookEvent::TYPE_POST === $event->getType()) {
            foreach ($linkRepo->findAll() as $link) {
                $plugin->addToolInCourse($courseId, $link);
            }
        }
    }
}
