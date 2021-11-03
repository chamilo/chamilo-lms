<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity\Listener;

use Chamilo\CoreBundle\Entity\Message;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Symfony\Component\Messenger\MessageBusInterface;

class MessageListener
{
    private MessageBusInterface $bus;

    public function __construct(MessageBusInterface $bus)
    {
        $this->bus = $bus;
    }

    public function postPersist(Message $message, LifecycleEventArgs $args): void
    {
        if ($message) {
            // Creates an outbox version, if message is sent in the inbox.
            if (Message::MESSAGE_TYPE_INBOX === $message->getMsgType()) {
                /*$messageSent = clone $message;
                $messageSent
                    ->setMsgType(Message::MESSAGE_TYPE_OUTBOX)
                    ->setRead(true)
                    ->setReceivers(null)
                ;
                $args->getEntityManager()->persist($messageSent);
                $args->getEntityManager()->flush();*/

                // Dispatch to the Messenger bus. Function MessageHandler.php will send the message.
                $this->bus->dispatch($message);
            }
        }
    }
}
