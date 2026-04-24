<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\EventSubscriber;

use Chamilo\CoreBundle\Event\AbstractEvent;
use Chamilo\CoreBundle\Event\Events;
use Chamilo\CoreBundle\Event\UserDeletedEvent;
use Event;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class UserDeletedSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            Events::USER_DELETED => 'onUserDeleted',
        ];
    }

    public function onUserDeleted(UserDeletedEvent $event): void
    {
        if (AbstractEvent::TYPE_POST !== $event->getType() || !$event->isHardDelete()) {
            return;
        }

        $this->logUserDeletion($event);
        $this->logCreatorReassignment($event);
    }

    private function logUserDeletion(UserDeletedEvent $event): void
    {
        Event::addEvent(
            LOG_USER_DELETE,
            LOG_USER_OBJECT,
            $event->getUserInfo(),
            api_get_utc_datetime(),
            $event->getActorId()
        );
    }

    private function logCreatorReassignment(UserDeletedEvent $event): void
    {
        $affectedIds = $event->getAffectedCreatorIds();
        $fallbackId = $event->getFallbackId();
        $deletedUserId = $event->getUserId();

        if (empty($affectedIds) || null === $fallbackId || $fallbackId === $deletedUserId) {
            return;
        }

        $nowUtc = api_get_utc_datetime();
        $actorId = $event->getActorId();

        foreach ($affectedIds as $affectedId) {
            Event::addEvent(
                LOG_USER_CREATOR_DELETED,
                LOG_USER_ID,
                [
                    'user_id' => $affectedId,
                    'old_creator_id' => $deletedUserId,
                    'new_creator_id' => $fallbackId,
                ],
                $nowUtc,
                $actorId
            );
        }
    }
}
