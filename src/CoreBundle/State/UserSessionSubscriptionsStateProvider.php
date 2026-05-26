<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\State;

use ApiPlatform\Doctrine\Orm\Extension\PaginationExtension;
use ApiPlatform\Doctrine\Orm\Paginator;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGenerator;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\Pagination\TraversablePaginator;
use ApiPlatform\State\ProviderInterface;
use ArrayIterator;
use Chamilo\CoreBundle\Entity\AccessUrl;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\AccessUrlHelper;
use Chamilo\CoreBundle\Helpers\UserHelper;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
use Chamilo\CoreBundle\Repository\SessionRepository;
use Exception;
use RuntimeException;
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
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array|object|null
    {
        /** @var User|null $user */
        $user = $this->userRepository->find($uriVariables['id'] ?? null);

        if (!$user) {
            throw new NotFoundHttpException('User not found');
        }

        $currentUser = $this->userHelper->getCurrent();

        $isAllowed = $user === $currentUser || ($currentUser && $currentUser->isAdmin());
        if (!$isAllowed) {
            throw new AccessDeniedException();
        }

        $url = $this->accessUrlHelper->getCurrent() ?? $this->accessUrlHelper->getFirstAccessUrl();
        if (!$url instanceof AccessUrl) {
            throw new RuntimeException('Access URL not found');
        }

        if ('user_session_subscriptions_past' === $operation->getName()) {
            $sessions = $this->sessionRepository->getPastSessionsOfUserInUrl($user, $url);

            // Ensure duration sessions have daysLeft for display consistency
            foreach ($sessions as $session) {
                $this->ensureDaysLeftHydrated($session, $user);
            }

            return $sessions;
        }

        if ('user_session_subscriptions_current' === $operation->getName()) {
            return $this->getCurrentSessionsPagedAndFiltered($context, $user, $url);
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
            foreach ($sessions as $session) {
                $this->ensureDaysLeftHydrated($session, $user);
            }

            return new TraversablePaginator(
                new ArrayIterator($sessions),
                (int) ($context['filters']['page'] ?? 1),
                (int) ($context['filters']['itemsPerPage'] ?? $context['pagination_items_per_page'] ?? 10),
                $paginator->getTotalItems()
            );
        }

        if (is_iterable($paginator)) {
            $sessions = \is_array($paginator) ? $paginator : iterator_to_array($paginator);
            foreach ($sessions as $session) {
                $this->ensureDaysLeftHydrated($session, $user);
            }

            return $sessions;
        }

        return [];
    }

    /**
     * We must filter expired duration sessions in PHP (depends on user and "daysLeft").
     * To keep pagination correct, we build the page slice AFTER filtering and return
     * a TraversablePaginator with the real total count.
     */
    private function getCurrentSessionsPagedAndFiltered(array $context, User $user, AccessUrl $url): TraversablePaginator
    {
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

        $wantedOffset = ($page - 1) * $itemsPerPage;
        $wantedEnd = $wantedOffset + $itemsPerPage;

        // Base query: current sessions candidates (includes duration sessions)
        $baseQb = $this->sessionRepository->getCurrentSessionsOfUserInUrl($user, $url);

        $scanOffset = 0;
        $scanSize = max($itemsPerPage * 5, 50);

        $pageItems = [];
        $totalAccepted = 0;

        while (true) {
            $qb = clone $baseQb;
            $qb->setFirstResult($scanOffset);
            $qb->setMaxResults($scanSize);

            /** @var Session[] $chunk */
            $chunk = $qb->getQuery()->getResult();
            if (empty($chunk)) {
                break;
            }

            foreach ($chunk as $session) {
                // Always hydrate daysLeft for duration sessions (for UI display)
                $this->ensureDaysLeftHydrated($session, $user);

                // Hide expired duration sessions for non-coaches
                if ($session->getDuration() > 0 && !$session->hasCoach($user)) {
                    $daysLeft = $session->getDaysLeft();

                    if (null !== $daysLeft && $daysLeft < 0) {
                        continue;
                    }
                }

                // Accepted session after filtering
                if ($totalAccepted >= $wantedOffset && $totalAccepted < $wantedEnd) {
                    $pageItems[] = $session;
                }

                $totalAccepted++;
            }

            $scanOffset += $scanSize;
        }

        return new TraversablePaginator(
            new ArrayIterator($pageItems),
            $page,
            $itemsPerPage,
            $totalAccepted
        );
    }

    /**
     * Ensures duration sessions have daysLeft for the given user.
     */
    private function ensureDaysLeftHydrated(Session $session, User $user): void
    {
        if ($session->getDuration() <= 0) {
            return;
        }

        if (null !== $session->getDaysLeft()) {
            return;
        }

        $session->setDaysLeft($session->getDaysLeftByUser($user));
    }
}
