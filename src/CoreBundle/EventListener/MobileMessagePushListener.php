<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\EventListener;

use Chamilo\CoreBundle\Entity\Message;
use Chamilo\CoreBundle\Helpers\AccessUrlHelper;
use Chamilo\CoreBundle\Message\MobileMessagePushRequested;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Events;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsDoctrineListener(event: Events::postPersist)]
#[AsDoctrineListener(event: Events::postFlush)]
final class MobileMessagePushListener
{
    /**
     * @var array<int, Message>
     */
    private array $pendingMessages = [];

    private bool $dispatching = false;

    public function __construct(
        private readonly MessageBusInterface $messageBus,
        private readonly AccessUrlHelper $accessUrlHelper,
    ) {}

    public function postPersist(PostPersistEventArgs $args): void
    {
        $message = $args->getObject();

        if (
            !$message instanceof Message
            || Message::MESSAGE_TYPE_INBOX !== $message->getMsgType()
        ) {
            return;
        }

        $messageId = $message->getId();
        if (null === $messageId) {
            return;
        }

        $this->pendingMessages[$messageId] = $message;
    }

    public function postFlush(PostFlushEventArgs $args): void
    {
        if ($this->dispatching || [] === $this->pendingMessages) {
            return;
        }

        $accessUrl = $this->accessUrlHelper->getCurrent();

        if (null === $accessUrl || null === $accessUrl->getId()) {
            $this->pendingMessages = [];

            return;
        }

        $messageIds = array_keys($this->pendingMessages);
        $this->pendingMessages = [];
        $this->dispatching = true;

        try {
            foreach ($messageIds as $messageId) {
                $this->messageBus->dispatch(
                    new MobileMessagePushRequested($messageId, (int) $accessUrl->getId())
                );
            }
        } finally {
            $this->dispatching = false;
        }
    }
}
