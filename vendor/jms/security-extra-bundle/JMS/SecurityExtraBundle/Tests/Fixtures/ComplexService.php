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

namespace JMS\SecurityExtraBundle\Tests\Fixtures;

use JMS\SecurityExtraBundle\Annotation\SecureReturn;
use JMS\SecurityExtraBundle\Annotation\SecureParam;
use JMS\SecurityExtraBundle\Annotation\Secure;

interface E
{
    /**
     * @SecureReturn(permissions="VIEW,UNDELETE")
     */
    public function retrieve();
}
interface F
{
    /**
     * @SecureParam(name="secure", permissions="OWNER")
     * @SecureParam(name="foo", permissions="MASTER, EDIT")
     */
    public function delete($foo, $asdf, $secure);
}
interface C { }
interface D extends F {}
interface B extends C, E { }
abstract class G implements F, E
{
    /**
     * @Secure(roles="ROLE_FOO, IS_AUTHENTICATED_FULLY")
     * @SecureParam(name="secure", permissions="FOO")
     * @SecureReturn(permissions="WOW")
     */
    abstract public function abstractMethod($foo, $secure);
}
class A extends G implements C, B, D
{
    public function retrieve() { }
    public function delete($one, $two, $three) { }
    public function abstractMethod($asdf, $wohoo) { }
}
class ComplexService extends A implements C { }
