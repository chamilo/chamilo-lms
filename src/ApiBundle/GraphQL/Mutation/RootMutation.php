<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\ApiBundle\GraphQL\Mutation;

use Chamilo\ApiBundle\GraphQL\ApiGraphQLTrait;
use Overblog\GraphQLBundle\Definition\Argument;
use Overblog\GraphQLBundle\Definition\Resolver\AliasedInterface;
use Overblog\GraphQLBundle\Definition\Resolver\MutationInterface;
use Overblog\GraphQLBundle\Error\UserError;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;

/**
 * Class RootMutation.
 *
 * @package Chamilo\ApiBundle\GraphQL\Mutation
 */
class RootMutation implements MutationInterface, AliasedInterface, ContainerAwareInterface
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
    public static function getAliases()
    {
        return [
            'mutationAuthenticate' => 'authenticate',
        ];
    }

    /**
     * @param Argument $args
     *
     * @return array
     */
    public function mutationAuthenticate(Argument $args)
    {
        try {
            $token = $this->getUserToken($args['username'], $args['password']);
        } catch (\Exception $exception) {
            throw new UserError(get_lang('NotAllowed'));
        }

        return [
            'token' => $token,
        ];
    }
}
