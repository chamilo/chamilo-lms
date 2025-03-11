<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

use Chamilo\CoreBundle\Event\CourseCreatedEvent;
use Chamilo\CoreBundle\Event\AbstractEvent;
use Chamilo\CoreBundle\Event\Events;
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
            Events::COURSE_CREATED => 'onCreateCourse',
        ];
    }

    public function onCreateCourse(CourseCreatedEvent $event): void
    {
        $plugin = TopLinksPlugin::create();

        $linkRepo = $this->entityManager->getRepository(TopLink::class);

        $courseId = $event->getCourseInfo()['id'];

        if (AbstractEvent::TYPE_POST === $event->getType()) {
            foreach ($linkRepo->findAll() as $link) {
                $plugin->addToolInCourse($courseId, $link);
            }
        }
    }
}
