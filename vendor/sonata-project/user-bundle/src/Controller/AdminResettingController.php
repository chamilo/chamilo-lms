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

use FOS\UserBundle\Controller\ResettingController;
use FOS\UserBundle\Model\UserInterface;
use FOS\UserBundle\Util\TokenGeneratorInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @author Márk Sági-Kazár <mark.sagikazar@gmail.com>
 */
class AdminResettingController extends ResettingController
{
    /**
     * {@inheritdoc}
     */
    public function requestAction()
    {
        // NEXT_MAJOR: remove when dropping Symfony <2.8 support
        $authorizationCheckerService = $this->container->has('security.authorization_checker')
            ? $this->container->get('security.authorization_checker') : $this->container->get('security.context');

        if ($authorizationCheckerService->isGranted('IS_AUTHENTICATED_FULLY')) {
            return new RedirectResponse($this->container->get('router')->generate('sonata_admin_dashboard'));
        }

        return $this->container->get('templating')->renderResponse('SonataUserBundle:Admin:Security/Resetting/request.html.'.$this->getEngine(), [
            'base_template' => $this->container->get('sonata.admin.pool')->getTemplate('layout'),
            'admin_pool' => $this->container->get('sonata.admin.pool'),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function sendEmailAction()
    {
        // NEXT_MAJOR: Inject $request in the method signature instead.
        if ($this->container->has('request_stack')) {
            $request = $this->container->get('request_stack')->getCurrentRequest();
        } else {
            $request = $this->container->get('request');
        }

        $username = $request->request->get('username');

        /** @var $user UserInterface */
        $user = $this->container->get('fos_user.user_manager')->findUserByUsernameOrEmail($username);

        if (null === $user) {
            return $this->container->get('templating')->renderResponse('SonataUserBundle:Admin:Security/Resetting/request.html.'.$this->getEngine(), [
                'invalid_username' => $username,
                'base_template' => $this->container->get('sonata.admin.pool')->getTemplate('layout'),
                'admin_pool' => $this->container->get('sonata.admin.pool'),
            ]);
        }

        if ($user->isPasswordRequestNonExpired($this->container->getParameter('fos_user.resetting.token_ttl'))) {
            return $this->container->get('templating')->renderResponse('SonataUserBundle:Admin:Security/Resetting/passwordAlreadyRequested.html.'.$this->getEngine(), [
                'base_template' => $this->container->get('sonata.admin.pool')->getTemplate('layout'),
                'admin_pool' => $this->container->get('sonata.admin.pool'),
            ]);
        }

        if (null === $user->getConfirmationToken()) {
            /** @var $tokenGenerator TokenGeneratorInterface */
            $tokenGenerator = $this->container->get('fos_user.util.token_generator');
            $user->setConfirmationToken($tokenGenerator->generateToken());
        }

        $this->container->get('session')->set(static::SESSION_EMAIL, $this->getObfuscatedEmail($user));
        $this->container->get('fos_user.mailer')->sendResettingEmailMessage($user);
        $user->setPasswordRequestedAt(new \DateTime());
        $this->container->get('fos_user.user_manager')->updateUser($user);

        return new RedirectResponse($this->container->get('router')->generate('sonata_user_admin_resetting_check_email'));
    }

    /**
     * {@inheritdoc}
     */
    public function checkEmailAction()
    {
        $session = $this->container->get('session');
        $email = $session->get(static::SESSION_EMAIL);
        $session->remove(static::SESSION_EMAIL);

        if (empty($email)) {
            // the user does not come from the sendEmail action
            return new RedirectResponse($this->container->get('router')->generate('sonata_user_admin_resetting_check_email'));
        }

        return $this->container->get('templating')->renderResponse('SonataUserBundle:Admin:Security/Resetting/checkEmail.html.'.$this->getEngine(), [
            'email' => $email,
            'base_template' => $this->container->get('sonata.admin.pool')->getTemplate('layout'),
            'admin_pool' => $this->container->get('sonata.admin.pool'),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function resetAction($token)
    {
        // NEXT_MAJOR: remove when dropping Symfony <2.8 support
        $authorizationCheckerService = $this->container->has('security.authorization_checker')
            ? $this->container->get('security.authorization_checker') : $this->container->get('security.context');

        if ($authorizationCheckerService->isGranted('IS_AUTHENTICATED_FULLY')) {
            return new RedirectResponse($this->container->get('router')->generate('sonata_admin_dashboard'));
        }

        $user = $this->container->get('fos_user.user_manager')->findUserByConfirmationToken($token);

        if (null === $user) {
            throw new NotFoundHttpException(sprintf('The user with "confirmation token" does not exist for value "%s"', $token));
        }

        if (!$user->isPasswordRequestNonExpired($this->container->getParameter('fos_user.resetting.token_ttl'))) {
            return new RedirectResponse($this->container->get('router')->generate('sonata_user_admin_resetting_request'));
        }

        $form = $this->container->get('fos_user.resetting.form');
        $formHandler = $this->container->get('fos_user.resetting.form.handler');
        $process = $formHandler->process($user);

        if ($process) {
            $this->setFlash('fos_user_success', 'resetting.flash.success');
            $response = new RedirectResponse($this->container->get('router')->generate('sonata_admin_dashboard'));
            $this->authenticateUser($user, $response);

            return $response;
        }

        return $this->container->get('templating')->renderResponse('SonataUserBundle:Admin:Security/Resetting/reset.html.'.$this->getEngine(), [
            'token' => $token,
            'form' => $form->createView(),
            'base_template' => $this->container->get('sonata.admin.pool')->getTemplate('layout'),
            'admin_pool' => $this->container->get('sonata.admin.pool'),
        ]);
    }
}
