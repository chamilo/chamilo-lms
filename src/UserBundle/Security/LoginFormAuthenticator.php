<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\UserBundle\Security;

use Chamilo\CoreBundle\Hook\CheckLoginCredentialsHook;
use Chamilo\CoreBundle\Hook\HookFactory;
use Chamilo\SettingsBundle\Manager\SettingsManager;
use Chamilo\UserBundle\Form\LoginType;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Guard\Authenticator\AbstractFormLoginAuthenticator;

/**
 * Class LoginFormAuthenticator.
 */
class LoginFormAuthenticator extends AbstractFormLoginAuthenticator
{
    private $em;
    private $router;
    private $passwordEncoder;
    private $csrfTokenManager;
    private $formFactory;
    private $hookFactory;

    public function __construct(
        EntityManager $em,
        RouterInterface $router,
        UserPasswordEncoderInterface $passwordEncoder,
        CsrfTokenManagerInterface $csrfTokenManager,
        FormFactoryInterface $formFactory,
        HookFactory $hookFactory
    ) {
        $this->em = $em;
        $this->router = $router;
        $this->passwordEncoder = $passwordEncoder;
        $this->csrfTokenManager = $csrfTokenManager;
        $this->formFactory = $formFactory;
        //$this->settingsManager = $settingsManager;
        $this->hookFactory = $hookFactory;
    }

    public function supports(Request $request): bool
    {
        if ('/login' !== $request->getPathInfo() || 'POST' != $request->getMethod()) {
            return false;
        }

        return true;
    }

    /**
     * @param array $credentials
     *
     * @return UserInterface|null
     */
    public function getUser($credentials, UserProviderInterface $userProvider)
    {
        return $userProvider->loadUserByUsername($credentials['_username']);
    }

    /**
     * @param mixed $credentials
     *
     * @throws \Exception
     *
     * @return bool
     */
    public function checkCredentials($credentials, UserInterface $user)
    {
        if ($this->passwordEncoder->isPasswordValid($user, $credentials['_password'])) {
            return true;
        }

        $hook = $this->hookFactory->build(CheckLoginCredentialsHook::class);

        if (empty($hook)) {
            return false;
        }

        $hook->setEventData(['user' => $user, 'credentials' => $credentials]);

        return $hook->notifyLoginCredentials();
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): RedirectResponse
    {
        $request->getSession()->set(Security::AUTHENTICATION_ERROR, $exception);

        return new RedirectResponse($this->router->generate('login'));
    }

    public function getLoginUrl(): RedirectResponse
    {
        return new RedirectResponse($this->router->generate('login'));
    }

    /**
     * @param string $providerKey
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey): RedirectResponse
    {
        return new RedirectResponse($this->router->generate('home'));
    }

    public function getCredentials(Request $request): array
    {
        if ('/login' !== $request->getPathInfo() || !$request->isMethod('POST')) {
            return false;
        }

        $form = $this->formFactory->create(LoginType::class);

        $form->handleRequest($request);
        $data = $form->getData();

        $request->getSession()->set(
            Security::LAST_USERNAME,
            $data['_username']
        );

        return $data;
    }
}
