<?php

namespace JMS\SecurityExtraBundle\Tests\Security\Acl\Expression;

use Symfony\Component\Security\Acl\Exception\NoAceFoundException;

use Symfony\Component\Security\Acl\Exception\AclNotFoundException;

use JMS\SecurityExtraBundle\Security\Acl\Expression\PermissionEvaluator;

class PermissionEvaluatorTest extends \PHPUnit_Framework_TestCase
{
    private $token;
    private $object;
    private $oid;
    private $sid;
    private $acl;
    private $evaluator;
    private $provider;
    private $oidStrategy;
    private $sidStrategy;
    private $permissionMap;

    public function testHasPermission()
    {
        $this->permissionMap->expects($this->once())
            ->method('getMasks')
            ->with('VIEW', $this->oid)
            ->will($this->returnValue(array(1, 2, 3, 4)));
        $this->sidStrategy->expects($this->once())
            ->method('getSecurityIdentities')
            ->with($this->token)
            ->will($this->returnValue(array($this->sid)));

        $this->provider->expects($this->once())
            ->method('findAcl')
            ->with($this->oid)
            ->will($this->returnValue($this->acl));

        $this->acl->expects($this->once())
            ->method('isGranted')
            ->with(array(1, 2, 3, 4), array($this->sid), false)
            ->will($this->returnValue(true));

        $this->assertTrue($this->evaluator->hasPermission($this->token, $this->oid, 'VIEW'));
    }

    public function testHasPermissionReturnsFalseWhenNoMasksExist()
    {
        $this->permissionMap->expects($this->once())
            ->method('getMasks')
            ->with('FOO', $this->object)
            ->will($this->returnValue(null));

        $this->assertFalse($this->evaluator->hasPermission($this->token, $this->object, 'FOO'));
    }

    public function testHasPermissionWhenNoObjectIsGiven()
    {
        $this->permissionMap->expects($this->once())
            ->method('getMasks')
            ->with('FOO', null)
            ->will($this->returnValue(array(1)));

        $this->assertTrue($this->evaluator->hasPermission($this->token, null, 'FOO'));
    }

    public function testHasPermissionWhenNoObjectIdentityIsAvailable()
    {
        $this->permissionMap->expects($this->once())
            ->method('getMasks')
            ->with('FOO', $this->object)
            ->will($this->returnValue(array(1)));

        $this->oidStrategy->expects($this->once())
            ->method('getObjectIdentity')
            ->with($this->object)
            ->will($this->returnValue(null));

        $this->assertTrue($this->evaluator->hasPermission($this->token, $this->object, 'FOO'));
    }

    public function testHasPermissionWhenAclIsNotFound()
    {
        $this->permissionMap->expects($this->once())
            ->method('getMasks')
            ->with('FOO', $this->oid)
            ->will($this->returnValue(array(1)));

        $this->sidStrategy->expects($this->once())
            ->method('getSecurityIdentities')
            ->with($this->token)
            ->will($this->returnValue(array($this->sid)));

        $this->provider->expects($this->once())
            ->method('findAcl')
            ->with($this->oid)
            ->will($this->throwException(new AclNotFoundException()));

        $this->assertFalse($this->evaluator->hasPermission($this->token, $this->oid, 'FOO'));
    }

    public function testHasPermissionWhenAceIsNotFound()
    {
        $this->permissionMap->expects($this->once())
            ->method('getMasks')
            ->with('FOO', $this->oid)
            ->will($this->returnValue(array(1)));

        $this->sidStrategy->expects($this->once())
            ->method('getSecurityIdentities')
            ->with($this->token)
            ->will($this->returnValue(array($this->sid)));

        $this->provider->expects($this->once())
            ->method('findAcl')
            ->with($this->oid)
            ->will($this->returnValue($this->acl));

        $this->acl->expects($this->once())
            ->method('isGranted')
            ->with(array(1), array($this->sid))
            ->will($this->throwException(new NoAceFoundException()));

        $this->assertFalse($this->evaluator->hasPermission($this->token, $this->oid, 'FOO'));
    }

    protected function setUp()
    {
        $this->token = $this->getMock('Symfony\Component\Security\Core\Authentication\Token\TokenInterface');
        $this->oid = $this->getMock('Symfony\Component\Security\Acl\Model\ObjectIdentityInterface');
        $this->sid = $this->getMock('Symfony\Component\Security\Acl\Model\SecurityIdentityInterface');
        $this->acl = $this->getMock('Symfony\Component\Security\Acl\Model\AclInterface');
        $this->object = new \stdClass;
        $this->provider = $this->getMock('Symfony\Component\Security\Acl\Model\AclProviderInterface');
        $this->oidStrategy = $this->getMock('Symfony\Component\Security\Acl\Model\ObjectIdentityRetrievalStrategyInterface');
        $this->sidStrategy = $this->getMock('Symfony\Component\Security\Acl\Model\SecurityIdentityRetrievalStrategyInterface');
        $this->permissionMap = $this->getMock('Symfony\Component\Security\Acl\Permission\PermissionMapInterface');
        $this->evaluator = new PermissionEvaluator($this->provider, $this->oidStrategy, $this->sidStrategy, $this->permissionMap);
    }
}
