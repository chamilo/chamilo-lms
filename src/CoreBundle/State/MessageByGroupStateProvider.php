<?php

declare(strict_types=1);

namespace Chamilo\CoreBundle\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\Entity\Message;
use Chamilo\CoreBundle\Repository\MessageRepository;
use Chamilo\CoreBundle\Repository\Node\UsergroupRepository;
use Chamilo\CoreBundle\Security\Authorization\Voter\UsergroupVoter;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * @template-implements ProviderInterface<Message>
 */
final class MessageByGroupStateProvider implements ProviderInterface
{
    public function __construct(
        private readonly MessageRepository $messageRepository,
        private readonly UsergroupRepository $usergroupRepository,
        private readonly Security $security,
    ) {}

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

        $groupId = (int) $groupId;
        $usergroup = $this->usergroupRepository->find($groupId);

        if (null === $usergroup) {
            return [];
        }

        if (!$this->security->isGranted(UsergroupVoter::VIEW, $usergroup)) {
            throw new AccessDeniedHttpException();
        }

        return $this->messageRepository->getMessagesByGroup($groupId, true);
    }
}
