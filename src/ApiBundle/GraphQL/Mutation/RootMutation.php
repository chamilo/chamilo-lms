<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\ApiBundle\GraphQL\Mutation;

use Chamilo\CoreBundle\Framework\Container;
use Overblog\GraphQLBundle\Definition\Resolver\AliasedInterface;
use Overblog\GraphQLBundle\Definition\Resolver\MutationInterface;

/**
 * Class RootMutation
 *
 * @package Chamilo\ApiBundle\GraphQL\Mutation
 */
class RootMutation implements MutationInterface, AliasedInterface
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
        return [];
    }
}