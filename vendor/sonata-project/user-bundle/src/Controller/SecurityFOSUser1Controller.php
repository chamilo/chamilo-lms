<?php

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\UserBundle\Controller;

use FOS\UserBundle\Controller\SecurityController;
use Sonata\UserBundle\Model\UserInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\SecurityContextInterface;

/**
 * @author Hugo Briand <briand@ekino.com>
 */
class SecurityFOSUser1Controller extends SecurityController
{
    /**
     * {@inheritdoc}
     */
    public function loginAction()
    {
        // NEXT_MAJOR: remove when dropping Symfony <2.8 support
        $tokenStorageService = $this->container->has('security.token_storage')
            ? $this->container->get('security.token_storage') : $this->container->get('security.context');

        $token = $tokenStorageService->getToken();

        if ($token && $token->getUser() instanceof UserInterface) {
            $this->container->get('session')->getFlashBag()->set('sonata_user_error', 'sonata_user_already_authenticated');
            $url = $this->container->get('router')->generate('sonata_user_profile_show');

            return new RedirectResponse($url);
        }

        /*
            Implementation of parent::loginAction
            Needed for fixing the "Bad Credentials" translation problem
            The code is a mix of the 2.0.0 version
            and the 1.3.6 version of FOSUserBundle
        */

        // NEXT_MAJOR: Inject $request in the method signature instead.
        if ($this->container->has('request_stack')) {
            $request = $this->container->get('request_stack')->getCurrentRequest();
        } else {
            $request = $this->container->get('request');
        }
        /** @var $session \Symfony\Component\HttpFoundation\Session\Session */
        $session = $request->getSession();

        // NEXT_MAJOR: Symfony <2.6 BC. To be removed.
        $authenticationErrorKey = class_exists('Symfony\Component\Security\Core\Security')
            ? Security::AUTHENTICATION_ERROR : SecurityContextInterface::AUTHENTICATION_ERROR;

        // get the error if any (works with forward and redirect -- see below)
        if ($request->attributes->has($authenticationErrorKey)) {
            $error = $request->attributes->get($authenticationErrorKey);
        } elseif (null !== $session && $session->has($authenticationErrorKey)) {
            $error = $session->get($authenticationErrorKey);
            $session->remove($authenticationErrorKey);
        } else {
            $error = null;
        }

        if (!$error instanceof AuthenticationException) {
            $error = null; // The value does not come from the security component.
        }

        // NEXT_MAJOR: Symfony <2.6 BC. To be removed.
        $lastUserNameKey = class_exists('Symfony\Component\Security\Core\Security')
            ? Security::LAST_USERNAME : SecurityContextInterface::LAST_USERNAME;

        // NEXT_MAJOR: Symfony <2.4 BC. To be removed.
        if ($this->container->has('security.csrf.token_manager')) {
            $csrfToken = $this->container->get('security.csrf.token_manager')->getToken('authenticate')->getValue();
        } else {
            $csrfToken = $this->container->has('form.csrf_provider')
                ? $this->container->get('form.csrf_provider')->generateCsrfToken('authenticate')
                : null;
        }

        return $this->renderLogin([
            'last_username' => (null === $session) ? '' : $session->get($lastUserNameKey),
            'error' => $error,
            'csrf_token' => $csrfToken,
        ]);
    }
}
