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

namespace JMS\SecurityExtraBundle\Security\Authorization\Voter;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

/**
 * This voter adds a special role "ROLE_IDDQD" which effectively bypasses any,
 * and all security checks.
 *
 * Most of the time, you will want to use this rule in combination with a
 * @RunAs annotation to disable security checks for the invocation of a
 * specific method.
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
class IddqdVoter implements VoterInterface
{
    /** @var array */
    private $iddqdAliases = array('ROLE_IDDQD');

    /** @var array */
    private $ignoredRoles;

    public function __construct(array $iddqdAliases, array $ignoredRoles)
    {
        $this->iddqdAliases = array_merge($this->iddqdAliases, $iddqdAliases);
        $this->ignoredRoles = $ignoredRoles;
    }

    public function vote(TokenInterface $token, $object, array $attributes)
    {
        if (! $this->isIddqd($token) || $this->isIgnoredRole($attributes)) {
            return VoterInterface::ACCESS_ABSTAIN;
        }

        return VoterInterface::ACCESS_GRANTED;
    }

    protected function isIgnoredRole(array $attributes)
    {
        foreach ($attributes as $attribute) {
            if (in_array($attribute, $this->ignoredRoles, true)) {
                return true;
            }
        }

        return false;
    }

    protected function isIddqd(TokenInterface $token)
    {
        foreach ($token->getRoles() as $role) {
            if (in_array($role->getRole(), $this->iddqdAliases, true)) {
                return true;
            }
        }

        return false;
    }

    public function supportsAttribute($attribute)
    {
        return true;
    }

    public function supportsClass($class)
    {
        return true;
    }
}
