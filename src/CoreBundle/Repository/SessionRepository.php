<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Repository;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * SessionRepository.
 *
 * @author  Julio Montoya <gugli100@gmail.com>
 */
class SessionRepository extends ServiceEntityRepository
{
    /**
     * SessionRepository constructor.
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Session::class);
    }

    /**
     * @param $status
     */
    protected function addUserInCourse(
        int $status,
        User $user,
        Course $course,
        Session $session
    ) {
        if ($session->isActive() &&
            $user->getIsActive() &&
            $course->isActive()
        ) {
            if ($session->hasCourse($course)) {
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
}
