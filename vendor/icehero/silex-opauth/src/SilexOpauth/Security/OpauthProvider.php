<?php

namespace SilexOpauth\Security;

use Symfony\Component\Security\Core\Authentication\Provider\AuthenticationProviderInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * @author Rafal Lindemann
 *  */
class OpauthProvider implements AuthenticationProviderInterface
{

    protected $userProvider;

    public function __construct(UserProviderInterface $userProvider)
    {
        $this->userProvider = $userProvider;
    }


    public function authenticate(TokenInterface $token)
    {
        /* @var $token OpauthToken */
        if ($this->userProvider instanceof OpauthUserProviderInterface) {
            $user = $this->userProvider->loadUserByOpauthResult($token->getOpauthResult());
        } else {
            $username = $token->getOpauthResult()->getProvider() . ':' . $token->getOpauthResult()->getUid();
            $user = $this->userProvider->loadUserByUsername($username);
        }

        if ($user) {
            $authenticatedToken = new OpauthToken($token->getOpauthResult(), $user->getRoles());
            $authenticatedToken->setUser($user);

            return $authenticatedToken;
        }

        throw new AuthenticationException('The OPauth authentication failed.');
    }


    public function supports(TokenInterface $token)
    {
        return $token instanceof OpauthToken;
    }


}