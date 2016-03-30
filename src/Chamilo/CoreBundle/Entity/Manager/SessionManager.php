<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity\Manager;

use Chamilo\CoreBundle\Entity\Repository\CourseRepository;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\SessionRelUser;
use Sonata\CoreBundle\Model\BaseEntityManager;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\UserBundle\Entity\User;
use Sonata\DatagridBundle\Pager\Doctrine\pager;
use Sonata\DatagridBundle\ProxyQuery\Doctrine\ProxyQuery;
use Doctrine\Common\Collections\Criteria;

/**
 * Class SessionManager
 * @package Chamilo\CoreBundle\Entity\Manager
 */
class SessionManager extends BaseEntityManager
{
    /**
     * @return Session
     */
    public function createSession()
    {
        return $this->create();
    }

    /**
     * @param $name
     * @return Session
     */
    public function findOneByName($name)
    {
        return $this->getRepository()->findOneByName($name);
    }

    /**
     * @param User $user
     * @param Session $session
     */
    public function addDrh(User $user, Session $session)
    {
        $session->addUserInSession(Session::COACH, $user);
    }

    /**
     * @param User $user
     * @param Session $session
     * @return bool
     */
    public function hasDrh(User $user, Session $session)
    {
        $subscription = new SessionRelUser();
        $subscription->setUser($user);
        $subscription->setSession($session);
        $subscription->setRelationType(Session::DRH);

        return $session->hasUser($subscription);
    }

    /**
     * @param User $user
     * @param Course $course
     * @param Session $session
     */
    public function addStudentInCourse(
        User $user,
        Course $course,
        Session $session
    ) {
        $this->addUserInCourse(Session::STUDENT, $user, $course, $session);
    }

    /**
     * @param User $user
     * @param Course $course
     * @param Session $session
     * @return bool
     */
    public function hasStudentInCourse(
        User $user,
        Course $course,
        Session $session
    ) {
        return $session->hasUserInCourse($user, $course, Session::STUDENT);
    }

    /**
     * @param User $user
     * @param Course $course
     * @param Session $session
     */
    public function addCoachInCourse(
        User $user,
        Course $course,
        Session $session
    ) {
        $this->addUserInCourse(Session::COACH, $user, $course, $session);
    }

    /**
     * @param User $user
     * @param Course $course
     * @param Session $session
     * @return bool
     */
    public function hasCoachInCourse(
        User $user,
        Course $course,
        Session $session
    ) {
        return $session->hasUserInCourse($user, $course, Session::COACH);
    }

    /**
     * @param $status
     * @param User $user
     * @param Course $course
     * @param Session $session
     */
    private function addUserInCourse(
        $status,
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
