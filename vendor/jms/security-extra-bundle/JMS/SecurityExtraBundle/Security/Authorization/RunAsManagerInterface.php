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

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * RunAsManagerInterface
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
interface RunAsManagerInterface
{
    /**
     * Creates a temporary RunAsToken.
     *
     * The returned Token must have a complementing AuthenticationProvider implementation.
     *
     * @param  TokenInterface $token        the original Token
     * @param  object         $secureObject the secure object which caused this call
     * @param  array          $attributes   an array of attributes to apply to the built token
     * @return TokenInterface
     */
    public function buildRunAs(TokenInterface $token, $secureObject, array $attributes);

    /**
     * Whether this RunAsManager supports the given attribute
     *
     * @param  string  $attribute
     * @return Boolean
     */
    public function supportsAttribute($attribute);

    /**
     * Whether this RunAsManager supports the given class.
     *
     * @param  string  $className The class of the secure object which requests RunAs capabilities
     * @return Boolean
     */
    public function supportsClass($className);
}
