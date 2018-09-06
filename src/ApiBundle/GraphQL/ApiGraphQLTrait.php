<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\ApiBundle\GraphQL;

use Chamilo\UserBundle\Entity\User;
use Doctrine\ORM\EntityManager;
use Firebase\JWT\JWT;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Trait ApiGraphQLTrait
 * @package Chamilo\ApiBundle\GraphQL
 */
trait ApiGraphQLTrait
{
    use ContainerAwareTrait;

    private $em;

    /**
     * ApiGraphQLTrait constructor.
     *
     * @param EntityManager $entityManager
     */
    public function __construct(EntityManager $entityManager)
    {
        $this->em = $entityManager;
    }

    /**
     * @param string $username
     * @param string $password
     *
     * @throws \Exception
     *
     * @return string
     */
    private function getUserToken($username, $password): string
    {
        /** @var User $user */
        $user = $this->em->getRepository('ChamiloUserBundle:User')->findOneBy(['username' => $username]);

        if (!$user) {
            throw new \Exception(get_lang('NoUser'));
        }

        $encoder = $this->container->get('chamilo_user.security.encoder');
        $isValid = $encoder->isPasswordValid(
            $user->getPassword(),
            $password,
            $user->getSalt()
        );

        if (!$isValid) {
            throw new \Exception(get_lang('InvalidId'));
        }

        return self::encodeToken($user);
    }

    /**
     * @param User $user
     *
     * @return string
     */
    private function encodeToken(User $user): string
    {
        $secret = $this->container->getParameter('secret');
        $time = time();

        $payload = [
            'iat' => $time,
            'exp' => $time + (60 * 60 * 24),
            'data' => [
                'user' => $user->getId(),
            ],
        ];

        return JWT::encode($payload, $secret, 'HS384');
    }

    /**
     * @param \ArrayObject $context
     *
     * @throws \Exception
     */
    public function checkAuthorization(\ArrayObject $context): void
    {
        $request = $this->container->get('request_stack')->getCurrentRequest();
        $header = $request->headers->get('Authorization');
        $token = str_replace(['Bearer ', 'bearer '], '', $header);

        if (empty($token)) {
            throw new \Exception(get_lang('NotAllowed'));
        }

        $tokenData = $this->decodeToken($token);

        /** @var User $user */
        $user = $this->em->find('ChamiloUserBundle:User', $tokenData['user']);

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
    private function decodeToken($token): array
    {
        $secret = $this->container->getParameter('secret');
        $jwt = JWT::decode($token, $secret, ['HS384']);

        $data = (array) $jwt->data;

        return $data;
    }
}
