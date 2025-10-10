<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Repository;

use Chamilo\CoreBundle\Entity\AccessUrl;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\SessionRelCourse;
use Chamilo\CoreBundle\Entity\SessionRelCourseRelUser;
use Chamilo\CoreBundle\Entity\SessionRelUser;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Settings\SettingsManager;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Exception;

/**
 * @author Julio Montoya <gugli100@gmail.com>
 */
class SessionRepository extends ServiceEntityRepository
{
    public function __construct(
        ManagerRegistry $registry,
        private readonly SettingsManager $settingsManager,
    ) {
        parent::__construct($registry, Session::class);
    }

    public function create(): ?Session
    {
        return new Session();
    }

    public function update(Session $session): void
    {
        $this->getEntityManager()->persist($session);
        $this->getEntityManager()->flush();
    }

    /**
     * @return array<SessionRelUser>
     */
    public function getUsersByAccessUrl(Session $session, AccessUrl $url, array $relationTypeList = []): array
    {
        if (0 === $session->getUsers()->count()) {
            return [];
        }

        $qb = $this->addSessionRelUserFilterByUrl($session, $url);
        $qb->orderBy('sru.relationType');

        if ($relationTypeList) {
            $qb->andWhere(
                $qb->expr()->in('sru.relationType', $relationTypeList)
            );
        }

        return $qb->getQuery()->getResult();
    }

    public function getSessionsByUser(User $user, AccessUrl $url): QueryBuilder
    {
        $qb = $this->createQueryBuilder('s');
        $qb
            ->innerJoin('s.users', 'sru')
            ->leftJoin('s.urls', 'urls')
            ->where($qb->expr()->eq('sru.user', ':user'))
            ->andWhere($qb->expr()->eq('urls.url', ':url'))
            ->setParameters([
                'user' => $user,
                'url' => $url,
            ])
            ->orderBy('s.category', 'ASC') // by default sort by category, display date, title and position
            ->addOrderBy('s.displayStartDate', 'ASC')
            ->addOrderBy('s.title', 'ASC')
            ->addOrderBy('s.position', 'ASC')
        ;

        return $qb;
    }

    public function getSessionsByCourse(Course $course): array
    {
        $qb = $this->createQueryBuilder('s');

        return $qb
            ->innerJoin('s.courses', 'src')
            ->where($qb->expr()->eq('src.course', ':course'))
            ->setParameter('course', $course)
            ->getQuery()->getResult();
    }

    /**
     * @return array<int, Session>
     *
     * @throws Exception
     */
    public function getPastSessionsOfUserInUrl(User $user, AccessUrl $url): array
    {
        $sessions = $this->getSubscribedSessionsOfUserInUrl($user, $url);

        $filterPastSessions = function (Session $session) use ($user) {
            $now = new DateTime();
            // Determine if the user is a coach
            $userIsCoach = $session->hasCoach($user);

            // Check if the session has a duration
            if ($session->getDuration() > 0) {
                $daysLeft = $session->getDaysLeftByUser($user);
                $session->setTitle($session->getTitle().'<-'.$daysLeft);

                return $daysLeft < 0 && !$userIsCoach;
            }

            // Get the appropriate end date based on whether the user is a coach
            $sessionEndDate = $userIsCoach && $session->getCoachAccessEndDate()
                ? $session->getCoachAccessEndDate()
                : $session->getAccessEndDate();

            // If there's no end date, the session is not considered past
            if (!$sessionEndDate) {
                return false;
            }

            // Check if the current date is after the end date
            return $now > $sessionEndDate;
        };

        return array_filter($sessions, $filterPastSessions);
    }

    public function getCurrentSessionsOfUserInUrl(User $user, AccessUrl $url): QueryBuilder
    {
        $qb = $this->getSessionsByUser($user, $url);

        return $qb
            ->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->isNull('sru.accessStartDate'),
                    $qb->expr()->isNull('sru.accessEndDate'),
                    $qb->expr()->andX(
                        $qb->expr()->lte('sru.accessStartDate', ':now'),
                        $qb->expr()->gte('sru.accessEndDate', ':now'),
                    ),
                )
            )
            ->setParameter('now', new DateTime())
        ;
    }

    public function getUpcomingSessionsOfUserInUrl(User $user, AccessUrl $url): QueryBuilder
    {
        $qb = $this->getSessionsByUser($user, $url);

        return $qb
            ->andWhere(
                $qb->expr()->gt('sru.accessStartDate', ':now'),
            )
            ->setParameter('now', new DateTime())
        ;
    }

    public function addUserInCourse(int $relationType, User $user, Course $course, Session $session): void
    {
        if (!$user->isActive()) {
            throw new Exception('User not active');
        }

        if (!$session->hasCourse($course)) {
            $msg = \sprintf('Course %s is not subscribed to the session %s', $course->getTitle(), $session->getTitle());

            throw new Exception($msg);
        }

        if (!\in_array($relationType, Session::getRelationTypeList(), true)) {
            throw new Exception(\sprintf('Cannot handle relationType %s', $relationType));
        }

        $entityManager = $this->getEntityManager();
        $existingRecord = $entityManager->getRepository(SessionRelUser::class)->findOneBy([
            'session' => $session,
            'user' => $user,
            'relationType' => $relationType,
        ]);

        if ($existingRecord) {
            $entityManager->remove($existingRecord);
            $entityManager->flush();
        }

        switch ($relationType) {
            case Session::DRH:
                if ($user->isHRM()) {
                    $session->addUserInSession(Session::DRH, $user);
                }

                break;

            case Session::STUDENT:
                $session
                    ->addUserInSession(Session::STUDENT, $user)
                    ->addUserInCourse(Session::STUDENT, $user, $course)
                ;

                break;

            case Session::COURSE_COACH:
                if ($user->isTeacher()) {
                    $session
                        ->addUserInSession(Session::COURSE_COACH, $user)
                        ->addUserInCourse(
                            Session::COURSE_COACH,
                            $user,
                            $course
                        )
                    ;
                }

                break;
        }

        $entityManager->persist($session);
        $entityManager->flush();
    }

    /**
     * @return array<SessionRelCourse>
     */
    public function getSessionCoursesByStatusInUserSubscription(User $user, Session $session, int $relationType, ?AccessUrl $url = null): array
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('src')
            ->from(SessionRelCourse::class, 'src')
            ->innerJoin(
                SessionRelUser::class,
                'sru',
                Join::WITH,
                'src.session = sru.session'
            )
            ->innerJoin('src.session', 'session')
            ->where(
                $qb->expr()->eq('session', ':session')
            )
            ->andWhere(
                $qb->expr()->eq('sru.user', ':user')
            )
            ->andWhere(
                $qb->expr()->eq('sru.relationType', ':relation_type')
            )
        ;

        $parameters = [
            'session' => $session,
            'user' => $user,
            'relation_type' => $relationType,
        ];

        if ($url) {
            $qb->innerJoin('session.urls', 'urls')
                ->andWhere(
                    $qb->expr()->eq('urls.url', ':url')
                )
            ;

            $parameters['url'] = $url;
        }

        $qb->setParameters($parameters);

        return $qb->getQuery()->getResult();
    }

    /**
     * @return array<SessionRelCourse>
     */
    public function getSessionCoursesByStatusInCourseSubscription(User $user, Session $session, int $status, ?AccessUrl $url = null): array
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('src')
            ->from(SessionRelCourse::class, 'src')
            ->innerJoin(
                SessionRelCourseRelUser::class,
                'srcru',
                Join::WITH,
                'src.session = srcru.session AND src.course = srcru.course'
            )
            ->innerJoin('srcru.session', 'session')
            ->where(
                $qb->expr()->eq('session', ':session')
            )
            ->andWhere(
                $qb->expr()->eq('srcru.user', ':user')
            )
            ->andWhere(
                $qb->expr()->eq('srcru.status', ':status')
            )
        ;

        $parameters = [
            'session' => $session,
            'user' => $user,
            'status' => $status,
        ];

        if ($url) {
            $qb->innerJoin('session.urls', 'urls')
                ->andWhere(
                    $qb->expr()->eq('urls.url', ':url')
                )
            ;

            $parameters['url'] = $url;
        }

        $qb->setParameters($parameters);

        return $qb->getQuery()->getResult();
    }

    private function addSessionRelUserFilterByUrl(Session $session, AccessUrl $url): QueryBuilder
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb
            ->select('sru')
            ->from(SessionRelUser::class, 'sru')
            ->innerJoin('sru.user', 'u')
            ->innerJoin('u.portals', 'p')
            ->andWhere('sru.session = :session AND p.url = :url')
            ->setParameters([
                'session' => $session,
                'url' => $url,
            ])
        ;

        return $qb;
    }

    public function getUserFollowedSessionsInAccessUrl(User $user, AccessUrl $url): QueryBuilder
    {
        $callback = fn (Session $session) => $session->getId();

        if ($user->isHRM()) {
            $idList = array_map($callback, $user->getDRHSessions());
        } elseif ($user->isTeacher() || COURSEMANAGER === $user->getStatus()) {
            $idListAsCoach = $user
                ->getSessionsByStatusInCourseSubscription(Session::COURSE_COACH)
                ->map($callback)
                ->getValues()
            ;
            $idListAsGeneralCoach = array_map($callback, $user->getSessionsAsGeneralCoach());
            $idList = array_merge($idListAsCoach, $idListAsGeneralCoach);
        } elseif ($user->isSessionAdmin()) {
            $idList = array_map($callback, $user->getSessionsAsAdmin());
        } else {
            $idList = array_map($callback, $user->getSessionsAsStudent());
        }

        $qb = $this->createQueryBuilder('s');
        $qb
            ->innerJoin('s.urls', 'u')
            ->where($qb->expr()->eq('u.url', $url->getId()))
            ->andWhere($qb->expr()->in('s.id', ':id_list'))
            ->setParameter('id_list', $idList)
        ;

        return $qb;
    }

    /**
     * @return array<int, Session>
     *
     * @throws Exception
     */
    public function getSubscribedSessionsOfUserInUrl(
        User $user,
        AccessUrl $url,
        bool $ignoreVisibilityForAdmins = false,
    ): array {
        $sessions = $this->getSessionsByUser($user, $url)->getQuery()->getResult();

        $filterSessions = function (Session $session) use ($user, $ignoreVisibilityForAdmins) {
            $visibility = $session->setAccessVisibilityByUser($user, $ignoreVisibilityForAdmins);

            if (Session::VISIBLE !== $visibility) {
                $closedOrHiddenCourses = $session->getClosedOrHiddenCourses();

                if ($closedOrHiddenCourses->count() === $session->getCourses()->count()) {
                    $visibility = Session::INVISIBLE;
                }
            }

            switch ($visibility) {
                case Session::READ_ONLY:
                case Session::VISIBLE:
                case Session::AVAILABLE:
                    break;

                case Session::INVISIBLE:
                    if (!$ignoreVisibilityForAdmins) {
                        return false;
                    }
            }

            return true;
        };

        return array_filter($sessions, $filterSessions);
    }

    /**
     * Finds a valid child session based on access dates and reinscription days.
     */
    public function findValidChildSession(Session $session): ?Session
    {
        $childSessions = $this->findChildSessions($session);
        $now = new DateTime();

        foreach ($childSessions as $childSession) {
            $startDate = $childSession->getAccessStartDate();
            $endDate = $childSession->getAccessEndDate();
            $daysToReinscription = $childSession->getDaysToReinscription();

            if (empty($daysToReinscription) || $daysToReinscription <= 0) {
                continue;
            }

            $adjustedEndDate = (clone $endDate)->modify('-'.$daysToReinscription.' days');

            if ($startDate <= $now && $adjustedEndDate >= $now) {
                return $childSession;
            }
        }

        return null;
    }

    /**
     * Finds a valid parent session based on access dates and reinscription days.
     */
    public function findValidParentSession(Session $session): ?Session
    {
        $parentSession = $this->findParentSession($session);
        if ($parentSession) {
            $now = new DateTime();
            $startDate = $parentSession->getAccessStartDate();
            $endDate = $parentSession->getAccessEndDate();
            $daysToReinscription = $parentSession->getDaysToReinscription();

            // Return null if days to reinscription is not set
            if (null === $daysToReinscription || '' === $daysToReinscription) {
                return null;
            }

            // Adjust the end date by days to reinscription
            $endDate = $endDate->modify('-'.$daysToReinscription.' days');

            // Check if the current date falls within the session's validity period
            if ($startDate <= $now && $endDate >= $now) {
                return $parentSession;
            }
        }

        return null;
    }

    /**
     * Finds child sessions based on the parent session.
     */
    public function findChildSessions(Session $parentSession): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.parentId = :parentId')
            ->setParameter('parentId', $parentSession->getId())
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * Finds the parent session for a given session.
     */
    public function findParentSession(Session $session): ?Session
    {
        if ($session->getParentId()) {
            return $this->find($session->getParentId());
        }

        return null;
    }

    /**
     * Find sessions without child and ready for repetition.
     *
     * @return Session[]
     */
    public function findSessionsWithoutChildAndReadyForRepetition()
    {
        $currentDate = new DateTime();

        $qb = $this->createQueryBuilder('s')
            ->where('s.daysToNewRepetition IS NOT NULL')
            ->andWhere('s.lastRepetition = :false')
            ->andWhere(':currentDate BETWEEN DATE_SUB(s.accessEndDate, s.daysToNewRepetition, \'DAY\') AND s.accessEndDate')
            ->andWhere('NOT EXISTS (
                SELECT 1
                FROM Chamilo\CoreBundle\Entity\Session child
                WHERE child.parentId = s.id
                AND child.accessEndDate >= :currentDate
            )')
            ->setParameter('false', false)
            ->setParameter('currentDate', $currentDate)
        ;

        return $qb->getQuery()->getResult();
    }

    public function countUsersBySession(int $sessionId, int $relationType = Session::STUDENT): int
    {
        $qb = $this->createQueryBuilder('s');
        $qb->select('COUNT(sru.id)')
            ->innerJoin('s.users', 'sru')
            ->where('s.id = :sessionId')
            ->andWhere('sru.relationType = :relationType')
            ->setParameter('sessionId', $sessionId)
            ->setParameter('relationType', $relationType)
        ;

        return (int) $qb->getQuery()->getSingleScalarResult();
    }
}
