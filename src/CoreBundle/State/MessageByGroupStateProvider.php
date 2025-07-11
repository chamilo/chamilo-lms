<?php

declare(strict_types=1);

namespace Chamilo\CoreBundle\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\Entity\Message;
use Chamilo\CoreBundle\Repository\MessageRepository;

/**
 * @template-implements ProviderInterface<Message>
 */
final class MessageByGroupStateProvider implements ProviderInterface
{
    private MessageRepository $messageRepository;

    public function __construct(MessageRepository $messageRepository)
    {
        $this->messageRepository = $messageRepository;
    }

    public function supports(Operation $operation, array $uriVariables = [], array $context = []): bool
    {
        return Message::class === $operation->getClass() && 'get_messages_by_group' === $operation->getName();
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        $groupId = $context['filters']['groupId'] ?? null;

        if (null === $groupId) {
            return [];
        }

        return $this->messageRepository->getMessagesByGroup((int) $groupId, true);
    }
}
