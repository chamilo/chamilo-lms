<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\State;

use ApiPlatform\Doctrine\Orm\Extension\PaginationExtension;
use ApiPlatform\Doctrine\Orm\Paginator;
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
            $sessions = $this->sessionRepository->getPastSessionsOfUserInUrl($user, $url);
            $this->hydrateDaysLeft($sessions, $user);

            return $sessions;
        }

        if ('user_session_subscriptions_current' === $operation->getName()) {
            $sessions = $this->getCurrentSessionsPagedAndFiltered($operation, $context, $user, $url);
            $this->hydrateDaysLeft($sessions, $user);

            return $sessions;
        }

        // Upcoming can stay as a pure DB filter (duration sessions won't be upcoming anyway)
        $qb = $this->sessionRepository->getUpcomingSessionsOfUserInUrl($user, $url);

        $this->paginationExtension->applyToCollection(
            $qb,
            new QueryNameGenerator(),
            Session::class,
            $operation,
            $context
        );

        $paginator = $this->paginationExtension->getResult($qb, Session::class, $operation, $context);

        if ($paginator instanceof Paginator) {
            $sessions = iterator_to_array($paginator);
            $this->hydrateDaysLeft($sessions, $user);

            return $sessions;
        }

        if (is_iterable($paginator)) {
            $sessions = \is_array($paginator) ? $paginator : iterator_to_array($paginator);
            $this->hydrateDaysLeft($sessions, $user);

            return $sessions;
        }

        return [];
    }

    /**
     * Ensures pagination is not broken by filtering duration sessions in PHP.
     * We scan ahead until we fill the requested page size or we run out of DB results.
     *
     * @return Session[]
     */
    private function getCurrentSessionsPagedAndFiltered(
        Operation $operation,
        array $context,
                  $user,
                  $url
    ): array {
        $filters = $context['filters'] ?? [];

        $page = (int) ($filters['page'] ?? 1);
        if ($page < 1) {
            $page = 1;
        }

        $itemsPerPage = (int) (
            $filters['itemsPerPage']
            ?? $context['pagination_items_per_page']
            ?? 10
        );
        if ($itemsPerPage < 1) {
            $itemsPerPage = 10;
        }

        // Base query: current sessions by date rules (this currently includes duration sessions due to NULL dates).
        $baseQb = $this->sessionRepository->getCurrentSessionsOfUserInUrl($user, $url);

        $wantedOffset = ($page - 1) * $itemsPerPage;
        $scanOffset = $wantedOffset;

        // Scan chunks to compensate filtering
        $scanSize = max($itemsPerPage * 3, 30);

        $result = [];

        while (\count($result) < $itemsPerPage) {
            $qb = clone $baseQb;
            $qb->setFirstResult($scanOffset);
            $qb->setMaxResults($scanSize);

            $chunk = $qb->getQuery()->getResult();
            if (empty($chunk)) {
                break;
            }

            foreach ($chunk as $session) {
                // Duration sessions: decide current/past based on remaining days (students only).
                if ($session->getDuration() > 0 && !$session->hasCoach($user)) {
                    $daysLeft = $session->getDaysLeftByUser($user);
                    $session->setDaysLeft($daysLeft);

                    if ($daysLeft < 0) {
                        continue; // It's past for the student
                    }
                }

                $result[] = $session;

                if (\count($result) >= $itemsPerPage) {
                    break;
                }
            }

            $scanOffset += $scanSize;
        }

        return $result;
    }

    /**
     * Adds daysLeft to duration sessions so the frontend can show remaining/expired properly.
     *
     * @param Session[] $sessions
     */
    private function hydrateDaysLeft(array $sessions, $user): void
    {
        foreach ($sessions as $session) {
            if ($session->getDuration() > 0 && !$session->hasCoach($user)) {
                $session->setDaysLeft($session->getDaysLeftByUser($user));
            }
        }
    }
}
