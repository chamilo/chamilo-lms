<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\ApiBundle\GraphQL\Resolver;

use Chamilo\ApiBundle\GraphQL\ApiGraphQLTrait;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\SessionRelCourse;
use Chamilo\CoreBundle\Security\Authorization\Voter\SessionVoter;
use Overblog\GraphQLBundle\Definition\Resolver\AliasedInterface;
use Overblog\GraphQLBundle\Definition\Resolver\ResolverInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;

/**
 * Class SessionResolver
 *
 * @package Chamilo\ApiBundle\GraphQL\Resolver
 */
class SessionResolver implements ResolverInterface, AliasedInterface, ContainerAwareInterface
{
    use ApiGraphQLTrait;

    /**
     * Returns methods aliases.
     *
     * For instance:
     * array('myMethod' => 'myAlias')
     *
     * @return array
     */
    public static function getAliases(): array
    {
        return [
            'resolveDescription' => 'session_description',
            'resolveNumberCourses' => 'session_nbrcourses',
            'resolveNumberUsers' => 'session_nbrusers',
            'resolveCourses' => 'session_courses',
        ];
    }

    /**
     * @param Session $session
     *
     * @return string
     */
    public function resolveDescription(Session $session): string
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
    public function resolveNumberCourses(Session $session): int
    {
        return $session->getNbrCourses();
    }

    /**
     * @param Session $session
     *
     * @return int
     */
    public function resolveNumberUsers(Session $session): int
    {
        return $session->getNbrUsers();
    }

    /**
     * @param Session $session
     *
     * @return array
     */
    public function resolveCourses(Session $session): array
    {
        $authChecker = $this->container->get('security.authorization_checker');

        $courses = [];

        /** @var SessionRelCourse $sessionCourse */
        foreach ($session->getCourses() as $sessionCourse) {
            $course = $sessionCourse->getCourse();

            $session->setCurrentCourse($course);

            if (false !== $authChecker->isGranted(SessionVoter::VIEW, $session)) {
                $courses[] = $course;
            }
        }

        return $courses;
    }
}
