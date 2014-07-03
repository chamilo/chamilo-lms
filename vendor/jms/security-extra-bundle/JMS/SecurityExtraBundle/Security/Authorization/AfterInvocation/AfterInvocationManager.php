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

namespace JMS\SecurityExtraBundle\Security\Authorization\AfterInvocation;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * This is the pendant to the AccessDecisionManager which is used to make
 * access decisions after a method has been executed.
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
class AfterInvocationManager implements AfterInvocationManagerInterface
{
    private $providers;

    public function __construct(array $providers)
    {
        $this->providers = $providers;
    }

    /**
     * {@inheritDoc}
     */
    public function decide(TokenInterface $token, $secureInvocation, array $attributes, $returnedObject)
    {
        foreach ($this->providers as $provider) {
            $returnedObject = $provider->decide($token, $secureInvocation, $attributes, $returnedObject);
        }

        return $returnedObject;
    }

    /**
     * {@inheritDoc}
     */
    public function supportsAttribute($attribute)
    {
        foreach ($this->providers as $provider) {
            if (true === $provider->supportsAttribute($attribute)) {
                return true;
            }
        }

        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function supportsClass($className)
    {
        foreach ($this->providers as $provider) {
            if (true === $provider->supportsClass($className)) {
                return true;
            }
        }

        return false;
    }
}
