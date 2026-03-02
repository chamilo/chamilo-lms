<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Controller\Api;

use Chamilo\CoreBundle\Entity\AccessUrl;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\SessionRelCourseRelUser;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\AccessUrlHelper;
use Chamilo\CoreBundle\Repository\SessionRepository;
use Chamilo\CourseBundle\Entity\CCalendarEvent;
use Chamilo\CourseBundle\Entity\CStudentPublication;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Throwable;

final class CalendarMyStudentsScheduleAction
{
    public function __construct(
        private readonly Security $security,
        private readonly AccessUrlHelper $accessUrlHelper,
        private readonly SessionRepository $sessionRepository,
        private readonly EntityManagerInterface $em,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        /** @var User|null $user */
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            return new JsonResponse([]);
        }

        $accessUrl = $this->accessUrlHelper->getCurrent();
        if (!$accessUrl instanceof AccessUrl) {
            return new JsonResponse([]);
        }

        $sid = $request->query->getInt('sid');

        // 1) No sid => return sessions where user is a tutor/coach (session coach OR course coach).
        if ($sid <= 0) {
            $sessions = $this->getTutorSessionsInAccessUrl($user, $accessUrl);

            return new JsonResponse(array_map(
                static fn (Session $s): array => [
                    'id' => (int) $s->getId(),
                    'name' => $s->getTitle(),
                ],
                $sessions
            ));
        }

        $session = $this->sessionRepository->find($sid);
        if (!$session instanceof Session) {
            return new JsonResponse([]);
        }

        // Must be a tutor/coach in the session (general coach OR course coach).
        if (!$this->isUserTutorInSession($user, $session)) {
            throw new AccessDeniedHttpException('Not allowed');
        }

        // Extra safety: ensure session is in current AccessUrl.
        if (!$this->isSessionInAccessUrl($session, $accessUrl)) {
            throw new AccessDeniedHttpException('Not allowed');
        }

        $start = $this->parseDateTime((string) $request->query->get('start', ''));
        $end = $this->parseDateTime((string) $request->query->get('end', ''));
        if (!$start || !$end) {
            return new JsonResponse([]);
        }

        // Exception rule: once tutor/coach in session => see ALL events in that session.
        $calendarEvents = $this->findCalendarEventsForSession($session, $start, $end);
        $assignmentEvents = $this->findAssignmentDeadlineEventsForSession($session, $start, $end);

        return new JsonResponse(array_values(array_merge($calendarEvents, $assignmentEvents)));
    }

    private function parseDateTime(string $value): ?DateTimeImmutable
    {
        $v = trim($value);
        if ('' === $v) {
            return null;
        }

        try {
            return new DateTimeImmutable($v);
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * @return Session[]
     */
    private function getTutorSessionsInAccessUrl(User $user, AccessUrl $accessUrl): array
    {
        // We gather sessions from two sources:
        // 1) sessions followed by user (common case)
        // 2) sessions where user is a course coach (even if not "followed")
        $byId = [];

        $followed = $this->sessionRepository
            ->getUserFollowedSessionsInAccessUrl($user, $accessUrl)
            ->getQuery()
            ->getResult()
        ;

        foreach ($followed as $s) {
            if ($s instanceof Session) {
                $byId[(int) $s->getId()] = $s;
            }
        }

        $courseCoachSessions = $this->findCourseCoachSessions($user);
        foreach ($courseCoachSessions as $s) {
            $byId[(int) $s->getId()] = $s;
        }

        // Keep only those in the current access URL and where user is tutor/coach.
        $out = [];
        foreach ($byId as $s) {
            if (!$this->isSessionInAccessUrl($s, $accessUrl)) {
                continue;
            }
            if (!$this->isUserTutorInSession($user, $s)) {
                continue;
            }
            $out[] = $s;
        }

        usort(
            $out,
            static fn (Session $a, Session $b): int => strcmp((string) $a->getTitle(), (string) $b->getTitle())
        );

        return $out;
    }

    private function isUserTutorInSession(User $user, Session $session): bool
    {
        // Session coach (general coach) - keep existing logic.
        if ($session->hasCoach($user)) {
            return true;
        }

        // Course coach inside the session (session_rel_course_rel_user.status = Session::COURSE_COACH)
        return $this->isUserCourseCoachInSession($user, $session);
    }

    /**
     * Returns sessions where the user is a COURSE coach.
     *
     * @return Session[]
     */
    private function findCourseCoachSessions(User $user): array
    {
        $qb = $this->em->createQueryBuilder();

        $qb
            ->select('DISTINCT s')
            ->from(Session::class, 's')
            ->innerJoin(
                SessionRelCourseRelUser::class,
                'scru',
                'WITH',
                'scru.session = s'
            )
            ->andWhere('scru.user = :user')
            ->andWhere('scru.status = :status')
            ->setParameter('user', $user->getId())
            ->setParameter('status', Session::COURSE_COACH)
        ;

        /** @var Session[] $sessions */
        return $qb->getQuery()->getResult();
    }

    private function isUserCourseCoachInSession(User $user, Session $session): bool
    {
        $qb = $this->em->createQueryBuilder();
        $qb
            ->select('COUNT(scru.id)')
            ->from(SessionRelCourseRelUser::class, 'scru')
            ->andWhere('scru.user = :user')
            ->andWhere('scru.session = :session')
            ->andWhere('scru.status = :status')
            ->setParameter('user', $user->getId())
            ->setParameter('session', $session->getId())
            ->setParameter('status', Session::COURSE_COACH)
        ;

        $count = (int) $qb->getQuery()->getSingleScalarResult();

        return $count > 0;
    }

    private function isSessionInAccessUrl(Session $session, AccessUrl $accessUrl): bool
    {
        foreach ($session->getUrls() as $rel) {
            $url = $rel->getUrl();
            if ($url && (int) $url->getId() === (int) $accessUrl->getId()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function findCalendarEventsForSession(Session $session, DateTimeImmutable $start, DateTimeImmutable $end): array
    {
        $qb = $this->em->createQueryBuilder();
        $qb
            ->select('DISTINCT e', 'c.title AS courseTitle')
            ->from(CCalendarEvent::class, 'e')
            ->innerJoin('e.resourceNode', 'rn')
            ->innerJoin('rn.resourceLinks', 'rl')
            ->leftJoin('rl.course', 'c')
            ->andWhere('rl.session = :session')
            ->andWhere('e.startDate IS NOT NULL')
            ->andWhere('(e.startDate < :end) AND (e.endDate IS NULL OR e.endDate > :start)')
            ->setParameter('session', $session->getId())
            ->setParameter('start', $start->format('Y-m-d H:i:s'))
            ->setParameter('end', $end->format('Y-m-d H:i:s'))
        ;

        /** @var array<int, mixed> $rows */
        $rows = $qb->getQuery()->getResult();

        // Deduplicate by iid because multiple resource links can create duplicates.
        $outById = [];

        foreach ($rows as $row) {
            /** @var CCalendarEvent|null $e */
            $e = \is_array($row) ? ($row[0] ?? null) : $row;
            if (!$e instanceof CCalendarEvent) {
                continue;
            }

            $courseTitle = \is_array($row) ? (($row['courseTitle'] ?? null) ?: null) : null;

            $iid = (string) $e->getIid();
            $key = 'ce-'.$iid;

            $startDt = $e->getStartDate();
            $endDt = $e->getEndDate();

            if (!isset($outById[$key])) {
                $outById[$key] = [
                    'id' => $key,
                    'title' => $e->getTitle(),
                    'start' => $startDt ? DateTimeImmutable::createFromInterface($startDt)->format(DateTimeImmutable::ATOM) : null,
                    'end' => $endDt ? DateTimeImmutable::createFromInterface($endDt)->format(DateTimeImmutable::ATOM) : null,
                    'allDay' => (bool) $e->isAllDay(),
                    'color' => $e->getColor() ?: null,
                    'extendedProps' => [
                        'objectType' => 'calendar_event',
                        'sessionId' => (int) $session->getId(),
                        'courseTitle' => $courseTitle,
                        'readOnly' => true,
                    ],
                ];
            } else {
                if ($courseTitle && empty($outById[$key]['extendedProps']['courseTitle'])) {
                    $outById[$key]['extendedProps']['courseTitle'] = $courseTitle;
                }
            }
        }

        return array_values($outById);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function findAssignmentDeadlineEventsForSession(Session $session, DateTimeImmutable $start, DateTimeImmutable $end): array
    {
        $qb = $this->em->createQueryBuilder();
        $qb
            ->select('DISTINCT p', 'c.title AS courseTitle')
            ->from(CStudentPublication::class, 'p')
            ->innerJoin('p.assignment', 'a')
            ->innerJoin('p.resourceNode', 'rn')
            ->innerJoin('rn.resourceLinks', 'rl')
            ->leftJoin('rl.course', 'c')
            ->andWhere('rl.session = :session')
            ->andWhere('(a.expiresOn IS NOT NULL OR a.endsOn IS NOT NULL)')
            ->andWhere('(
            (a.expiresOn IS NOT NULL AND a.expiresOn >= :start AND a.expiresOn < :end)
            OR
            (a.expiresOn IS NULL AND a.endsOn IS NOT NULL AND a.endsOn >= :start AND a.endsOn < :end)
        )')
            ->setParameter('session', $session->getId())
            ->setParameter('start', $start->format('Y-m-d H:i:s'))
            ->setParameter('end', $end->format('Y-m-d H:i:s'))
        ;

        /** @var array<int, mixed> $rows */
        $rows = $qb->getQuery()->getResult();

        $outById = [];

        foreach ($rows as $row) {
            /** @var CStudentPublication|null $p */
            $p = \is_array($row) ? ($row[0] ?? null) : $row;
            if (!$p instanceof CStudentPublication) {
                continue;
            }

            $courseTitle = \is_array($row) ? (($row['courseTitle'] ?? null) ?: null) : null;

            $assignment = $p->getAssignment();
            if (!$assignment) {
                continue;
            }

            $deadline = $assignment->getExpiresOn() ?: $assignment->getEndsOn();
            if (!$deadline) {
                continue;
            }

            $deadlineI = DateTimeImmutable::createFromInterface($deadline);
            $key = 'as-'.(string) $p->getIid();

            $outById[$key] = [
                'id' => $key,
                'title' => $p->getTitle(),
                'start' => $deadlineI->format('Y-m-d'),
                'end' => $deadlineI->format('Y-m-d'),
                'allDay' => true,
                'color' => 'rgba(255,140,0,0.9)',
                'extendedProps' => [
                    'objectType' => 'assignment',
                    'sessionId' => (int) $session->getId(),
                    'courseTitle' => $courseTitle,
                    'publicationId' => (int) $p->getIid(),
                    'readOnly' => true,
                ],
            ];
        }

        return array_values($outById);
    }
}
