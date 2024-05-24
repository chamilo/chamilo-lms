<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State;

use ApiPlatform\Metadata\DeleteOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Post;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\Entity\Message;
use Notification;

final class MessageProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly ProcessorInterface $persistProcessor,
        private readonly ProcessorInterface $removeProcessor,
    ) {}

    public function process($data, Operation $operation, array $uriVariables = [], array $context = [])
    {
        if ($operation instanceof DeleteOperationInterface) {
            return $this->removeProcessor->process($data, $operation, $uriVariables, $context);
        }

        $message = $this->persistProcessor->process($data, $operation, $uriVariables, $context);

        \assert($message instanceof Message);

        if ($operation instanceof Post) {
            if (Message::MESSAGE_TYPE_INBOX === $message->getMsgType()) {
                $this->saveNotificationForInboxMessage($message);
            }
        }

        return $message;
    }

    private function saveNotificationForInboxMessage(Message $message): void
    {
        $sender_info = api_get_user_info(
            $message->getSender()->getId()
        );

        foreach ($message->getReceivers() as $receiver) {
            $user = $receiver->getReceiver();

            (new Notification())
                ->saveNotification(
                    $message->getId(),
                    Notification::NOTIFICATION_TYPE_MESSAGE,
                    [$user->getId()],
                    $message->getTitle(),
                    $message->getContent(),
                    $sender_info,
                )
            ;
        }
    }
}
