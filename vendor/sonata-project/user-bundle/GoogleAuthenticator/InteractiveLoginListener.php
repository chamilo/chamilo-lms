<?php

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\UserBundle\GoogleAuthenticator;

use Sonata\UserBundle\Model\UserInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;

class InteractiveLoginListener
{
    /**
     * @var Helper
     */
    protected $helper;

    /**
     * @param Helper $helper
     */
    public function __construct(Helper $helper)
    {
        $this->helper = $helper;
    }

    /**
     * @param InteractiveLoginEvent $event
     */
    public function onSecurityInteractiveLogin(InteractiveLoginEvent $event)
    {
        if (!$event->getAuthenticationToken() instanceof UsernamePasswordToken) {
            return;
        }

        $token = $event->getAuthenticationToken();

        if (!$token->getUser() instanceof UserInterface) {
            return;
        }

        if (!$token->getUser()->getTwoStepVerificationCode()) {
            return;
        }

        $event->getRequest()->getSession()->set($this->helper->getSessionKey($token), null);
    }
}
