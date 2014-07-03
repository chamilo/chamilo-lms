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

namespace JMS\AopBundle\Aop;

/**
 * Pointcut Interface.
 *
 * Implementations of this class are responsible for making a decision on whether
 * a certain method call matches the advice which is associated with this pointcut.
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
interface PointcutInterface
{
    /**
     * Determines whether the advice applies to instances of the given class.
     *
     * There are some limits as to what you can do in this method. Namely, you may
     * only base your decision on resources that are part of the ContainerBuilder.
     * Specifically, you may not use any data in the class itself, such as
     * annotations.
     *
     * @param  \ReflectionClass $class
     * @return boolean
     */
    public function matchesClass(\ReflectionClass $class);

    /**
     * Determines whether the advice applies to the given method.
     *
     * This method is not limited in the way the matchesClass method is. It may
     * use information in the associated class to make its decision.
     *
     * @param  \ReflectionMethod $method
     * @return boolean
     */
    public function matchesMethod(\ReflectionMethod $method);
}
