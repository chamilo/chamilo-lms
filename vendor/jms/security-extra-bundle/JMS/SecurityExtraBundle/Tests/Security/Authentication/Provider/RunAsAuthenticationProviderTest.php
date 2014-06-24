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

namespace JMS\SecurityExtraBundle\Tests\Security\Authentication\Provider;

use JMS\SecurityExtraBundle\Security\Authentication\Token\RunAsUserToken;

use JMS\SecurityExtraBundle\Security\Authentication\Provider\RunAsAuthenticationProvider;

class RunAsAuthenticationProviderTest extends \PHPUnit_Framework_TestCase
{
    public function testAuthenticateReturnsNullIfTokenISUnsupported()
    {
        $provider = new RunAsAuthenticationProvider('foo');
        $token = $this->getMock('Symfony\Component\Security\Core\Authentication\Token\TokenInterface');

        $this->assertNull($provider->authenticate($token));
    }

    /**
     * @expectedException Symfony\Component\Security\Core\Exception\BadCredentialsException
     */
    public function testAuthenticateThrowsExceptionWhenKeysDontMatch()
    {
        $provider = new RunAsAuthenticationProvider('foo');
        $token = $this->getSupportedToken();
        $token
            ->expects($this->once())
            ->method('getKey')
            ->will($this->returnValue('moo'))
        ;

        $provider->authenticate($token);
    }

    public function testAuthenticate()
    {
        $provider = new RunAsAuthenticationProvider('foo');
        $token = $this->getSupportedToken();
        $token
            ->expects($this->once())
            ->method('getKey')
            ->will($this->returnValue('foo'))
        ;

        $this->assertSame($token, $provider->authenticate($token));
    }

    public function testSupportsDoesNotAcceptInvalidToken()
    {
        $provider = new RunAsAuthenticationProvider('foo');
        $token = $this->getMock('Symfony\Component\Security\Core\Authentication\Token\TokenInterface');

        $this->assertFalse($provider->supports($token));
    }

    public function testSupports()
    {
        $provider = new RunAsAuthenticationProvider('foo');

        $token = $this->getSupportedToken();
        $this->assertTrue($provider->supports($token));
    }

    protected function getSupportedToken()
    {
        return $this->getMockBuilder('JMS\SecurityExtraBundle\Security\Authentication\Token\RunAsUserToken')
                ->disableOriginalConstructor()
                ->getMock();
    }
}
