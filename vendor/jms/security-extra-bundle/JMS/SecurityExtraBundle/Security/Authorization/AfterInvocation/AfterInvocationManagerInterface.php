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
 * AfterInvocationManagerInterface
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
interface AfterInvocationManagerInterface
{
    /**
     * Makes an access decision after the invocation of a method
     *
     * @param  TokenInterface $token
     * @param  object         $secureObject
     * @param  array          $attributes
     * @param  mixed          $returnedValue the value that was returned by the method invocation
     * @return mixed          the filter return value
     */
    public function decide(TokenInterface $token, $secureObject, array $attributes, $returnedValue);

    /**
     * Determines whether the given attribute is supported
     *
     * @param  string  $attribute
     * @return Boolean
     */
    public function supportsAttribute($attribute);

    /**
     * Determines whether the given class is supported
     *
     * @param  string  $className the class of the secure object
     * @return Boolean
     */
    public function supportsClass($className);
}
