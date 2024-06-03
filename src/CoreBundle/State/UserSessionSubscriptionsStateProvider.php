<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
use Chamilo\CoreBundle\Repository\SessionRepository;
use Chamilo\CoreBundle\ServiceHelper\AccessUrlHelper;
use Chamilo\CoreBundle\ServiceHelper\UserHelper;
use Exception;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * @template-implements ProviderInterface<Session>
 */
class UserSessionSubscriptionsStateProvider implements ProviderInterface
{
    public function __construct(
        private readonly UserHelper $userHelper,
        private readonly AccessUrlHelper $accessUrlHelper,
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
        $url = $this->accessUrlHelper->getCurrent();

        $isAllowed = $user === $currentUser || $currentUser->isAdmin();

        if (!$isAllowed) {
            throw new AccessDeniedException();
        }

        $sessionList = match ($operation->getName()) {
            'user_session_subscriptions_past' => $this->sessionRepository->getPastSessionsWithDatesForUser($user, $url),
            'user_session_subscriptions_current' => $this->sessionRepository->getCurrentSessionsWithDatesForUser($user, $url),
            'user_session_subscriptions_upcoming' => $this->sessionRepository->getUpcomingSessionsWithDatesForUser($user, $url),
        };

        foreach ($sessionList as $session) {
            $session->checkAccessVisibilityByUser($user);
        }

        return $sessionList;
    }
}
