<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
use Chamilo\CoreBundle\Repository\SessionRepository;
use Chamilo\CoreBundle\ServiceHelper\AccessUrlHelper;
use Chamilo\CoreBundle\ServiceHelper\UserHelper;
use Exception;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

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
    public function provide(Operation $operation, array $uriVariables = [], array $context = [])
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

        $qb = match ($operation->getName()) {
            'user_session_subscriptions_past' => $this->sessionRepository->getPastSessionsByUser($user, $url),
            'user_session_subscriptions_current' => $this->sessionRepository->getCurrentSessionsByUser($user, $url),
            'user_session_subscriptions_upcoming' => $this->sessionRepository->getUpcomingSessionsByUser($user, $url),
        };

        return $qb->getQuery()->getResult();
    }
}
