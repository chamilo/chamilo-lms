<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State;

use ApiPlatform\Metadata\DeleteOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\Entity\Message;
use Chamilo\CoreBundle\Entity\MessageAttachment;
use Chamilo\CoreBundle\Entity\MessageRelUser;
use Chamilo\CoreBundle\Repository\ResourceNodeRepository;
use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use Notification;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Vich\UploaderBundle\Storage\FlysystemStorage;

final class MessageProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly ProcessorInterface $persistProcessor,
        private readonly ProcessorInterface $removeProcessor,
        private readonly FlysystemStorage $storage,
        private readonly EntityManagerInterface $entityManager,
        private readonly ResourceNodeRepository $resourceNodeRepository,
        private readonly Security $security,
        private readonly RequestStack $requestStack
    ) {}

    public function process($data, Operation $operation, array $uriVariables = [], array $context = [])
    {
        if ($operation instanceof DeleteOperationInterface) {
            return $this->removeProcessor->process($data, $operation, $uriVariables, $context);
        }

        /** @var Message $message */
        $message = $this->persistProcessor->process($data, $operation, $uriVariables, $context);

        foreach ($message->getAttachments() as $attachment) {
            $attachment->resourceNode->addResourceFile(
                $attachment->getResourceFileToAttach()
            );

            foreach ($message->getReceivers() as $receiver) {
                $attachment->addUserLink($receiver->getReceiver());
            }
        }

        $user = $this->security->getUser();
        if (!$user) {
            throw new LogicException('User not found.');
        }

        // Check if the relationship already exists
        $messageRelUserRepository = $this->entityManager->getRepository(MessageRelUser::class);
        $existingRelation = $messageRelUserRepository->findOneBy([
            'message' => $message,
            'receiver' => $user,
            'receiverType' => MessageRelUser::TYPE_SENDER,
        ]);

        if (!$existingRelation) {
            $messageRelUser = new MessageRelUser();
            $messageRelUser->setMessage($message);
            $messageRelUser->setReceiver($user);
            $messageRelUser->setReceiverType(MessageRelUser::TYPE_SENDER);
            $this->entityManager->persist($messageRelUser);
        }

        if (Message::MESSAGE_TYPE_INBOX === $message->getMsgType()) {
            $this->saveNotificationForInboxMessage($message);
        }

        $this->entityManager->flush();

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
