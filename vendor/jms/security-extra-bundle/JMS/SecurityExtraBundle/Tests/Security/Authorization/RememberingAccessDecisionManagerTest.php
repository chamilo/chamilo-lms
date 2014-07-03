<?php

namespace JMS\SecurityExtraBundle\Tests\Security\Authorization;

use JMS\SecurityExtraBundle\Security\Authorization\RememberingAccessDecisionManager;

class RememberingAccessDecisionManagerTest extends \PHPUnit_Framework_TestCase
{
    private $adm;
    private $delegate;

    public function testRemembersTheLastCall()
    {
        $token = $this->getMock('Symfony\Component\Security\Core\Authentication\Token\TokenInterface');

        $this->assertNull($this->adm->getLastDecisionCall());
        $this->delegate->expects($this->once())
            ->method('decide')
            ->with($token, array('FOO'), null)
            ->will($this->returnValue(true));

        $this->assertTrue($this->adm->decide($token, array('FOO')));
        $this->assertSame(array($token, array('FOO'), null), $this->adm->getLastDecisionCall());
    }

    public function testSupportsAttribute()
    {
        $this->delegate->expects($this->once())
            ->method('supportsAttribute')
            ->with('FOO')
            ->will($this->returnValue(false));

        $this->assertFalse($this->adm->supportsAttribute('FOO'));
    }

    public function testSupportsClass()
    {
        $this->delegate->expects($this->once())
            ->method('supportsClass')
            ->with('BAR')
            ->will($this->returnValue(true));

        $this->assertTrue($this->adm->supportsClass('BAR'));
    }

    protected function setUp()
    {
        $this->delegate = $this->getMock('Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface');
        $this->adm = new RememberingAccessDecisionManager($this->delegate);
    }
}