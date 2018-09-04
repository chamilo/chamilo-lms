<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\ApiBundle\GraphQL\Resolver;

use Chamilo\CoreBundle\Entity\Message;
use Overblog\GraphQLBundle\Definition\Resolver\AliasedInterface;
use Overblog\GraphQLBundle\Definition\Resolver\ResolverInterface;

/**
 * Class MessageResolver
 * @package Chamilo\ApiBundle\GraphQL\Resolver
 */
class MessageResolver implements ResolverInterface, AliasedInterface
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
            'resolveSender' => 'message_sender',
            'resolveExcerpt' => 'message_excerpt',
            'resolveHasAttachments' => 'message_has_attachments',
        ];
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
     * @param Message $message
     * @param int     $length
     *
     * @return string
     */
    public function resolveExcerpt($message, $length = 50)
    {
        $striped = strip_tags($message->getContent());
        $replaced = str_replace(["\r\n", "\n"], ' ', $striped);
        $trimmed = trim($replaced);

        return api_trunc_str($trimmed, $length);
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