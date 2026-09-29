<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

use Chamilo\CoreBundle\Entity\ConferenceMeeting;
use Chamilo\CoreBundle\Entity\ConferenceRecording;
use Chamilo\CoreBundle\Event\AbstractEvent;
use Chamilo\CoreBundle\Event\CourseDeletedEvent;
use Chamilo\CoreBundle\Event\Events;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class BbbCourseDeletedEventSubscriber implements EventSubscriberInterface
{
    private BbbPlugin $plugin;

    public function __construct()
    {
        $this->plugin = BbbPlugin::create();
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

        // Guarded on installed rather than isEnabled(): the meetings still exist
        // when the plugin is disabled, or enabled only on another access URL.
        if (empty($courseId) || !AppPlugin::getInstance()->isInstalled($this->plugin->get_name())) {
            return;
        }

        if ('true' !== $this->plugin->get('delete_recordings_on_course_delete')) {
            return;
        }

        $em = Database::getManager();
        $recordingRepo = $em->getRepository(ConferenceRecording::class);

        $meetings = $em->getRepository(ConferenceMeeting::class)->findBy([
            'course' => $courseId,
            'serviceProvider' => 'bbb',
        ]);

        foreach ($meetings as $meeting) {
            $recordings = $recordingRepo->findBy([
                'meeting' => $meeting,
                'formatType' => 'bbb',
            ]);

            foreach ($recordings as $recording) {
                if ($recordId = $this->plugin->extractRecordId($recording->getResourceUrl())) {
                    $this->plugin->deleteRecording($recordId);
                }

                $em->remove($recording);
            }

            $em->remove($meeting);
        }

        $em->flush();
    }
}
