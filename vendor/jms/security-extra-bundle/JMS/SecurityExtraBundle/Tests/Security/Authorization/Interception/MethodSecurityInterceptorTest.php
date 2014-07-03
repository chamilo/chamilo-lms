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

namespace JMS\SecurityExtraBundle\Tests\Security\Authorization\Interception;

use JMS\SecurityExtraBundle\Exception\RuntimeException;
use JMS\SecurityExtraBundle\Metadata\MethodMetadata;

use JMS\SecurityExtraBundle\Metadata\ClassMetadata;

use Metadata\MetadataFactoryInterface;

use JMS\SecurityExtraBundle\Security\Authentication\Token\RunAsUserToken;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use JMS\SecurityExtraBundle\Security\Authorization\Interception\MethodSecurityInterceptor;
use CG\Proxy\MethodInvocation;

class MethodSecurityInterceptorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @expectedException Symfony\Component\Security\Core\Exception\AuthenticationCredentialsNotFoundException
     */
    public function testInvokeThrowsExceptionWhenSecurityContextHasNoToken()
    {
        list($interceptor, $securityContext,,,,) = $this->getInterceptor();

        $securityContext
            ->expects($this->once())
            ->method('getToken')
            ->will($this->returnValue(null))
        ;

        $this->getInvocation($interceptor)->proceed();
    }

    /**
     * @expectedException Symfony\Component\Security\Core\Exception\AuthenticationException
     */
    public function testInvokeAuthenticatesTokenIfItIsNotYetAuthenticated()
    {
        list($interceptor, $securityContext, $authManager,,,) = $this->getInterceptor();

        $token = $this->getMock('Symfony\Component\Security\Core\Authentication\Token\TokenInterface');
        $token
            ->expects($this->once())
            ->method('isAuthenticated')
            ->will($this->returnValue(false))
        ;

        $securityContext
            ->expects($this->once())
            ->method('getToken')
            ->will($this->returnValue($token))
        ;

        $authManager
            ->expects($this->once())
            ->method('authenticate')
            ->will($this->throwException(new AuthenticationException('Could not authenticate.')))
        ;

        $this->getInvocation($interceptor)->proceed();
    }

    /**
     * @expectedException Symfony\Component\Security\Core\Exception\AuthenticationException
     */
    public function testInvokeAuthenticatesTokenIfAlwaysAuthenticateIsTrue()
    {
        list($interceptor, $securityContext, $authManager,,,) = $this->getInterceptor();

        $token = $this->getMock('Symfony\Component\Security\Core\Authentication\Token\TokenInterface');

        $securityContext
            ->expects($this->once())
            ->method('getToken')
            ->will($this->returnValue($token))
        ;

        $authManager
            ->expects($this->once())
            ->method('authenticate')
            ->will($this->throwException(new AuthenticationException('Could not authenticate.')))
        ;

        $invocation = $this->getInvocation($interceptor);
        $interceptor->setAlwaysAuthenticate(true);

        $invocation->proceed();
    }

    /**
     * @expectedException Symfony\Component\Security\Core\Exception\AccessDeniedException
     */
    public function testInvokeCallsADMForRolesAndThrowsExceptionWhenInsufficientPriviledges()
    {
        $factory = $this->getMock('Metadata\MetadataFactoryInterface');
        $metadata = new ClassMetadata('JMS\SecurityExtraBundle\Tests\Security\Authorization\Interception\SecureService');
        $metadata->methodMetadata['foo'] = new MethodMetadata('JMS\SecurityExtraBundle\Tests\Security\Authorization\Interception\SecureService', 'foo');
        $metadata->methodMetadata['foo']->roles = array('ROLE_FOO');
        $factory
            ->expects($this->once())
            ->method('getMetadataForClass')
            ->with($this->equalTo($metadata->reflection->name))
            ->will($this->returnValue($metadata))
        ;

        list($interceptor, $context, $authManager, $adm,,) = $this->getInterceptor($factory);

        $token = $this->getMock('Symfony\Component\Security\Core\Authentication\Token\TokenInterface');
        $token
            ->expects($this->once())
            ->method('isAuthenticated')
            ->will($this->returnValue(false))
        ;

        $context
            ->expects($this->once())
            ->method('getToken')
            ->will($this->returnValue($token))
        ;
        $context
            ->expects($this->once())
            ->method('setToken')
            ->with($this->equalTo($token))
        ;

        $authManager
            ->expects($this->once())
            ->method('authenticate')
            ->will($this->returnValue($token))
        ;

        $invocation = $this->getInvocation($interceptor);
        $adm
            ->expects($this->once())
            ->method('decide')
            ->with($this->equalTo($token), $this->equalTo(array('ROLE_FOO')), $this->equalTo($invocation))
            ->will($this->returnValue(false))
        ;

        $invocation->proceed();
    }

    /**
     * @expectedException Symfony\Component\Security\Core\Exception\AccessDeniedException
     */
    public function testInvokeCallsADMForEachParamPermissionsAndThrowsExceptionOnInsufficientPermissions()
    {
        $factory = $this->getMock('Metadata\MetadataFactoryInterface');
        $metadata = new ClassMetadata('JMS\SecurityExtraBundle\Tests\Security\Authorization\Interception\SecureService');
        $metadata->methodMetadata['foo'] = new MethodMetadata('JMS\SecurityExtraBundle\Tests\Security\Authorization\Interception\SecureService', 'foo');
        $metadata->methodMetadata['foo']->paramPermissions = array(
            $p0 = array('ROLE_FOO', 'ROLE_ASDF'),
            $p1 = array('ROLE_MOO'),
        );
        $factory
            ->expects($this->once())
            ->method('getMetadataForClass')
            ->with($this->equalTo($metadata->reflection->name))
            ->will($this->returnValue($metadata))
        ;

        list($interceptor, $context,, $adm,,) = $this->getInterceptor($factory);

        $context
            ->expects($this->once())
            ->method('getToken')
            ->will($this->returnValue($token = $this->getToken()))
        ;

        $invocation = $this->getInvocation($interceptor);
        $adm
            ->expects($this->at(0))
            ->method('decide')
            ->with($this->equalTo($token), $this->equalTo($p0), $this->equalTo(new \stdClass()))
            ->will($this->returnValue(true))
        ;
        $adm
            ->expects($this->at(1))
            ->method('decide')
            ->with($this->equalTo($token), $this->equalTo($p1), $this->equalTo(new \stdClass()))
            ->will($this->returnValue(false))
        ;

        $invocation->proceed();
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testInvokehandlesExceptionsFromWithintheInvokedMethodGracefully()
    {
        $factory = $this->getMock('Metadata\MetadataFactoryInterface');
        $metadata = new ClassMetadata('JMS\SecurityExtraBundle\Tests\Security\Authorization\Interception\SecureService');
        $metadata->methodMetadata['throwException'] = new MethodMetadata('JMS\SecurityExtraBundle\Tests\Security\Authorization\Interception\SecureService', 'foo');
        $metadata->methodMetadata['throwException']->runAsRoles = array('ROLE_FOO');
        $factory
            ->expects($this->once())
            ->method('getMetadataForClass')
            ->with($this->equalTo($metadata->reflection->name))
            ->will($this->returnValue($metadata))
        ;

        list($interceptor, $context,,,, $runAsManager) = $this->getInterceptor($factory);
        $invocation = $this->getInvocation($interceptor, 'throwException');

        $token = $this->getToken();
        $context
            ->expects($this->once())
            ->method('getToken')
            ->will($this->returnValue($token))
        ;

        $runAsToken = new RunAsUserToken('asdf', 'user', 'foo', array('ROLE_FOO'), $token);
        $runAsManager
            ->expects($this->once())
            ->method('buildRunAs')
            ->will($this->returnValue($runAsToken))
        ;

        $context
            ->expects($this->exactly(2))
            ->method('setToken')
        ;

        $invocation->proceed();
    }

    protected function getToken($isAuthenticated = true)
    {
        $token = $this->getMock('Symfony\Component\Security\Core\Authentication\Token\TokenInterface');
        $token
            ->expects($this->once())
            ->method('isAuthenticated')
            ->will($this->returnValue($isAuthenticated))
        ;

        return $token;
    }

    protected function getInterceptor(MetadataFactoryInterface $metadataFactory = null)
    {
        if (null === $metadataFactory) {
            $metadataFactory = $this->getMock('Metadata\MetadataFactoryInterface');

            $metadata = new ClassMetadata('JMS\SecurityExtraBundle\Tests\Security\Authorization\Interception\SecureService');
            $metadata->methodMetadata['foo'] = new MethodMetadata('JMS\SecurityExtraBundle\Tests\Security\Authorization\Interception\SecureService', 'foo');

            $metadataFactory
                ->expects($this->once())
                ->method('getMetadataForClass')
                ->with($this->equalTo('JMS\SecurityExtraBundle\Tests\Security\Authorization\Interception\SecureService'))
                ->will($this->returnValue($metadata))
            ;
        }

        $securityContext = $this->getMockBuilder('Symfony\Component\Security\Core\SecurityContext')
                            ->disableOriginalConstructor()
                            ->getMock();

        $authenticationManager = $this->getMock('Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface');
        $accessDecisionManager = $this->getMock('Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface');
        $afterInvocationManager = $this->getMock('JMS\SecurityExtraBundle\Security\Authorization\AfterInvocation\AfterInvocationManagerInterface');
        $runAsManager = $this->getMock('JMS\SecurityExtraBundle\Security\Authorization\RunAsManagerInterface');

        return array(
            new MethodSecurityInterceptor($securityContext, $authenticationManager, $accessDecisionManager, $afterInvocationManager, $runAsManager, $metadataFactory),
            $securityContext,
            $authenticationManager,
            $accessDecisionManager,
            $afterInvocationManager,
            $runAsManager,
        );
    }

    protected function getInvocation(MethodSecurityInterceptor $interceptor, $method = 'foo', $arguments = array())
    {
        if ('foo' === $method && 0 === count($arguments)) {
            $arguments = array(new \stdClass(), new \stdClass());
        }
        $object = new SecureService();

        return new MethodInvocation(new \ReflectionMethod($object, $method), $object, $arguments, array($interceptor));
    }
}

class SecureService
{
    public function foo($param, $other)
    {
        return $param;
    }

    public function throwException()
    {
        throw new RuntimeException;
    }
}
