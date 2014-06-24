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

namespace JMS\SecurityExtraBundle\Tests\Security\Authentication\Token;

use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

use Symfony\Component\Security\Core\Role\Role;
use JMS\SecurityExtraBundle\Security\Authentication\Token\RunAsUserToken;

class RunAsUserTokenTest extends \PHPUnit_Framework_TestCase
{
    public function testConstructor()
    {
        $user = $this->getMock('Symfony\Component\Security\Core\User\UserInterface');
        $token = $this->getMock('Symfony\Component\Security\Core\Authentication\Token\TokenInterface');

        $runAsToken = new RunAsUserToken('foo', $user, 'secret', array('ROLE_FOO'), $token);
        $this->assertSame($user, $runAsToken->getUser());
        $this->assertSame('secret', $runAsToken->getCredentials());
        $this->assertSame($token, $runAsToken->getOriginalToken());
        $this->assertEquals(array(new Role('ROLE_FOO')), $runAsToken->getRoles());
        $this->assertSame('foo', $runAsToken->getKey());
    }

    public function testEraseCredentials()
    {
        $token = new RunAsUserToken('foo', 'foo', 'secret', array(), $this->getMock('Symfony\Component\Security\Core\Authentication\Token\TokenInterface'));
        $this->assertEquals('secret', $token->getCredentials());
        $token->eraseCredentials();
        $this->assertNull($token->getCredentials());
    }

    public function testSerializeUnserialize()
    {
        $token = new RunAsUserToken('foo', 'bar', 'secret', array(), new UsernamePasswordToken('foo', 'pass', 'foo', array()));
        $this->assertEquals($token, unserialize(serialize($token)));
    }
}