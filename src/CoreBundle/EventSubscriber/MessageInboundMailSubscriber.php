<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\EventSubscriber;

use Chamilo\CoreBundle\Event\AbstractEvent;
use Chamilo\CoreBundle\Event\Events;
use Chamilo\CoreBundle\Event\NotificationContentFormattedEvent;
use Chamilo\CoreBundle\Service\Message\MessageInboundMailService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final readonly class MessageInboundMailSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private MessageInboundMailService $inboundMailService,
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            Events::NOTIFICATION_CONTENT_FORMATTED => ['onNotificationContentFormatted', 1000],
        ];
    }

    public function onNotificationContentFormatted(NotificationContentFormattedEvent $event): void
    {
        if (AbstractEvent::TYPE_POST !== $event->getType()) {
            return;
        }

        $event->setContent(
            $this->inboundMailService->prependPreparedReplyMarker($event->getContent())
        );
    }
}
