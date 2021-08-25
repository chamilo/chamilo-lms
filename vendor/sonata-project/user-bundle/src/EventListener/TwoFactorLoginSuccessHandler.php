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

namespace Sonata\UserBundle\EventListener;

use Sonata\UserBundle\GoogleAuthenticator\Helper;
use Sonata\UserBundle\Model\User;
use Sonata\UserBundle\Model\UserManagerInterface;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;

/**
 * Class TwoFactorLoginSuccessHandler is used for handling 2FA authorization for enabled roles and ips.
 *
 * @author Aleksej Krichevsky <krich.al.vl@gmail.com>
 */
final class TwoFactorLoginSuccessHandler implements AuthenticationSuccessHandlerInterface
{
    /**
     * @var EngineInterface
     */
    private $engine;

    /**
     * @var Helper
     */
    private $googleAuthenticator;

    /**
     * @var UserManagerInterface
     */
    private $userManager;

    public function __construct(
        EngineInterface $engine,
        Helper $helper,
        UserManagerInterface $userManager
    ) {
        $this->engine = $engine;
        $this->googleAuthenticator = $helper;
        $this->userManager = $userManager;
    }

    /**
     * @return RedirectResponse|Response
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token)
    {
        /** @var $user User */
        $user = $token->getUser();
        $redirectResponse = new RedirectResponse('/admin');

        $needToHave2FA = $this->googleAuthenticator->needToHaveGoogle2FACode($request);

        if ($needToHave2FA && !$user->getTwoStepVerificationCode()) {
            $secret = $this->googleAuthenticator->generateSecret();
            $user->setTwoStepVerificationCode($secret);

            $qrCodeUrl = $this->googleAuthenticator->getUrl($user);
            $this->userManager->updateUser($user);

            return $this->engine->renderResponse(
                '@SonataUser/Admin/Security/login.html.twig',
                [
                    'qrCodeUrl' => $qrCodeUrl,
                    'qrSecret' => $secret,
                    'base_template' => '@SonataAdmin/standard_layout.html.twig',
                    'error' => [],
                ]
            );
        } elseif ($needToHave2FA && $user->getTwoStepVerificationCode()) {
            $request->getSession()->set($this->googleAuthenticator->getSessionKey($token), null);
        }

        return $redirectResponse;
    }
}
