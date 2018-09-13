<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\ApiBundle\GraphQL\Resolver;

use Chamilo\ApiBundle\GraphQL\ApiGraphQLTrait;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\SessionRelCourse;
use Chamilo\CoreBundle\Security\Authorization\Voter\SessionVoter;
use GraphQL\Type\Definition\ResolveInfo;
use Overblog\GraphQLBundle\Definition\Argument;
use Overblog\GraphQLBundle\Definition\Resolver\ResolverInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;

/**
 * Class SessionResolver
 *
 * @package Chamilo\ApiBundle\GraphQL\Resolver
 */
class SessionResolver implements ResolverInterface, ContainerAwareInterface
{
    use ApiGraphQLTrait;

    public function __invoke(Session $session, Argument $args, ResolveInfo $info, \ArrayObject $context)
    {
        $context->offsetSet('session', $session);

        $method = 'resolve'.ucfirst($info->fieldName);

        if (method_exists($this, $method)) {
            return $this->$method($session, $args, $context);
        }

        $method = 'get'.ucfirst($info->fieldName);

        if (method_exists($session, $method)) {
            return $session->$method();
        }

        return null;
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
    public function resolveNumberOfCourses(Session $session): int
    {
        return $session->getNbrCourses();
    }

    /**
     * @param Session $session
     *
     * @return int
     */
    public function resolveNumberOfUsers(Session $session): int
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
