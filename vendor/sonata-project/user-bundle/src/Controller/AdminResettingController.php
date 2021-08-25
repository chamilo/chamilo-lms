<?php

declare(strict_types=1);

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\UserBundle\Controller;

use FOS\UserBundle\Model\UserInterface;
use FOS\UserBundle\Util\TokenGeneratorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Exception\AccountStatusException;

class AdminResettingController extends Controller
{
    /**
     * @return Response
     */
    public function requestAction()
    {
        if ($this->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_FULLY')) {
            return new RedirectResponse($this->get('router')->generate('sonata_admin_dashboard'));
        }

        return $this->render('@SonataUser/Admin/Security/Resetting/request.html.twig', [
            'base_template' => $this->get('sonata.admin.pool')->getTemplate('layout'),
            'admin_pool' => $this->get('sonata.admin.pool'),
        ]);
    }

    /**
     * @param Request $request
     *
     * @return Response
     */
    public function sendEmailAction(Request $request)
    {
        $username = $request->request->get('username');

        /** @var $userManager \FOS\UserBundle\Model\UserManagerInterface */
        $userManager = $this->get('fos_user.user_manager');

        $user = $userManager->findUserByUsernameOrEmail($username);

        $ttl = $this->container->getParameter('fos_user.resetting.retry_ttl');

        if (null !== $user && !$user->isPasswordRequestNonExpired($ttl)) {
            if (!$user->isAccountNonLocked()) {
                return new RedirectResponse($this->get('router')->generate('sonata_user_admin_resetting_request'));
            }

            if (null === $user->getConfirmationToken()) {
                /** @var $tokenGenerator TokenGeneratorInterface */
                $tokenGenerator = $this->get('fos_user.util.token_generator');
                $user->setConfirmationToken($tokenGenerator->generateToken());
            }

            $this->sendResettingEmailMessage($user);
            $user->setPasswordRequestedAt(new \DateTime());
            $userManager->updateUser($user);
        }

        return new RedirectResponse($this->generateUrl('sonata_user_admin_resetting_check_email', [
            'username' => $username,
        ]));
    }

    /**
     * @param Request $request
     *
     * @return Response
     */
    public function checkEmailAction(Request $request)
    {
        $username = $request->query->get('username');

        if (empty($username)) {
            // the user does not come from the sendEmail action
            return new RedirectResponse($this->generateUrl('sonata_user_admin_resetting_request'));
        }

        return $this->render('@SonataUser/Admin/Security/Resetting/checkEmail.html.twig', [
            'base_template' => $this->get('sonata.admin.pool')->getTemplate('layout'),
            'admin_pool' => $this->get('sonata.admin.pool'),
            'tokenLifetime' => ceil($this->container->getParameter('fos_user.resetting.retry_ttl') / 3600),
        ]);
    }

    /**
     * @param Request $request
     * @param string  $token
     *
     * @return Response
     */
    public function resetAction(Request $request, $token)
    {
        if ($this->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_FULLY')) {
            return new RedirectResponse($this->get('router')->generate('sonata_admin_dashboard'));
        }

        /** @var $formFactory \FOS\UserBundle\Form\Factory\FactoryInterface */
        $formFactory = $this->get('fos_user.resetting.form.factory');
        /** @var $userManager \FOS\UserBundle\Model\UserManagerInterface */
        $userManager = $this->get('fos_user.user_manager');
        /** @var $loginManager \FOS\UserBundle\Security\LoginManagerInterface */
        $loginManager = $this->get('fos_user.security.login_manager');

        $user = $userManager->findUserByConfirmationToken($token);

        $firewallName = $this->container->getParameter('fos_user.firewall_name');

        if (null === $user) {
            throw new NotFoundHttpException(sprintf('The user with "confirmation token" does not exist for value "%s"', $token));
        }

        if (!$user->isPasswordRequestNonExpired($this->container->getParameter('fos_user.resetting.token_ttl'))) {
            return new RedirectResponse($this->generateUrl('sonata_user_admin_resetting_request'));
        }

        $form = $formFactory->createForm();
        $form->setData($user);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user->setConfirmationToken(null);
            $user->setPasswordRequestedAt(null);
            $user->setEnabled(true);

            $message = $this->get('translator')->trans('resetting.flash.success', [], 'FOSUserBundle');
            $this->addFlash('success', $message);
            $response = new RedirectResponse($this->generateUrl('sonata_admin_dashboard'));

            try {
                $loginManager->logInUser($firewallName, $user, $response);
                $user->setLastLogin(new \DateTime());
            } catch (AccountStatusException $ex) {
                // We simply do not authenticate users which do not pass the user
                // checker (not enabled, expired, etc.).
                if ($this->has('logger')) {
                    $this->get('logger')->warning(sprintf(
                        'Unable to login user %d after password reset',
                        $user->getId())
                    );
                }
            }

            $userManager->updateUser($user);

            return $response;
        }

        return $this->render('@SonataUser/Admin/Security/Resetting/reset.html.twig', [
            'token' => $token,
            'form' => $form->createView(),
            'base_template' => $this->get('sonata.admin.pool')->getTemplate('layout'),
            'admin_pool' => $this->get('sonata.admin.pool'),
        ]);
    }

    /**
     * Send an email to a user to confirm the password reset.
     *
     * @param UserInterface $user
     */
    private function sendResettingEmailMessage(UserInterface $user): void
    {
        $url = $this->generateUrl('sonata_user_admin_resetting_reset', [
            'token' => $user->getConfirmationToken(),
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        $rendered = $this->renderView($this->container->getParameter('fos_user.resetting.email.template'), [
            'user' => $user,
            'confirmationUrl' => $url,
        ]);

        // Render the email, use the first line as the subject, and the rest as the body
        $renderedLines = explode(PHP_EOL, trim($rendered));
        $subject = array_shift($renderedLines);
        $body = implode(PHP_EOL, $renderedLines);
        $message = (new \Swift_Message())
            ->setSubject($subject)
            ->setFrom($this->container->getParameter('fos_user.resetting.email.from_email'))
            ->setTo((string) $user->getEmail())
            ->setBody($body);
        $this->get('mailer')->send($message);
    }
}
