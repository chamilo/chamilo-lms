<?php
/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\PluginBundle\EmbedRegistry\EventSubscriber;

use Chamilo\CoreBundle\Event\AbstractEvent;
use Chamilo\CoreBundle\Event\CourseCreatedEvent;
use Chamilo\CoreBundle\Event\Events;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

readonly class EmbedRegistryEventSubscriber implements EventSubscriberInterface
{
    private \EmbedRegistryPlugin $plugin;

    public function __construct()
    {
        $this->plugin = \EmbedRegistryPlugin::create();
    }

    public static function getSubscribedEvents(): array
    {
        return [
            Events::COURSE_CREATED => 'onCourseCreated',
        ];
    }

    public function onCourseCreated(CourseCreatedEvent $event): void
    {
        if (!$this->plugin->isEnabled()) {
            return;
        }

        if (AbstractEvent::TYPE_POST !== $event->getType()) {
            return;
        }

        $course = $event->getCourse();

        if (null === $course) {
            return;
        }

        $this->plugin->ensureSchema();
        $this->plugin->addShortcutInCourse($course);
    }
}
