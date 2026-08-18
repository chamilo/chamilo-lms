<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

use Chamilo\CoreBundle\Event\AbstractEvent;
use Chamilo\CoreBundle\Event\Events;
use Chamilo\CoreBundle\Event\SessionDeletedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class BbbSessionDeletedEventSubscriber implements EventSubscriberInterface
{
    private BbbPlugin $plugin;

    public function __construct()
    {
        $this->plugin = BbbPlugin::create();
    }

    public static function getSubscribedEvents(): array
    {
        return [
            Events::SESSION_DELETED => 'onSessionDeleted',
        ];
    }

    public function onSessionDeleted(SessionDeletedEvent $event): void
    {
        if (AbstractEvent::TYPE_PRE !== $event->getType()) {
            return;
        }

        $sessionId = $event->getSessionId();

        // Guarded on installed rather than isEnabled(): the meetings still exist
        // when the plugin is disabled, or enabled only on another access URL.
        if (empty($sessionId) || !AppPlugin::getInstance()->isInstalled($this->plugin->get_name())) {
            return;
        }

        $this->plugin->doWhenDeletingSession($sessionId);
    }
}
