<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Mobile;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Post;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\ApiResource\Mobile\MobileMessage;
use Chamilo\CoreBundle\ApiResource\Mobile\MobileMessageWriteInput;
use Chamilo\CoreBundle\Entity\Message;
use Chamilo\CoreBundle\Entity\MessageRelUser;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\UserHelper;
use Chamilo\CoreBundle\Repository\MessageRepository;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use Notification;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

use const ENT_QUOTES;
use const ENT_SUBSTITUTE;

/**
 * @implements ProcessorInterface<MobileMessageWriteInput, MobileMessage>
 */
final readonly class MobileMessageSendProcessor implements ProcessorInterface
{
    private const int RECIPIENT_CHECK_LIMIT = 100;

    public function __construct(
        private MessageRepository $messageRepository,
        private UserRepository $userRepository,
        private EntityManagerInterface $entityManager,
        private UserHelper $userHelper,
    ) {}

    public function process(
        mixed $data,
        Operation $operation,
        array $uriVariables = [],
        array $context = []
    ): MobileMessage {
        if (!$operation instanceof Post || !$data instanceof MobileMessageWriteInput) {
            throw new LogicException('Unsupported mobile message send operation.');
        }

        $sender = $this->userHelper->getCurrent();

        if (null === $sender || null === $sender->getId()) {
            throw new AccessDeniedHttpException('An authenticated user is required.');
        }

        $recipient = $this->resolveAllowedRecipient($sender, $data->recipientId);
        $parent = $this->resolveParent($sender, $recipient, $data->parentId);
        $title = trim(strip_tags($data->title));
        $plainContent = trim($data->content);

        if ('' === $title || '' === $plainContent) {
            throw new UnprocessableEntityHttpException('Message subject and content are required.');
        }

        $message = (new Message())
            ->setSender($sender)
            ->addReceiverTo($recipient)
            ->setTitle($title)
            ->setContent(
                nl2br(
                    htmlspecialchars(
                        $plainContent,
                        ENT_QUOTES | ENT_SUBSTITUTE,
                        'UTF-8'
                    )
                )
            )
            ->setParent($parent)
        ;

        $senderRelation = (new MessageRelUser())
            ->setReceiver($sender)
            ->setReceiverType(MessageRelUser::TYPE_SENDER)
        ;
        $message->addReceiver($senderRelation);

        $this->entityManager->persist($message);
        $this->entityManager->flush();

        $this->notifyRecipient($message, $recipient);

        $resource = MobileMessage::fromMessage($message, $sender, true, 'sent');

        if (!$resource instanceof MobileMessage) {
            throw new LogicException('The sent message could not be serialized.');
        }

        return $resource;
    }

    private function resolveAllowedRecipient(User $sender, int $recipientId): User
    {
        $recipient = $this->userRepository->find($recipientId);

        if (
            !$recipient instanceof User
            || null === $recipient->getId()
            || $recipient->getId() === $sender->getId()
            || !$recipient->isActive()
        ) {
            throw new NotFoundHttpException('Recipient not found.');
        }

        foreach (
            $this->userRepository->findUsersToSendMessage(
                (int) $sender->getId(),
                $recipient->getUsername(),
                self::RECIPIENT_CHECK_LIMIT
            ) as $allowedRecipient
        ) {
            if ($allowedRecipient->getId() === $recipient->getId()) {
                return $recipient;
            }
        }

        throw new AccessDeniedHttpException('Messages cannot be sent to this recipient.');
    }

    private function resolveParent(User $sender, User $recipient, ?int $parentId): ?Message
    {
        if (null === $parentId) {
            return null;
        }

        $parent = $this->messageRepository->findMobileMessageForUser($parentId, $sender);

        if (!$parent instanceof Message || !$this->isParticipant($parent, $recipient)) {
            throw new NotFoundHttpException('Parent message not found.');
        }

        return $parent;
    }

    private function isParticipant(Message $message, User $user): bool
    {
        if ($message->getSender()?->getId() === $user->getId()) {
            return true;
        }

        foreach ($message->getReceivers() as $relation) {
            if (
                MessageRelUser::TYPE_SENDER !== $relation->getReceiverType()
                && $relation->getReceiver()->getId() === $user->getId()
            ) {
                return true;
            }
        }

        return false;
    }

    private function notifyRecipient(Message $message, User $recipient): void
    {
        if (null === $message->getId() || null === $message->getSender()) {
            return;
        }

        $senderInfo = api_get_user_info((int) $message->getSender()->getId());

        (new Notification())->saveNotification(
            $message->getId(),
            Notification::NOTIFICATION_TYPE_MESSAGE,
            [(int) $recipient->getId()],
            $message->getTitle(),
            $message->getContent(),
            $senderInfo,
            [],
        );
    }
}
