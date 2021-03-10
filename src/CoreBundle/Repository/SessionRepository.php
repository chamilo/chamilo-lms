<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Repository;

use Chamilo\CoreBundle\Entity\AccessUrl;
use Chamilo\CoreBundle\Entity\AccessUrlRelUser;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\SessionRelUser;
use Chamilo\CoreBundle\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\Persistence\ManagerRegistry;

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

    /**
     * @return SessionRelUser[]
     */
    public function getUsersByAccessUrl(Session $session, AccessUrl $url)
    {
        if (0 === $session->getUsers()->count()) {
            return [];
        }

        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb
            ->select('sru')
            ->from(User::class, 'u')
            ->innerJoin(SessionRelUser::class, 'sru')
            ->innerJoin(AccessUrlRelUser::class, 'uru')
            ->andWhere('sru.session = :session AND uru.url = :url ')
            ->setParameters([
                'session' => $session,
                'url' => $url,
            ])
            ->orderBy('sru.relationType')
        ;

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

    protected function addUserInCourse(
        int $status,
        User $user,
        Course $course,
        Session $session
    ): void {
        if ($session->isActive() &&
            $user->getIsActive() &&
            $course->isActive() && $session->hasCourse($course)
        ) {
            switch ($status) {
                case Session::DRH:
                    if ($user->hasRole('ROLE_RRHH')) {
                        $session->addUserInSession(Session::DRH, $user);
                    }

                    break;
                case Session::STUDENT:
                    $session->addUserInSession(Session::STUDENT, $user);
                    $session->addUserInCourse(
                        Session::STUDENT,
                        $user,
                        $course
                    );

                    break;
                case Session::COACH:
                    if ($user->hasRole('ROLE_TEACHER')) {
                        $session->addUserInCourse(
                            Session::COACH,
                            $user,
                            $course
                        );
                    }

                    break;
            }
        }
    }
}
