<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
use Chamilo\CoreBundle\Repository\SessionRepository;
use Chamilo\CoreBundle\Utils\AccessUrlUtil;
use Chamilo\CoreBundle\Utils\UserUtil;
use Exception;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * @template-implements ProviderInterface<Session>
 */
class UserSessionSubscriptionsStateProvider implements ProviderInterface
{
    public function __construct(
        private readonly UserUtil $userHelper,
        private readonly AccessUrlUtil $accessUrlUtil,
        private readonly UserRepository $userRepository,
        private readonly SessionRepository $sessionRepository,
    ) {}

    /**
     * @throws Exception
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        $user = $this->userRepository->find($uriVariables['id']);

        if (!$user) {
            throw new NotFoundHttpException('User not found');
        }

        $currentUser = $this->userHelper->getCurrent();
        $url = $this->accessUrlUtil->getCurrent();

        $isAllowed = $user === $currentUser || $currentUser->isAdmin();

        if (!$isAllowed) {
            throw new AccessDeniedException();
        }

        return match ($operation->getName()) {
            'user_session_subscriptions_past' => $this->sessionRepository->getPastSessionsOfUserInUrl($user, $url),
            'user_session_subscriptions_current' => $this->sessionRepository->getCurrentSessionsOfUserInUrl($user, $url),
            'user_session_subscriptions_upcoming' => $this->sessionRepository->getUpcomingSessionsOfUserInUrl($user, $url),
        };
    }
}
