<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Mobile;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Post;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\ApiResource\Mobile\MobileMessage;
use Chamilo\CoreBundle\ApiResource\Mobile\MobileMessageStarInput;
use Chamilo\CoreBundle\Entity\Message;
use Chamilo\CoreBundle\Helpers\UserHelper;
use Chamilo\CoreBundle\Repository\MessageRepository;
use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @implements ProcessorInterface<MobileMessageStarInput, MobileMessage>
 */
final readonly class MobileMessageStarProcessor implements ProcessorInterface
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
        if (!$operation instanceof Post || !$data instanceof MobileMessageStarInput) {
            throw new LogicException('Unsupported mobile message star operation.');
        }

        $user = $this->userHelper->getCurrent();

        if (null === $user) {
            throw new AccessDeniedHttpException('An authenticated user is required.');
        }

        $message = $this->messageRepository->findMobileMessageForUser(
            (int) ($uriVariables['id'] ?? 0),
            $user
        );

        if (!$message instanceof Message) {
            throw new NotFoundHttpException('Message not found.');
        }

        $relation = MobileMessage::findUserRelation($message, $user);

        if (null === $relation) {
            throw new NotFoundHttpException('Message not found.');
        }

        $relation->setStarred($data->starred);
        $this->entityManager->flush();

        $resource = MobileMessage::fromMessage($message, $user, true);

        if (!$resource instanceof MobileMessage) {
            throw new NotFoundHttpException('Message not found.');
        }

        return $resource;
    }
}
