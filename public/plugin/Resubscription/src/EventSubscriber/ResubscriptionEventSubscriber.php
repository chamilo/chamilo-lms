<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

use Chamilo\CoreBundle\Event\AbstractEvent;
use Chamilo\CoreBundle\Event\Events;
use Chamilo\CoreBundle\Event\SessionResubscriptionEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ResubscriptionEventSubscriber implements EventSubscriberInterface
{
    private \Resubscription $plugin;

    public function __construct()
    {
        $this->plugin = \Resubscription::create();
    }

    public static function getSubscribedEvents(): array
    {
        return [
            Events::SESSION_RESUBSCRIPTION => 'onResubscribe',
        ];
    }

    /**
     * @throws Exception
     */
    public function onResubscribe(SessionResubscriptionEvent $event): void
    {
        if (AbstractEvent::TYPE_PRE !== $event->getType()) {
            return;
        }

        $sessionId = $event->getSessionId();
        $userId = $event->getUserId() ?? api_get_user_id();

        if (empty($sessionId) || empty($userId)) {
            return;
        }

        $this->plugin->assertUserCanResubscribe((int) $userId, (int) $sessionId);
    }
}
