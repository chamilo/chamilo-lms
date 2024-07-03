<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State;

use ApiPlatform\Metadata\DeleteOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Post;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\Entity\Message;
use Chamilo\CoreBundle\Entity\MessageAttachment;
use Chamilo\CoreBundle\Entity\MessageRelUser;
use Chamilo\CoreBundle\Repository\ResourceNodeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Notification;
use Vich\UploaderBundle\Storage\FlysystemStorage;

final class MessageProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly ProcessorInterface $persistProcessor,
        private readonly ProcessorInterface $removeProcessor,
        private readonly FlysystemStorage $storage,
        private readonly EntityManagerInterface $entityManager,
        private readonly ResourceNodeRepository $resourceNodeRepository,
    ) {}

    public function process($data, Operation $operation, array $uriVariables = [], array $context = [])
    {
        if ($operation instanceof DeleteOperationInterface) {
            return $this->removeProcessor->process($data, $operation, $uriVariables, $context);
        }

        /** @var Message $message */
        $message = $this->persistProcessor->process($data, $operation, $uriVariables, $context);

        foreach ($message->getAttachments() as $attachment) {
            $attachment->resourceNode->setResourceFile(
                $attachment->getResourceFileToAttach()
            );

            foreach ($message->getReceivers() as $receiver) {
                $attachment->addUserLink($receiver->getReceiver());
            }
        }

        $this->entityManager->flush();

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

        $userIdList = $message
            ->getReceivers()
            ->map(fn (MessageRelUser $messageRelUser): int => $messageRelUser->getReceiver()->getId())
            ->getValues()
        ;

        $attachmentList = [];

        /** @var MessageAttachment $messageAttachment */
        foreach ($message->getAttachments() as $messageAttachment) {
            $stream = $this->resourceNodeRepository->getResourceNodeFileStream(
                $messageAttachment->resourceNode
            );

            $attachmentList[] = [
                'stream' => $stream,
                'filename' => $messageAttachment->getFilename(),
            ];
        }

        (new Notification())
            ->saveNotification(
                $message->getId(),
                Notification::NOTIFICATION_TYPE_MESSAGE,
                $userIdList,
                $message->getTitle(),
                $message->getContent(),
                $sender_info,
                $attachmentList,
            )
        ;
    }
}
