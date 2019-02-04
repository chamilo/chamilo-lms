<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\GraphQlBundle\Traits;

use Chamilo\CoreBundle\Entity\AccessUrl;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\SettingsBundle\Manager\SettingsManager;
use Chamilo\UserBundle\Entity\User;
use Doctrine\ORM\EntityManager;
use Firebase\JWT\JWT;
use Overblog\GraphQLBundle\Error\UserError;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Trait GraphQLTrait.
 *
 * @package Chamilo\GraphQlBundle\Traits
 */
trait GraphQLTrait
{
    use ContainerAwareTrait;

    /**
     * @var User
     */
    protected $currentUser;
    /**
     * @var AccessUrl
     */
    protected $currentAccessUrl;

    /**
     * @var EntityManager
     */
    protected $em;
    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var SettingsManager
     */
    protected $settingsManager;

    /**
     * @var AuthorizationCheckerInterface
     */
    protected $securityChecker;

    /**
     * ApiGraphQLTrait constructor.
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->em = $container->get('doctrine.orm.entity_manager');
        $this->translator = $container->get('translator');
        $this->settingsManager = $container->get('chamilo.settings.manager');
        $this->securityChecker = $container->get('security.authorization_checker');

        $this->getAccessUrl();
    }

    /**
     * Check if the Authorization header was sent to decode the token and authenticate manually the user.
     */
    public function checkAuthorization()
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

        $this->currentUser = $user;
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
     * Throw a UserError if $user doesn't match with the current user.
     *
     * @param User $user User to compare with the context's user
     */
    private function protectCurrentUserData(User $user)
    {
        $currentUser = $this->getCurrentUser();

        if ($user->getId() === $currentUser->getId()) {
            return;
        }

        throw new UserError($this->translator->trans("The user info doesn't match."));
    }

    /**
     * Get the current logged user.
     *
     * @return User
     */
    private function getCurrentUser(): User
    {
        if (null === $this->currentUser) {
            $token = $this->container->get('security.token_storage')->getToken();

            $this->currentUser = $token->getUser();
        }

        return $this->currentUser;
    }

    /**
     * @return AccessUrl
     */
    private function getAccessUrl(): AccessUrl
    {
        if (null === $this->currentAccessUrl) {
            $urlRepo = $this->em->getRepository('ChamiloCoreBundle:AccessUrl');

            if (!api_is_multiple_url_enabled()) {
                $this->currentAccessUrl = $urlRepo->find(1);
            } else {
                $host = $this->container->get('request_stack')->getCurrentRequest()->getSchemeAndHttpHost();

                $this->currentAccessUrl = $urlRepo->findOneBy(['url' => "$host/"]);
            }
        }

        if (null === $this->currentAccessUrl) {
            throw new UserError($this->translator->trans('Access URL not allowed'));
        }

        return $this->currentAccessUrl;
    }

    /**
     * Check if the current user has access to course.
     *
     * @param Course       $course
     * @param Session|null $session
     */
    private function checkCourseAccess(Course $course, Session $session = null)
    {
        if (!empty($session)) {
            $session->setCurrentCourse($course);

            if (!$this->securityChecker->isGranted(SessionVoter::VIEW, $session)) {
                throw new UserError('Unauthorised access to session!');
            }

            return;
        }

        if (!$this->securityChecker->isGranted(CourseVoter::VIEW, $course)) {
            throw new UserError('Unauthorised access to course!');
        }
    }
}
