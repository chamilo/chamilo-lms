<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\ApiBundle\GraphQL;

use Chamilo\UserBundle\Entity\User;
use Doctrine\ORM\EntityManager;
use Firebase\JWT\JWT;
use Overblog\GraphQLBundle\Error\UserError;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Trait ApiGraphQLTrait.
 *
 * @package Chamilo\ApiBundle\GraphQL
 */
trait ApiGraphQLTrait
{
    use ContainerAwareTrait;

    /**
     * @var EntityManager
     */
    private $em;
    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * ApiGraphQLTrait constructor.
     *
     * @param EntityManager       $entityManager
     * @param TranslatorInterface $translator
     */
    public function __construct(EntityManager $entityManager, TranslatorInterface $translator)
    {
        $this->em = $entityManager;
        $this->translator = $translator;
    }

    /**
     * @param \ArrayObject $context
     */
    public function checkAuthorization(\ArrayObject $context): void
    {
        $request = $this->container->get('request_stack')->getCurrentRequest();
        $header = $request->headers->get('Authorization');
        $token = str_replace(['Bearer ', 'bearer '], '', $header);

        if (empty($token)) {
            throw new UserError($this->translator->trans('NotAllowed'));
        }

        $tokenData = $this->decodeToken($token);

        try {
            /** @var User $user */
            $user = $this->em->find('ChamiloUserBundle:User', $tokenData['user']);
        } catch (\Exception $e) {
            $user = null;
        }

        if (!$user) {
            throw new UserError($this->translator->trans('NotAllowed'));
        }

        $token = new UsernamePasswordToken($user, null, 'main', $user->getRoles());
        $this->container->get('security.token_storage')->setToken($token);
        $this->container->get('session')->set('_security_main', serialize($token));

        $context->offsetSet('user', $user);
    }

    /**
     * @param string $username
     * @param string $password
     *
     * @return string
     */
    private function getUserToken($username, $password): string
    {
        /** @var User $user */
        $user = $this->em->getRepository('ChamiloUserBundle:User')->findOneBy(['username' => $username]);

        if (!$user) {
            throw new UserError($this->translator->trans('NoUser'));
        }

        $encoder = $this->container->get('chamilo_user.security.encoder');
        $isValid = $encoder->isPasswordValid(
            $user->getPassword(),
            $password,
            $user->getSalt()
        );

        if (!$isValid) {
            throw new UserError($this->translator->trans('InvalidId'));
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
     * @param string $token
     *
     * @return array
     */
    private function decodeToken($token): array
    {
        $secret = $this->container->getParameter('secret');

        try {
            $jwt = JWT::decode($token, $secret, ['HS384']);

            $data = (array) $jwt->data;

            return $data;
        } catch (\Exception $exception) {
            throw new UserError($exception->getMessage());
        }
    }

    /**
     * Throw a UserError if current user doesn't match with context's user.
     *
     * @param \ArrayObject $context Current context
     * @param User         $user    User to compare with the context's user
     */
    private function protectUserData(\ArrayObject $context, User $user)
    {
        /** @var User $contextUser */
        $contextUser = $context['user'];

        if ($user->getId() === $contextUser->getId()) {
            return;
        }

        throw new UserError($this->translator->trans('UserInfoDoesNotMatch'));
    }
}
