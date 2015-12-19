<?php

/*
 * This file is part of the Sonata project.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\UserBundle\GoogleAuthenticator;

use Google\Authenticator\GoogleAuthenticator as BaseGoogleAuthenticator;
use Sonata\UserBundle\Model\UserInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

class Helper
{
    protected $server;

    protected $authenticator;

    /**
     * @param $server
     * @param \Google\Authenticator\GoogleAuthenticator $authenticator
     */
    public function __construct($server, BaseGoogleAuthenticator $authenticator)
    {
        $this->server = $server;
        $this->authenticator = $authenticator;
    }

    /**
     * @param \Sonata\UserBundle\Model\UserInterface $user
     * @param $code
     * @return bool
     */
    public function checkCode(UserInterface $user, $code)
    {
        return $this->authenticator->checkCode($user->getTwoStepVerificationCode(), $code);
    }

    /**
     * @param  \Sonata\UserBundle\Model\UserInterface $user
     * @return string
     */
    public function getUrl(UserInterface $user)
    {
        return $this->authenticator->getUrl($user->getUsername(), $this->server, $user->getTwoStepVerificationCode());
    }

    /**
     * @return string
     */
    public function generateSecret()
    {
        return $this->authenticator->generateSecret();
    }

    /**
     * @param  \Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken $token
     * @return string
     */
    public function getSessionKey(UsernamePasswordToken $token)
    {
        return sprintf('sonata_user_google_authenticator_%s_%s', $token->getProviderKey(), $token->getUsername());
    }
}
