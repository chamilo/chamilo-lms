<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\ApiBundle\GraphQL;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\CourseRelUser;
use Chamilo\UserBundle\Entity\User;
use Doctrine\ORM\EntityManager;
use Firebase\JWT\JWT;
use Overblog\GraphQLBundle\Error\UserError;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

/**
 * Trait ApiGraphQLTrait.
 *
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
     * @param \ArrayObject $context
     */
    public function checkAuthorization(\ArrayObject $context): void
    {
        $request = $this->container->get('request_stack')->getCurrentRequest();
        $header = $request->headers->get('Authorization');
        $token = str_replace(['Bearer ', 'bearer '], '', $header);

        if (empty($token)) {
            throw new UserError(get_lang('NotAllowed'));
        }

        $tokenData = $this->decodeToken($token);

        try {
            /** @var User $user */
            $user = $this->em->find('ChamiloUserBundle:User', $tokenData['user']);
        } catch (\Exception $e) {
            $user = null;
        }

        if (!$user) {
            throw new UserError(get_lang('NotAllowed'));
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
            throw new UserError(get_lang('NoUser'));
        }

        $encoder = $this->container->get('chamilo_user.security.encoder');
        $isValid = $encoder->isPasswordValid(
            $user->getPassword(),
            $password,
            $user->getSalt()
        );

        if (!$isValid) {
            throw new UserError(get_lang('InvalidId'));
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

        throw new UserError(get_lang('UserInfoDoesNotMatch'));
    }

    /**
     * @param Course       $course
     * @param \ArrayObject $context
     *
     * @return bool
     */
    private function userIsAllowedToCourse(Course $course, \ArrayObject $context): bool
    {
        $authorizationChecker = $this->container->get('security.authorization_checker');
        /** @var User $contextUser */
        $contextUser = $context['user'];

        if ($authorizationChecker->isGranted('ROLE_ADMIN')) {
            return true;
        }

        /** @var CourseRelUser $subscription */
        foreach ($contextUser->getCourses() as $subscription) {
            if ($subscription->getCourse()->getId() === $course->getId()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Throw a UserError if current user is not allowed to course.
     *
     * @param Course       $course
     * @param \ArrayObject $context
     */
    private function protectCourseData(Course $course, \ArrayObject $context)
    {
        if ($this->userIsAllowedToCourse($course, $context)) {
            return;
        }

        throw new UserError(get_lang('NotAllowed'));
    }
}
