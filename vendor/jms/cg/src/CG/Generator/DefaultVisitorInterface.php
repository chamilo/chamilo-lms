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

namespace CG\Generator;

/**
 * The visitor interface required by the DefaultNavigator.
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
interface DefaultVisitorInterface
{
    /**
     * Resets the visitors internal state to allow re-using the same instance.
     *
     * @return void
     */
    function reset();

    function startVisitingClass(PhpClass $class);
    function startVisitingConstants();
    function visitConstant($name, $value);
    function endVisitingConstants();
    function startVisitingProperties();
    function visitProperty(PhpProperty $property);
    function endVisitingProperties();
    function startVisitingMethods();
    function visitMethod(PhpMethod $method);
    function endVisitingMethods();
    function endVisitingClass(PhpClass $class);
    function visitFunction(PhpFunction $function);
}