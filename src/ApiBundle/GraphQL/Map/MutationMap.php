<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\ApiBundle\GraphQL\Map;

use Chamilo\ApiBundle\GraphQL\ApiGraphQLTrait;
use Chamilo\UserBundle\Entity\User;
use GraphQL\Type\Definition\ResolveInfo;
use Overblog\GraphQLBundle\Definition\Argument;
use Overblog\GraphQLBundle\Error\UserError;
use Overblog\GraphQLBundle\Resolver\ResolverMap;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;

/**
 * Class MutationResolverMap.
 *
 * @package Chamilo\ApiBundle\GraphQL\Map
 */
class MutationMap extends ResolverMap implements ContainerAwareInterface
{
    use ApiGraphQLTrait;

    /**
     * @return array
     */
    protected function map()
    {
        return [
            'Mutation' => [
                self::RESOLVE_FIELD => function ($value, Argument $args, \ArrayObject $context, ResolveInfo $info) {
                    $method = 'resolve'.ucfirst($info->fieldName);

                    return $this->$method($args, $context);
                },
            ],
        ];
    }

    /**
     * @param Argument $args
     *
     * @return array
     */
    protected function resolveAuthenticate(Argument $args)
    {
        /** @var User $user */
        $user = $this->em->getRepository('ChamiloUserBundle:User')->findOneBy(['username' => $args['username']]);

        if (!$user) {
            throw new UserError($this->translator->trans('User not found.'));
        }

        $encoder = $this->container->get('security.password_encoder');
        $isValid = $encoder->isPasswordValid($user, $args['password']);

        if (!$isValid) {
            throw new UserError($this->translator->trans('Password is not valid.'));
        }

        return [
            'token' => $this->encodeToken($user),
        ];
    }

    /**
     * @param Argument $args
     *
     * @return array
     */
    protected function resolveViewerSendMessage(Argument $args)
    {
        $this->checkAuthorization();

        $currentUser = $this->getCurrentUser();
        $usersRepo = $this->em->getRepository('ChamiloUserBundle:User');
        $users = $usersRepo->findUsersToSendMessage($currentUser->getId());
        $receivers = array_filter(
            $args['receivers'],
            function ($receiverId) use ($users) {
                /** @var User $user */
                foreach ($users as $user) {
                    if ($user->getId() === (int) $receiverId) {
                        return true;
                    }
                }

                return false;
            }
        );

        $result = [];

        foreach ($receivers as $receiverId) {
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
                $currentUser->getId()
            );

            $result[] = [
                'receiverId' => $receiverId,
                'sent' => (bool) $messageId,
            ];
        }

        return $result;
    }
}
