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
use Doctrine\Common\Collections\ArrayCollection;
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
        ;

        return $qb;
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

    /**
     * @return array<int, Session>
     *
     * @throws Exception
     */
    public function getCurrentSessionsOfUserInUrl(User $user, AccessUrl $url): array
    {
        $sessions = $this->getSubscribedSessionsOfUserInUrl($user, $url);

        $filterCurrentSessions = function (Session $session) use ($user, $url) {
            $userIsGeneralCoach = $session->hasUserAsGeneralCoach($user);
            if (!$userIsGeneralCoach) {
                $coursesAsCoach = $this->getSessionCoursesByStatusInCourseSubscription($user, $session, Session::COURSE_COACH, $url);
                $coursesAsStudent = $this->getSessionCoursesByStatusInCourseSubscription($user, $session, Session::STUDENT, $url);
                $validCourses = array_merge($coursesAsCoach, $coursesAsStudent);

                if (empty($validCourses)) {
                    return false;
                }
                $session->setCourses(new ArrayCollection($validCourses));
            }

            $userIsCoach = $session->hasCoach($user);

            // Check if session has a duration
            if ($session->getDuration() > 0) {
                $daysLeft = $session->getDaysLeftByUser($user);

                return $daysLeft >= 0 || $userIsCoach;
            }

            // Determine the start date based on whether the user is a coach
            $sessionStartDate = $userIsCoach && $session->getCoachAccessStartDate()
                ? $session->getCoachAccessStartDate()
                : $session->getAccessStartDate();

            // If there is no start date, consider the session current
            if (!$sessionStartDate) {
                return true;
            }

            // Get the current date and time
            $now = new DateTime();

            // Determine the end date based on whether the user is a coach
            $sessionEndDate = $userIsCoach && $session->getCoachAccessEndDate()
                ? $session->getCoachAccessEndDate()
                : $session->getAccessEndDate();

            // Check if the current date is within the start and end dates
            return $now >= $sessionStartDate && (!$sessionEndDate || $now <= $sessionEndDate);
        };

        return array_filter($sessions, $filterCurrentSessions);
    }

    /**
     * @return array<int, Session>
     *
     * @throws Exception
     */
    public function getUpcomingSessionsOfUserInUrl(User $user, AccessUrl $url): array
    {
        $sessions = $this->getSubscribedSessionsOfUserInUrl($user, $url);

        $filterUpcomingSessions = function (Session $session) use ($user) {
            $now = new DateTime();

            // All session with access by duration call be either current or past
            if ($session->getDuration() > 0) {
                return false;
            }

            // Determine if the user is a coach
            $userIsCoach = $session->hasCoach($user);

            // Get the appropriate start date based on whether the user is a coach
            $sessionStartDate = $userIsCoach && $session->getCoachAccessStartDate()
                ? $session->getCoachAccessStartDate()
                : $session->getAccessStartDate();

            // If there's no start date, the session is not considered future
            if (!$sessionStartDate) {
                return false;
            }

            // Check if the current date is before the start date
            return $now < $sessionStartDate;
        };

        return array_filter($sessions, $filterUpcomingSessions);
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
                if ($user->hasRole('ROLE_HR')) {
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
                if ($user->hasRole('ROLE_TEACHER')) {
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
}
