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
use DateTime;
use DateTimeZone;
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
    public function __construct(ManagerRegistry $registry)
    {
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
    public function getPastSessionsWithDatesForUser(User $user, AccessUrl $url): array
    {
        $now = new DateTime('now', new DateTimeZone('UTC'));

        $qb = $this->getSessionsByUser($user, $url);
        $qb
            ->andWhere(
                $qb->expr()->andX(
                    $qb->expr()->isNotNull('s.accessEndDate'),
                    $qb->expr()->lt('s.accessEndDate', ':now')
                )
            )
            ->setParameter('now', $now)
        ;

        return $qb->getQuery()->getResult();
    }

    /**
     * @return array<int, Session>
     *
     * @throws Exception
     */
    public function getCurrentSessionsWithDatesForUser(User $user, AccessUrl $url): array
    {
        $now = new DateTime('now', new DateTimeZone('UTC'));

        $qb = $this->getSessionsByUser($user, $url);
        $qb
            ->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->andX(
                        $qb->expr()->isNull('s.accessStartDate'),
                        $qb->expr()->isNull('s.accessEndDate'),
                        $qb->expr()->orX(
                            $qb->expr()->eq('s.duration', 0),
                            $qb->expr()->isNull('s.duration')
                        )
                    ),
                    $qb->expr()->andX(
                        $qb->expr()->isNotNull('s.accessStartDate'),
                        $qb->expr()->isNull('s.accessEndDate'),
                        $qb->expr()->lte('s.accessStartDate', ':now')
                    ),
                    $qb->expr()->andX(
                        $qb->expr()->isNotNull('s.accessStartDate'),
                        $qb->expr()->isNotNull('s.accessEndDate'),
                        $qb->expr()->lte('s.accessStartDate', ':now'),
                        $qb->expr()->gte('s.accessEndDate', ':now')
                    ),
                    $qb->expr()->andX(
                        $qb->expr()->isNull('s.accessStartDate'),
                        $qb->expr()->isNotNull('s.accessEndDate'),
                        $qb->expr()->gte('s.accessEndDate', ':now')
                    )
                )
            )
            ->setParameter('now', $now)
        ;

        return $qb->getQuery()->getResult();
    }

    /**
     * @return array<int, Session>
     *
     * @throws Exception
     */
    public function getUpcomingSessionsWithDatesForUser(User $user, AccessUrl $url): array
    {
        $now = new DateTime('now', new DateTimeZone('UTC'));

        $qb = $this->getSessionsByUser($user, $url);
        $qb
            ->andWhere(
                $qb->expr()->andX(
                    $qb->expr()->isNotNull('s.accessStartDate'),
                    $qb->expr()->gt('s.accessStartDate', ':now')
                )
            )
            ->setParameter('now', $now)
        ;

        return $qb->getQuery()->getResult();
    }

    public function addUserInCourse(int $relationType, User $user, Course $course, Session $session): void
    {
        if (!$user->isActive()) {
            throw new Exception('User not active');
        }

        if (!$session->hasCourse($course)) {
            $msg = sprintf('Course %s is not subscribed to the session %s', $course->getTitle(), $session->getTitle());

            throw new Exception($msg);
        }

        if (!\in_array($relationType, Session::getRelationTypeList(), true)) {
            throw new Exception(sprintf('Cannot handle relationType %s', $relationType));
        }

        switch ($relationType) {
            case Session::DRH:
                if ($user->hasRole('ROLE_RRHH')) {
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
}
