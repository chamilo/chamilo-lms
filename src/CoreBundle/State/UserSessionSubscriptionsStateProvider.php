<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\State;

use ApiPlatform\Doctrine\Orm\Extension\PaginationExtension;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGenerator;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Helpers\AccessUrlHelper;
use Chamilo\CoreBundle\Helpers\UserHelper;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
use Chamilo\CoreBundle\Repository\SessionRepository;
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
        private readonly PaginationExtension $paginationExtension,
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

        $isAllowed = $user === $currentUser || ($currentUser && $currentUser->isAdmin());

        if (!$isAllowed) {
            throw new AccessDeniedException();
        }

        if ('user_session_subscriptions_past' === $operation->getName()) {
            return $this->sessionRepository->getPastSessionsOfUserInUrl($user, $url);
        }

        $qb = match ($operation->getName()) {
            'user_session_subscriptions_current' => $this->sessionRepository->getCurrentSessionsOfUserInUrl(
                $user,
                $url
            ),
            'user_session_subscriptions_upcoming' => $this->sessionRepository->getUpcomingSessionsOfUserInUrl(
                $user,
                $url
            ),
        };

        $this->paginationExtension->applyToCollection(
            $qb,
            new QueryNameGenerator(),
            Session::class,
            $operation,
            $context
        );

        return $this->paginationExtension->getResult($qb, Session::class, $operation, $context);
    }
}
