<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

use Chamilo\CoreBundle\Event\CourseCreatedEvent;
use Chamilo\CoreBundle\Event\AbstractEvent;
use Chamilo\CoreBundle\Event\Events;
use Chamilo\PluginBundle\TopLinks\Entity\TopLink;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

readonly class TopLinksEventSubscriber implements EventSubscriberInterface
{
    private TopLinksPlugin $plugin;

    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
        $this->plugin = TopLinksPlugin::create();
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

        $linkRepo = $this->entityManager->getRepository(TopLink::class);

        $course = $event->getCourse();

        if (AbstractEvent::TYPE_POST === $event->getType() && $course) {
            foreach ($linkRepo->findAll() as $link) {
                $this->plugin->addToolInCourse($course->getId(), $link);
            }
        }
    }
}
