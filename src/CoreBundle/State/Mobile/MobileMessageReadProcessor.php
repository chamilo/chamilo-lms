<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Mobile;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Post;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\ApiResource\Mobile\MobileMessage;
use Chamilo\CoreBundle\Entity\Message;
use Chamilo\CoreBundle\Helpers\UserHelper;
use Chamilo\CoreBundle\Repository\MessageRepository;
use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @implements ProcessorInterface<mixed, MobileMessage>
 */
final readonly class MobileMessageReadProcessor implements ProcessorInterface
{
    public function __construct(
        private MessageRepository $messageRepository,
        private EntityManagerInterface $entityManager,
        private UserHelper $userHelper,
    ) {}

    public function process(
        mixed $data,
        Operation $operation,
        array $uriVariables = [],
        array $context = []
    ): MobileMessage {
        if (!$operation instanceof Post) {
            throw new LogicException('Unsupported mobile message operation.');
        }

        $user = $this->userHelper->getCurrent();

        if (null === $user) {
            throw new AccessDeniedHttpException('An authenticated user is required.');
        }

        $messageId = (int) ($uriVariables['id'] ?? 0);
        $message = $this->messageRepository->findMobileMessageForUser($messageId, $user);

        if (!$message instanceof Message) {
            throw new NotFoundHttpException('Message not found.');
        }

        $relation = MobileMessage::findUserRelation($message, $user, 'inbox');

        if (null === $relation) {
            throw new NotFoundHttpException('Message not found.');
        }

        if (!$relation->isRead()) {
            $relation->setRead(true);
            $this->entityManager->flush();
        }

        $resource = MobileMessage::fromMessage($message, $user, true);

        if (!$resource instanceof MobileMessage) {
            throw new NotFoundHttpException('Message not found.');
        }

        return $resource;
    }
}
