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

namespace JMS\SecurityExtraBundle\Tests\Security\Authorization\AfterInvocation;

use Symfony\Component\Security\Acl\Exception\NoAceFoundException;

use Symfony\Component\Security\Acl\Exception\AclNotFoundException;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use JMS\SecurityExtraBundle\Security\Authorization\AfterInvocation\AclAfterInvocationProvider;

class AclAfterInvocationProviderTest extends \PHPUnit_Framework_TestCase
{
    public function testDecideReturnsNullWhenObjectIsNull()
    {
        $provider = new AclAfterInvocationProvider($this->getAclProvider(), $this->getOidStrategy(), $this->getSidStrategy(), $this->getPermissionMap());

        $this->assertNull($provider->decide($this->getToken(), null, array(), null));
    }

    public function testDecideDoesNotModifyReturnedObjectWhenNoAttributeIsSupported()
    {
        $provider = new AclAfterInvocationProvider($this->getAclProvider(), $this->getOidStrategy(), $this->getSidStrategy(), $this->getPermissionMap());

        $returnedObject = new \stdClass;
        $this->assertSame($returnedObject, $provider->decide($this->getToken(), null, array('foo', 'moo'), $returnedObject));
    }

    public function testDecideDoesNotModifyReturnedObjectWhenNoObjectIdentityCanBeRetrieved()
    {
        $oidStrategy = $this->getOidStrategy();
        $oidStrategy
            ->expects($this->once())
            ->method('getObjectIdentity')
            ->will($this->returnValue(null))
        ;

        $permissionMap = $this->getPermissionMap();
        $permissionMap
            ->expects($this->once())
            ->method('contains')
            ->will($this->returnValue(true))
        ;

        $returnedObject = array('foo' => 'moo');
        $provider = new AclAfterInvocationProvider($this->getAclProvider(), $oidStrategy, $this->getSidStrategy(), $permissionMap);
        $this->assertSame($returnedObject, $provider->decide($this->getToken(), null, array('foo'), $returnedObject));
    }

    /**
     * @expectedException Symfony\Component\Security\Core\Exception\AccessDeniedException
     */
    public function testDecideThrowsAccessDeniedExceptionWhenNoAclIsFound()
    {
        $oidStrategy = $this->getOidStrategy();
        $oidStrategy
            ->expects($this->once())
            ->method('getObjectIdentity')
            ->will($this->returnValue(new ObjectIdentity(1, 'foo')))
        ;

        $sidStrategy = $this->getSidStrategy();
        $sidStrategy
            ->expects($this->once())
            ->method('getSecurityIdentities')
            ->will($this->returnValue(array()))
        ;

        $permissionMap = $this->getPermissionMap();
        $permissionMap
            ->expects($this->once())
            ->method('contains')
            ->will($this->returnValue(true))
        ;

        $aclProvider = $this->getAclProvider();
        $aclProvider
            ->expects($this->once())
            ->method('findAcl')
            ->will($this->throwException(new AclNotFoundException('No ACL')))
        ;

        $provider = new AclAfterInvocationProvider($aclProvider, $oidStrategy, $sidStrategy, $permissionMap);
        $provider->decide($this->getToken(), null, array('foo'), 'foo');
    }

    /**
     * @expectedException Symfony\Component\Security\Core\Exception\AccessDeniedException
     */
    public function testDecideThrowsAccessDeniedExceptionWhenNoAceIsFound()
    {
        $masks = array(1, 3);
        $permissionMap = $this->getPermissionMap();
        $permissionMap
            ->expects($this->once())
            ->method('contains')
            ->will($this->returnValue(true))
        ;
        $permissionMap
            ->expects($this->once())
            ->method('getMasks')
            ->will($this->returnValue($masks))
        ;

        $acl = $this->getMock('Symfony\Component\Security\Acl\Model\AclInterface');
        $acl
            ->expects($this->once())
            ->method('isGranted')
            ->will($this->throwException(new NoAceFoundException('No ACE')))
        ;

        $aclProvider = $this->getAclProvider();
        $aclProvider
            ->expects($this->once())
            ->method('findAcl')
            ->will($this->returnValue($acl))
        ;

        $oidStrategy = $this->getOidStrategy();
        $oidStrategy
            ->expects($this->once())
            ->method('getObjectIdentity')
            ->will($this->returnValue(new ObjectIdentity(1, 'foo')))
        ;

        $sidStrategy = $this->getSidStrategy();
        $sidStrategy
            ->expects($this->once())
            ->method('getSecurityIdentities')
            ->will($this->returnValue(array('foo')))
        ;

        $provider = new AclAfterInvocationProvider($aclProvider, $oidStrategy, $sidStrategy, $permissionMap);
        $provider->decide($this->getToken(), null, array('foo'), array('foo'));
    }

    public function testDecide()
    {
        $masks = array(1, 3);
        $permissionMap = $this->getPermissionMap();
        $permissionMap
            ->expects($this->once())
            ->method('contains')
            ->will($this->returnValue(true))
        ;
        $permissionMap
            ->expects($this->once())
            ->method('getMasks')
            ->will($this->returnValue($masks))
        ;

        $acl = $this->getMock('Symfony\Component\Security\Acl\Model\AclInterface');
        $acl
            ->expects($this->once())
            ->method('isGranted')
            ->will($this->returnValue(true))
        ;

        $aclProvider = $this->getAclProvider();
        $aclProvider
            ->expects($this->once())
            ->method('findAcl')
            ->will($this->returnValue($acl))
        ;

        $oidStrategy = $this->getOidStrategy();
        $oidStrategy
            ->expects($this->once())
            ->method('getObjectIdentity')
            ->will($this->returnValue(new ObjectIdentity(1, 'foo')))
        ;

        $sidStrategy = $this->getSidStrategy();
        $sidStrategy
            ->expects($this->once())
            ->method('getSecurityIdentities')
            ->will($this->returnValue(array('foo')))
        ;

        $provider = new AclAfterInvocationProvider($aclProvider, $oidStrategy, $sidStrategy, $permissionMap);
        $this->assertSame(array('foo'), $provider->decide($this->getToken(), null, array('foo'), array('foo')));
    }

    public function testSupportsAttribute()
    {
        $aclProvider = $this->getAclProvider();
        $oidStrategy = $this->getOidStrategy();
        $sidStrategy = $this->getSidStrategy();
        $permissionMap = $this->getPermissionMap();

        $permissionMap
            ->expects($this->at(0))
            ->method('contains')
            ->with($this->equalTo('foo'))
            ->will($this->returnValue(true))
        ;
        $permissionMap
            ->expects($this->at(1))
            ->method('contains')
            ->with($this->equalTo('asdf'))
            ->wilL($this->returnValue(false))
        ;

        $provider = new AclAfterInvocationProvider($aclProvider, $oidStrategy, $sidStrategy, $permissionMap);
        $this->assertTrue($provider->supportsAttribute('foo'));
        $this->assertFalse($provider->supportsAttribute('asdf'));
    }

    protected function getToken()
    {
        return $this->getMock('Symfony\Component\Security\Core\Authentication\Token\TokenInterface');
    }

    protected function getPermissionMap()
    {
        return $this->getMock('Symfony\Component\Security\Acl\Permission\PermissionMapInterface');
    }

    protected function getAclProvider()
    {
        return $this->getMock('Symfony\Component\Security\Acl\Model\AclProviderInterface');
    }

    protected function getOidStrategy()
    {
        return $this->getMock('Symfony\Component\Security\Acl\Model\ObjectIdentityRetrievalStrategyInterface');
    }

    protected function getSidStrategy()
    {
        return $this->getMock('Symfony\Component\Security\Acl\Model\SecurityIdentityRetrievalStrategyInterface');
    }
}
