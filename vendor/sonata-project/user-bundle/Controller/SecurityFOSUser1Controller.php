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
use Symfony\Component\Security\Core\SecurityContextInterface;

/**
 * Class SecurityFOSUser1Controller.
 *
 *
 * @author Hugo Briand <briand@ekino.com>
 */
class SecurityFOSUser1Controller extends SecurityController
{
    /**
     * {@inheritdoc}
     */
    public function loginAction()
    {
        $token = $this->container->get('security.context')->getToken();

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

        /** @var $request \Symfony\Component\HttpFoundation\Request */
        $request = $this->container->get('request');
        /** @var $session \Symfony\Component\HttpFoundation\Session\Session */
        $session = $request->getSession();

        // get the error if any (works with forward and redirect -- see below)
        if ($request->attributes->has(SecurityContextInterface::AUTHENTICATION_ERROR)) {
            $error = $request->attributes->get(SecurityContextInterface::AUTHENTICATION_ERROR);
        } elseif (null !== $session && $session->has(SecurityContextInterface::AUTHENTICATION_ERROR)) {
            $error = $session->get(SecurityContextInterface::AUTHENTICATION_ERROR);
            $session->remove(SecurityContextInterface::AUTHENTICATION_ERROR);
        } else {
            $error = null;
        }

        if (!$error instanceof AuthenticationException) {
            $error = null; // The value does not come from the security component.
        }

        return $this->renderLogin(array(
            'last_username' => (null === $session) ? '' : $session->get(SecurityContextInterface::LAST_USERNAME),
            'error' => $error,
            'csrf_token' => $this->container->get('form.csrf_provider')->generateCsrfToken('authenticate'),
        ));
    }
}
