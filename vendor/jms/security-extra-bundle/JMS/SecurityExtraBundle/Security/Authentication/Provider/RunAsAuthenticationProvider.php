<?php

/*
 * Copyright 2011 Johannes M. Schmitt <schmittjoh@gmail.com>
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace JMS\SecurityExtraBundle\Security\Authentication\Provider;

use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use JMS\SecurityExtraBundle\Security\Authentication\Token\RunAsUserToken;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Provider\AuthenticationProviderInterface;

/**
 * Class which authenticates RunAsTokens.
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
class RunAsAuthenticationProvider implements AuthenticationProviderInterface
{
    private $key;

    public function __construct($key)
    {
        $this->key = $key;
    }

    public function authenticate(TokenInterface $token)
    {
        if (!$this->supports($token)) {
            return null;
        }

        if ($token->getKey() === $this->key) {
            return $token;
        } else {
            throw new BadCredentialsException('The keys do not match.');
        }
    }

    public function supports(TokenInterface $token)
    {
        return $token instanceof RunAsUserToken;
    }
}
