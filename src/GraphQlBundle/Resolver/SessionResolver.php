<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\GraphQlBundle\Resolver;

use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\SessionRelCourse;
use Chamilo\CoreBundle\Security\Authorization\Voter\SessionVoter;
use Chamilo\GraphQlBundle\Traits\GraphQLTrait;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;

/**
 * Class SessionResolver.
 *
 * @package Chamilo\GraphQlBundle\Resolver
 */
class SessionResolver implements ContainerAwareInterface
{
    use GraphQLTrait;

    /**
     * @param Session $session
     *
     * @return string
     */
    public function getDescription(Session $session)
    {
        if (false === $session->getShowDescription()) {
            return '';
        }

        return $session->getDescription();
    }

    /**
     * @param Session $session
     *
     * @return int
     */
    public function getNumberOfUsers(Session $session)
    {
        return $session->getNbrUsers();
    }

    /**
     * @param Session $session
     *
     * @return int
     */
    public function getNumberOfCourses(Session $session)
    {
        return $session->getNbrCourses();
    }

    /**
     * @param Session $session
     *
     * @return array
     */
    public function getCourses(Session $session): array
    {
        $courses = [];

        /** @var SessionRelCourse $sessionCourse */
        foreach ($session->getCourses() as $sessionCourse) {
            $course = $sessionCourse->getCourse();

            $session->setCurrentCourse($course);

            if (false !== $this->securityChecker->isGranted(SessionVoter::VIEW, $session)) {
                $courses[] = $course;
            }
        }

        return $courses;
    }
}
