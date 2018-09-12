<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\ApiBundle\GraphQL\Resolver;

use Chamilo\ApiBundle\GraphQL\ApiGraphQLTrait;
use Chamilo\CoreBundle\Entity\Session;
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
}
