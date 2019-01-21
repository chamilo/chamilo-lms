<?php

namespace Chamilo\UserBundle\Security;

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
 *
 * @package Chamilo\UserBundle\Security
 */
class LoginFormAuthenticator extends AbstractFormLoginAuthenticator
{
    private $em;
    private $router;
    private $passwordEncoder;
    private $csrfTokenManager;
    private $formFactory;

    public function __construct(
        EntityManager $em,
        RouterInterface $router,
        UserPasswordEncoderInterface $passwordEncoder,
        CsrfTokenManagerInterface $csrfTokenManager,
        FormFactoryInterface $formFactory
    ) {
        $this->em = $em;
        $this->router = $router;
        $this->passwordEncoder = $passwordEncoder;
        $this->csrfTokenManager = $csrfTokenManager;
        $this->formFactory = $formFactory;
        //$this->settingsManager = $settingsManager;
    }

    /**
     * @param Request $request
     *
     * @return bool
     */
    public function supports(Request $request): bool
    {
        if ($request->getPathInfo() !== '/login' || $request->getMethod() != 'POST') {
            return false;
        }

        return true;
    }

    /**
     * @param array                 $credentials
     * @param UserProviderInterface $userProvider
     *
     * @return UserInterface|null
     */
    public function getUser($credentials, UserProviderInterface $userProvider)
    {
        return $userProvider->loadUserByUsername($credentials['_username']);
    }

    /**
     * @param mixed         $credentials
     * @param UserInterface $user
     *
     * @return bool
     */
    public function checkCredentials($credentials, UserInterface $user)
    {
        if (!$this->passwordEncoder->isPasswordValid($user, $credentials['_password'])) {
            return false;
        }

        // other checks

        return true;
    }

    /**
     * @param Request                 $request
     * @param AuthenticationException $exception
     *
     * @return RedirectResponse
     */
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): RedirectResponse
    {
        $request->getSession()->set(Security::AUTHENTICATION_ERROR, $exception);

        return new RedirectResponse($this->router->generate('login'));
    }

    /**
     * @return RedirectResponse
     */
    public function getLoginUrl(): RedirectResponse
    {
        return new RedirectResponse($this->router->generate('login'));
    }

    /**
     * @param Request        $request
     * @param TokenInterface $token
     * @param string         $providerKey
     *
     * @return RedirectResponse
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey): RedirectResponse
    {
        return new RedirectResponse($this->router->generate('home'));
    }

    /**
     * @param Request $request
     *
     * @return array
     */
    public function getCredentials(Request $request): array
    {
        if ($request->getPathInfo() !== '/login' || !$request->isMethod('POST')) {
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
