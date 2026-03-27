<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

use Chamilo\CoreBundle\Event\Events;
use Chamilo\CoreBundle\Event\ExerciseReportActionEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ExerciseFocusedEventSubscriber implements EventSubscriberInterface
{
    private ExerciseFocusedPlugin $plugin;

    public function __construct()
    {
        $this->plugin = ExerciseFocusedPlugin::create();
    }

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents(): array
    {
        return [
            Events::EXERCISE_REPORT_ACTION => 'onExerciseReportAction',
        ];
    }

    public function onExerciseReportAction(ExerciseReportActionEvent $event): void
    {
        if (!$this->plugin->isEnabled(true)) {
            return;
        }

        $event->addAction(
            $this->plugin->getLinkReporting($event->getQuizId())
        );
    }
}
