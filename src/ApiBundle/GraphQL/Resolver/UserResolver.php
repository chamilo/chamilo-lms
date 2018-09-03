<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\ApiBundle\GraphQL\Resolver;

use Chamilo\CoreBundle\Framework\Container;
use Chamilo\UserBundle\Entity\User;
use Overblog\GraphQLBundle\Definition\Argument;
use Overblog\GraphQLBundle\Definition\Resolver\AliasedInterface;
use Overblog\GraphQLBundle\Definition\Resolver\ResolverInterface;

/**
 * Class UserResolver
 *
 * @package Chamilo\ApiBundle\GraphQL\Resolver
 */
class UserResolver implements ResolverInterface, AliasedInterface
{
    public const IMAGE_SIZE_TINY = 16;
    public const IMAGE_SIZE_SMALL = 32;
    public const IMAGE_SIZE_MEDIUM = 64;
    public const IMAGE_SIZE_BIG = 128;

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
            'resolveUserPicture' => 'user_picture',
        ];
    }

    /**
     * @param int          $size
     * @param \ArrayObject $context
     *
     * @return string
     */
    public function resolveUserPicture($size, \ArrayObject $context): string
    {
        /** @var User $user */
        $user = $context->offsetGet('user');

        if (!$user) {
            return null;
        }

        $path = $user->getAvatarOrAnonymous((int) $size);
        $url = Container::getAsset()->getUrl($path);

        return $url;
    }
}