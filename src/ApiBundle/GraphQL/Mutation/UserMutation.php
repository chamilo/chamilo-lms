<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\ApiBundle\GraphQL\Mutation;

use Chamilo\ApiBundle\GraphQL\ApiGraphQLTrait;
use Chamilo\UserBundle\Entity\User;
use Overblog\GraphQLBundle\Definition\Argument;
use Overblog\GraphQLBundle\Definition\Resolver\AliasedInterface;
use Overblog\GraphQLBundle\Definition\Resolver\MutationInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;

/**
 * Class UserMutation.
 *
 * @package Chamilo\ApiBundle\GraphQL\Mutation
 */
class UserMutation implements MutationInterface, AliasedInterface, ContainerAwareInterface
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
            'mutateSendMessage' => 'user_send_message',
        ];
    }

    /**
     * @param Argument     $args
     * @param \ArrayObject $context
     *
     * @return array
     */
    public function mutateSendMessage(Argument $args, \ArrayObject $context): array
    {
        $this->checkAuthorization($context);

        /** @var User $contextUser */
        $contextUser = $context['user'];
        $usersRepo = $this->em->getRepository('ChamiloUserBundle:User');
        $users = $usersRepo->findUsersToSendMessage($contextUser->getId());
        $result = [];

        foreach ($args['receivers'] as $receiverId) {
            $sentMessage = false;

            /** @var User $user */
            foreach ($users as $user) {
                if ((int) $receiverId === $user->getId()) {
                    $sentMessage = true;
                }
            }

            $item = [
                'receiverId' => $receiverId,
                'sent' => false,
            ];

            if ($sentMessage) {
                $messageId = \MessageManager::send_message(
                    $receiverId,
                    $args['subject'],
                    $args['text'],
                    [],
                    [],
                    0,
                    0,
                    0,
                    0,
                    $contextUser->getId()
                );

                $item['sent'] = (bool) $messageId;
            }

            $result[] = $item;
        }

        return $result;
    }
}
