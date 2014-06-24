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

namespace JMS\SecurityExtraBundle\Security\Authorization;

use JMS\SecurityExtraBundle\Security\Authentication\Token\RunAsUserToken;
use Symfony\Component\Security\Core\Role\Role;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * The RunAsManager creates throw-away Tokens which are temporarily injected into
 * the security context for the duration of the invocation of a specific method.
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
class RunAsManager implements RunAsManagerInterface
{
    private $key;
    private $rolePrefix;

    public function __construct($key, $rolePrefix = 'ROLE_')
    {
        $this->key = $key;
        $this->rolePrefix = $rolePrefix;
    }

    /**
     * {@inheritDoc}
     */
    public function buildRunAs(TokenInterface $token, $secureObject, array $attributes)
    {
        $roles = array();
        foreach ($attributes as $attribute) {
            if ($this->supportsAttribute($attribute)) {
                $roles[] = new Role($attribute);
            }
        }

        if (0 === count($roles)) {
            return null;
        }

        $roles = array_merge($roles, $token->getRoles());

        return new RunAsUserToken($this->key, $token->getUser(), $token->getCredentials(), $roles, $token);
    }

    /**
     * {@inheritDoc}
     */
    public function supportsAttribute($attribute)
    {
        return !empty($attribute) && 0 === strpos($attribute, $this->rolePrefix);
    }

    /**
     * {@inheritDoc}
     */
    public function supportsClass($className)
    {
        return true;
    }
}
