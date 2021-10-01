<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Repository;

use Chamilo\CoreBundle\Entity\AccessUrl;
use Chamilo\CoreBundle\Entity\AccessUrlRelUser;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\SessionRelCourse;
use Chamilo\CoreBundle\Entity\SessionRelCourseRelUser;
use Chamilo\CoreBundle\Entity\SessionRelUser;
use Chamilo\CoreBundle\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Exception;

/**
 * SessionRepository.
 *
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
    public function getUsersByAccessUrl(Session $session, AccessUrl $url, array $onlyTypes = []): array
    {
        if (0 === $session->getUsers()->count()) {
            return [];
        }

        $qb = $this->addSessionRelUserFilterByUrl($session, $url);
        $qb->orderBy('sru.relationType');

        if ($onlyTypes) {
            $qb->andWhere(
                $qb->expr()->in('sru.relationType', $onlyTypes)
            );
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @return Session[]
     */
    public function getSessionsByUser(User $user, AccessUrl $url)
    {
        $qb = $this->createQueryBuilder('s');
        $qb
            ->select('s')
            ->innerJoin('s.users', 'sru')
            ->leftJoin(AccessUrlRelUser::class, 'uru', Join::WITH, 'uru.user = sru.user')
            ->andWhere('sru.user = :user AND uru.url = :url')
            ->setParameters([
                'user' => $user,
                'url' => $url,
            ])
        ;

        return $qb->getQuery()->getResult();
    }

    public function addUserInCourse(int $status, User $user, Course $course, Session $session): void
    {
        if (!$session->isActive()) {
            throw new Exception('Session not active');
        }

        if (!$user->isActive()) {
            throw new Exception('User not active');
        }

        if (!$course->isActive()) {
            throw new Exception('Course not active');
        }

        if (!$session->hasCourse($course)) {
            $msg = sprintf('Course %s is not subscribed to the session %s', $course->getTitle(), $session->getName());

            throw new Exception($msg);
        }

        switch ($status) {
            case Session::DRH:
                if ($user->hasRole('ROLE_RRHH')) {
                    $session->addUserInSession(Session::DRH, $user);
                }

                break;
            case Session::STUDENT:
                $session
                    ->addUserInSession(Session::STUDENT, $user)
                    ->addUserInCourse(
                        Session::STUDENT,
                        $user,
                        $course
                    )
                ;

                break;
            case Session::COURSE_COACH:
                if ($user->hasRole('ROLE_TEACHER')) {
                    $session->addUserInCourse(
                        Session::COURSE_COACH,
                        $user,
                        $course
                    );
                }

                break;
            default:
                throw new Exception(sprintf('Cannot handle status %s', $status));
        }
    }

    /**
     * @return array<SessionRelCourse>
     */
    public function getSessionCoursesByStatusInUserSubscription(User $user, Session $session, int $relationType, AccessUrl $url = null): array
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
    public function getSessionCoursesByStatusInCourseSuscription(User $user, Session $session, int $status, AccessUrl $url = null): array
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
            ->from(User::class, 'u')
            ->innerJoin(SessionRelUser::class, 'sru', Join::WITH, 'sru.user = u.id')
            ->innerJoin(AccessUrlRelUser::class, 'uru', Join::WITH, 'uru.user = u.id')
            ->andWhere('sru.session = :session AND uru.url = :url')
            ->setParameters([
                'session' => $session,
                'url' => $url,
            ])
        ;

        return $qb;
    }
}
