<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\ApiBundle\GraphQL;

use Chamilo\CoreBundle\Framework\Container;
use Chamilo\UserBundle\Entity\User;
use Firebase\JWT\JWT;
use Overblog\GraphQLBundle\Error\UserError;

/**
 * Class Auth
 *
 * @package Chamilo\ApiBundle\GraphQL
 */
class Auth
{

    /**
     * @param string $username
     * @param string $password
     *
     * @return string
     */
    public function getUserToken($username, $password): string
    {
        $userRepo = Container::getEntityManager()->getRepository('ChamiloUserBundle:User');

        /** @var User $user */
        $user = $userRepo->findOneByUsername($username);

        if (!$user) {
            throw new UserError(get_lang('NoUser'));
        }

        $encoder = Container::$container->get('chamilo_user.security.encoder');
        $isValid = $encoder->isPasswordValid(
            $user->getPassword(),
            $password,
            $user->getSalt()
        );

        if (!$isValid) {
            throw new UserError(get_lang('InvalidId'));
        }

        return self::generateToken($user->getId());
    }

    /**
     * @param int $userId
     *
     * @return string
     */
    public static function generateToken($userId): string
    {
        $secret = Container::$container->getParameter('secret');
        $time = time();

        $payload = [
            'iat' => $time,
            'exp' => $time + (60 * 60 * 24),
            'data' => [
                'user' => $userId,
            ],
        ];

        return JWT::encode($payload, $secret, 'HS384');
    }

    /**
     * @param string $token
     *
     * @return array
     */
    public static function getTokenData($token): array
    {
        $secret = Container::$container->getParameter('secret');
        $jwt = JWT::decode($token, $secret, ['HS384']);

        $data = (array) $jwt->data;

        return $data;
    }
}
