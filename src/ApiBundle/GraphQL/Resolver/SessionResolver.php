<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\ApiBundle\GraphQL\Resolver;

use Chamilo\ApiBundle\GraphQL\ApiGraphQLTrait;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\SessionRelCourse;
use Chamilo\UserBundle\Entity\User;
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

        if ($authChecker->isGranted('ROLE_ADMIN')) {
            $courses = [];

            /** @var SessionRelCourse $sessionCourse */
            foreach ($session->getCourses() as $sessionCourse) {
                $courses[] = $sessionCourse->getCourse();
            }

            return $courses;
        }

        $token = $this->container->get('security.token_storage')->getToken();
        /** @var User $user */
        $user = $token->getUser();
        $courseList = \UserManager::get_courses_list_by_session($user->getId(), $session->getId());
        $courseRepo = $this->em->getRepository('ChamiloCoreBundle:Course');
        $qb = $courseRepo->createQueryBuilder('c');
        $courses = $qb
            ->where(
                $qb->expr()->in('id', array_column($courseList, 'real_id'))
            )
            ->getQuery()
            ->getResult();

        return $courses;
    }
}
