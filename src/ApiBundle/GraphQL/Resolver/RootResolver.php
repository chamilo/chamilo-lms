<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\ApiBundle\GraphQL\Resolver;

use Chamilo\ApiBundle\GraphQL\Auth;
use Chamilo\UserBundle\Entity\User;
use Overblog\GraphQLBundle\Definition\Resolver\AliasedInterface;
use Overblog\GraphQLBundle\Definition\Resolver\ResolverInterface;
use Overblog\GraphQLBundle\Error\UserError;

/**
 * Class RootResolver
 *
 * @package Chamilo\ApiBundle\GraphQL\Resolver
 */
class RootResolver implements ResolverInterface, AliasedInterface
{

    /**
     * Returns methods aliases.
     *
     * For instance:
     * array('myMethod' => 'myAlias')
     *
     * @return array
     */
    public static function getAliases()
    {
        return [
            'resolverViewer' => 'viewer',
        ];
    }

    /**
     * @param \ArrayObject $context
     *
     * @return User
     */
    public function resolverViewer(\ArrayObject $context)
    {
        try {
            Auth::checkAuthorization($context);
        } catch (\Exception $exception) {
            throw new UserError($exception->getMessage());
        }

        /** @var User $user */
        $user = $context->offsetGet('user');

        return $user;
    }
}
