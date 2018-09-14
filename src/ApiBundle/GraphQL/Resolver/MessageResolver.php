<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\ApiBundle\GraphQL\Resolver;

use Chamilo\ApiBundle\GraphQL\ApiGraphQLTrait;
use Chamilo\CoreBundle\Entity\Message;
use GraphQL\Type\Definition\ResolveInfo;
use Overblog\GraphQLBundle\Definition\Argument;
use Overblog\GraphQLBundle\Definition\Resolver\ResolverInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;

/**
 * Class MessageResolver.
 *
 * @package Chamilo\ApiBundle\GraphQL\Resolver
 */
class MessageResolver implements ResolverInterface, ContainerAwareInterface
{
    use ApiGraphQLTrait;

    /**
     * @param Message      $message
     * @param Argument     $args
     * @param ResolveInfo  $info
     * @param \ArrayObject $context
     *
     * @return null
     */
    public function __invoke(Message $message, Argument $args, ResolveInfo $info, \ArrayObject $context)
    {
        $method = 'resolve'.ucfirst($info->fieldName);

        if (method_exists($this, $method)) {
            return $this->$method($message, $args, $context);
        }

        $method = 'get'.ucfirst($info->fieldName);

        if (method_exists($message, $method)) {
            return $message->$method();
        }

        return null;
    }

    /**
     * @param Message $message
     *
     * @return \Chamilo\UserBundle\Entity\User
     */
    public function resolveSender(Message $message)
    {
        return $message->getUserSender();
    }

    /**
     * @param Message  $message
     * @param Argument $args
     *
     * @return string
     */
    public function resolveExcerpt(Message $message, Argument $args)
    {
        $striped = strip_tags($message->getContent());
        $replaced = str_replace(["\r\n", "\n"], ' ', $striped);
        $trimmed = trim($replaced);

        return api_trunc_str($trimmed, $args['length']);
    }

    /**
     * @param Message $message
     *
     * @return bool
     */
    public function resolveHasAttachments(Message $message)
    {
        return $message->getAttachments()->count() > 0;
    }
}
