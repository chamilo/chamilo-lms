<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\ApiResource\SessionPlanItem;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\AccessUrlHelper;
use Chamilo\CoreBundle\Helpers\ThemeHelper;
use Chamilo\CoreBundle\Repository\SessionRepository;
use DateInterval;
use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * @template-implements ProviderInterface<Session>
 */
final class SessionPlanStateProvider implements ProviderInterface
{
    /**
     * This is a safety limit to avoid rendering huge grids.
     * It must be applied to the year-filtered result, not to all followed sessions.
     */
    private const MAX_SESSIONS = 50;

    public function __construct(
        private readonly Security $security,
        private readonly AccessUrlHelper $accessUrlHelper,
        private readonly SessionRepository $sessionRepository,
        private readonly RequestStack $requestStack,
        private readonly ThemeHelper $themeHelper,
    ) {}

    /**
     * @return array<SessionPlanItem>
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        /** @var User|null $user */
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            return [];
        }

        $request = $this->requestStack->getMainRequest();
        $year = $request?->query->getInt('year') ?: (int) (new DateTime())->format('Y');

        $accessUrl = $this->accessUrlHelper->getCurrent();

        // This returns all sessions followed by the user in the current access URL.
        $sessions = $this->sessionRepository
            ->getUserFollowedSessionsInAccessUrl($user, $accessUrl)
            ->getQuery()
            ->getResult()
        ;

        // Keep visibility filtering before building items.
        $sessions = array_values(array_filter(
            $sessions,
            fn (Session $s) => $this->isVisibleForUser($s, $user)
        ));

        $items = $this->buildPlanItems($sessions, $user, $year);

        // Sort by week start like C1 usort().
        usort($items, static fn (SessionPlanItem $a, SessionPlanItem $b): int => $a->start <=> $b->start);

        return $items;
    }

    private function isVisibleForUser(Session $session, User $user): bool
    {
        $visibility = $session->setAccessVisibilityByUser($user, true);

        if (Session::VISIBLE !== $visibility) {
            $closedOrHiddenCourses = $session->getClosedOrHiddenCourses();
            if ($closedOrHiddenCourses->count() === $session->getCourses()->count()) {
                $visibility = Session::INVISIBLE;
            }
        }

        return Session::INVISIBLE !== $visibility;
    }

    /**
     * @param Session[] $sessions
     *
     * @return SessionPlanItem[]
     */
    private function buildPlanItems(array $sessions, User $user, int $year): array
    {
        $items = [];

        // Colors palette size is bounded to the max rendered sessions.
        $paletteSize = max(1, min(\count($sessions), self::MAX_SESSIONS));
        $colors = $this->themeHelper->getColorPalette(false, true, $paletteSize);

        $tz = $this->getTimezone();
        $colorIndex = 0;

        foreach ($sessions as $session) {
            [$start, $end] = $this->resolveEffectiveDatesForUser($session, $user, $tz);

            // Duration sessions with no first access should be skipped
            if (!$start && ($session->getDuration() ?? 0) > 0) {
                continue;
            }

            if (!$this->isValidForYear($start, $end, $year)) {
                continue;
            }

            if (\count($items) >= self::MAX_SESSIONS) {
                throw new AccessDeniedHttpException('Too much sessions in planification');
            }

            $plan = $this->computeWeekPlan($start, $end, $year);

            $item = new SessionPlanItem();
            $item->id = (int) $session->getId();
            $item->title = $session->getTitle();

            $item->startDate = $start ? $start->format('Y-m-d') : null;
            $item->endDate = $end ? $end->format('Y-m-d') : null;

            $item->humanDate = $this->buildHumanDate($item->startDate, $item->endDate);

            $item->start = $plan['start'];
            $item->duration = $plan['duration'];

            $item->startInLastYear = $plan['start_in_last_year'];
            $item->endInNextYear = $plan['end_in_next_year'];
            $item->noStart = $plan['no_start'];
            $item->noEnd = $plan['no_end'];

            $item->color = $colors[$colorIndex % \count($colors)] ?? 'rgba(70,130,180,0.9)';
            $colorIndex++;

            $items[] = $item;
        }

        return $items;
    }

    private function getTimezone(): DateTimeZone
    {
        // Keep stable. If you later want user timezone, swap here.
        return new DateTimeZone(date_default_timezone_get() ?: 'UTC');
    }

    private function buildHumanDate(?string $start, ?string $end): ?string
    {
        if ($start && $end) {
            return $start.' → '.$end;
        }
        if ($start && !$end) {
            return $start.' → …';
        }
        if (!$start && $end) {
            return '… → '.$end;
        }

        return null;
    }

    /**
     * Effective dates:
     * - duration>0: based on user's first access date (+ duration + user extension duration)
     * - else: prefer subscription dates, fallback to session access/display dates
     *
     * @return array{0:?DateTimeImmutable,1:?DateTimeImmutable}
     */
    private function resolveEffectiveDatesForUser(Session $session, User $user, DateTimeZone $tz): array
    {
        // Duration session: use first access
        if (($session->getDuration() ?? 0) > 0) {
            $courseAccess = $user->getFirstAccessToSession($session);
            if (!$courseAccess) {
                return [null, null];
            }

            $firstAccess = DateTimeImmutable::createFromInterface($courseAccess->getLoginCourseDate())->setTimezone($tz);

            $durationDays = (int) ($session->getDuration() ?? 0);

            $subscription = $user->getSubscriptionToSession($session);
            if ($subscription) {
                $durationDays += (int) $subscription->getDuration();
            }

            $end = $firstAccess->add(new DateInterval('P'.$durationDays.'D'));

            return [$firstAccess, $end];
        }

        // Date-based: subscription dates first
        $subscription = $user->getSubscriptionToSession($session);

        $start = null;
        $end = null;

        if ($subscription) {
            $start = $subscription->getAccessStartDate();
            $end = $subscription->getAccessEndDate();
        }

        $start = $start ?: $session->getAccessStartDate() ?: $session->getDisplayStartDate();
        $end = $end ?: $session->getAccessEndDate() ?: $session->getDisplayEndDate();

        $startI = $start ? DateTimeImmutable::createFromInterface($start)->setTimezone($tz) : null;
        $endI = $end ? DateTimeImmutable::createFromInterface($end)->setTimezone($tz) : null;

        return [$startI, $endI];
    }

    private function isValidForYear(?DateTimeInterface $start, ?DateTimeInterface $end, int $year): bool
    {
        $startYear = (int) $start?->format('Y');
        $endYear = (int) $end?->format('Y');

        if ($startYear && $endYear) {
            return $startYear <= $year && $endYear >= $year;
        }

        if ($startYear && !$endYear) {
            return $startYear === $year;
        }

        if (!$startYear && $endYear) {
            return $endYear === $year;
        }

        return false;
    }

    /**
     * Week logic.
     *
     * @return array{start:int,duration:int,start_in_last_year:bool,end_in_next_year:bool,no_start:bool,no_end:bool}
     */
    private function computeWeekPlan(?DateTimeInterface $start, ?DateTimeInterface $end, int $year): array
    {
        $startYear = (int) $start?->format('Y');
        $startWeekYear = (int) $start?->format('o'); // ISO week-year
        $startWeek = (int) $start?->format('W');

        $endYear = (int) $end?->format('Y');
        $endWeekYear = (int) $end?->format('o');
        $endWeek = (int) $end?->format('W');

        $startIndex = ($startWeekYear < $year) ? 0 : ($startWeek - 1);
        if ($startIndex < 0) {
            $startIndex = 0;
        }
        if ($startIndex > 51) {
            $startIndex = 51;
        }

        $durationWeeks = ($endWeekYear > $year) ? (52 - $startIndex) : ($endWeek - $startIndex);
        if ($durationWeeks <= 0) {
            $durationWeeks = 1;
        }
        if ($durationWeeks > 52) {
            $durationWeeks = 52;
        }

        return [
            'start' => $startIndex,
            'duration' => $durationWeeks,
            'start_in_last_year' => $startYear < $year,
            'end_in_next_year' => $endYear > $year,
            'no_start' => !$startWeek,
            'no_end' => !$endWeek,
        ];
    }
}
