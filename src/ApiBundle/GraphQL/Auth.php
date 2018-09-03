<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\ApiBundle\GraphQL;

use Chamilo\CoreBundle\Framework\Container;
use Chamilo\UserBundle\Entity\User;
use Firebase\JWT\JWT;

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
     * @throws \Exception
     */
    public function getUserToken($username, $password): string
    {
        /** @var User $user */
        $user = Container::getUserManager()->findUserBy(['username' => $username]);

        if (!$user) {
            throw new \Exception(get_lang('NoUser'));
        }

        $encoder = Container::$container->get('chamilo_user.security.encoder');
        $isValid = $encoder->isPasswordValid(
            $user->getPassword(),
            $password,
            $user->getSalt()
        );

        if (!$isValid) {
            throw new \Exception(get_lang('InvalidId'));
        }

        return self::generateToken($user->getId());
    }

    /**
     * @param int $userId
     *
     * @return string
     */
    private static function generateToken($userId): string
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
     * @param \ArrayObject $context
     *
     * @throws \Exception
     */
    public static function checkAuthorization(\ArrayObject $context): void
    {
        $header = Container::getRequest()->headers->get('Authorization');
        $token = str_replace(['Bearer ', 'bearer '], '', $header);

        if (empty($token)) {
            throw new \Exception(get_lang('NotAllowed'));
        }

        $tokenData = Auth::getTokenData($token);

        /** @var User $user */
        $user = Container::getUserManager()->find($tokenData['user']);

        if (!$user) {
            throw new \Exception(get_lang('NotAllowed'));
        }

        $context->offsetSet('user', $user);
    }

    /**
     * @param string $token
     *
     * @return array
     */
    private static function getTokenData($token): array
    {
        $secret = Container::$container->getParameter('secret');
        $jwt = JWT::decode($token, $secret, ['HS384']);

        $data = (array) $jwt->data;

        return $data;
    }
}
