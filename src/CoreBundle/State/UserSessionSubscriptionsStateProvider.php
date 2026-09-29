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
use Chamilo\CoreBundle\Entity\SessionRelCourse;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\AccessUrlHelper;
use Chamilo\CoreBundle\Helpers\CourseStudentInfoHelper;
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
        private readonly CourseStudentInfoHelper $courseStudentInfoHelper,
    ) {}

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     *
     * @return iterable<Session>|Session|null
     *
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

            foreach ($sessions as $session) {
                $this->hydrateSessionForUser($session, $user);
            }

            return $sessions;
        }

        if ('user_session_subscriptions_current' === $operation->getName()) {
            return $this->getCurrentSessionsPagedAndFiltered($context, $user, $url);
        }

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
                $this->hydrateSessionForUser($session, $user);
            }

            return new TraversablePaginator(
                new ArrayIterator($sessions),
                (int) ($context['filters']['page'] ?? 1),
                (int) ($context['filters']['itemsPerPage'] ?? $context['pagination_items_per_page'] ?? 10),
                $paginator->getTotalItems()
            );
        }

        if (is_iterable($paginator)) {
            /** @var array<int, Session> $sessions */
            $sessions = \is_array($paginator) ? $paginator : iterator_to_array($paginator);
            foreach ($sessions as $session) {
                $this->hydrateSessionForUser($session, $user);
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
                $this->ensureDaysLeftHydrated($session, $user);

                if ($session->getDuration() > 0 && !$session->hasCoach($user)) {
                    $daysLeft = $session->getDaysLeft();

                    if (null !== $daysLeft && $daysLeft < 0) {
                        continue;
                    }
                }

                if ($totalAccepted >= $wantedOffset && $totalAccepted < $wantedEnd) {
                    $this->hydrateSessionTracking($session, $user);
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

    private function hydrateSessionForUser(Session $session, User $user): void
    {
        $this->ensureDaysLeftHydrated($session, $user);
        $this->hydrateSessionTracking($session, $user);
    }

    private function hydrateSessionTracking(Session $session, User $user): void
    {
        $userId = (int) $user->getId();
        $sessionId = (int) $session->getId();
        if ($userId <= 0 || $sessionId <= 0) {
            return;
        }

        $courseIds = [];
        foreach ($session->getCourses() as $sessionCourse) {
            if (!$sessionCourse instanceof SessionRelCourse) {
                continue;
            }

            $courseId = (int) $sessionCourse->getCourse()->getId();
            if ($courseId > 0) {
                $courseIds[] = $courseId;
            }
        }

        $courseIds = array_values(array_unique($courseIds));
        if (empty($courseIds)) {
            return;
        }

        $statsByCourse = $this->courseStudentInfoHelper->getStudentInfoBatchForCourses(
            $userId,
            $courseIds,
            $sessionId
        );

        foreach ($session->getCourses() as $sessionCourse) {
            if (!$sessionCourse instanceof SessionRelCourse) {
                continue;
            }

            $courseId = (int) $sessionCourse->getCourse()->getId();
            $stats = $statsByCourse[(string) $courseId] ?? null;
            if (!\is_array($stats)) {
                continue;
            }

            $sessionCourse->setTrackingProgress(
                isset($stats['progress']) && is_numeric($stats['progress']) ? (float) $stats['progress'] : null
            );
            $sessionCourse->setScore(
                isset($stats['score']) && is_numeric($stats['score']) ? (float) $stats['score'] : null
            );
            $sessionCourse->setBestScore(
                isset($stats['bestScore']) && is_numeric($stats['bestScore']) ? (float) $stats['bestScore'] : null
            );
            $sessionCourse->setTimeSpentSeconds(
                isset($stats['timeSpentSeconds']) && is_numeric($stats['timeSpentSeconds'])
                    ? max(0, (int) $stats['timeSpentSeconds'])
                    : null
            );
            $sessionCourse->setCertificateAvailable(
                \array_key_exists('certificateAvailable', $stats) ? (bool) $stats['certificateAvailable'] : null
            );
            $sessionCourse->setCompleted(
                \array_key_exists('completed', $stats) ? (bool) $stats['completed'] : null
            );
        }
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
