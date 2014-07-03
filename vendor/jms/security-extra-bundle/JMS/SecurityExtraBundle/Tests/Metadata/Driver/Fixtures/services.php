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

namespace JMS\SecurityExtraBundle\Tests\Mapping\Driver;

use JMS\SecurityExtraBundle\Annotation\SecureReturn;
use JMS\SecurityExtraBundle\Annotation\SecureParam;
use JMS\SecurityExtraBundle\Annotation\Secure;

class FooService implements FooInterface
{
    /**
     * @Secure(roles="ROLE_USER, ROLE_ADMIN, ROLE_SUPERADMIN")
     * @SecureParam(name="param", permissions="VIEW")
     */
    public function foo($param, $anotherParam) { }

    /**
     * @Secure("ROLE_FOO, ROLE_BAR")
     */
    public function shortNotation() { }
    
    /**     
     * @Secure(roles={"ROLE_FOO", "ROLE_BAR"})
     * @SecureParam(name="param", permissions={"OWNER"})     
     * @SecureReturn(permissions={"MASTER"})
     */
    public function bar($param) { }
}
/**
  * @SecureParam(name="param", permissions="VIEW")
  */
class FooSecureService implements FooInterface
{
    /**
     * @SecureParam(name="anotherParam", permissions="EDIT")
     */
    public function foo($param, $anotherParam) {}

    public function baz($param) {}
}
/**
 * @SecureParam(name="param", permissions="VIEW")
 * @SecureParam(name="anotherParam", permissions="EDIT")
 */
class FooMultipleSecureService implements FooInterface
{
    public function foo($param, $anotherParam) {}
}
interface FooInterface
{
    /**
     * @SecureParam(name="param", permissions="OWNER")
     * @SecureParam(name="anotherParam", permissions="EDIT")
     * @SecureReturn(permissions="MASTER")
     */
    public function foo($param, $anotherParam);
}
