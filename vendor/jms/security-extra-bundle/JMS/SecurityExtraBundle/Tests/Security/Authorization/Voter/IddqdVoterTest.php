<?php

namespace JMS\SecurityExtraBundle\Tests\Security\Authorization\Voter;

use JMS\SecurityExtraBundle\Security\Authorization\Voter\IddqdVoter;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class IddqdVoterTest extends \PHPUnit_Framework_TestCase
{
    public function testRoleIddqd()
    {
        $token = $this->getToken(array('ROLE_IDDQD'));
        $voter = new IddqdVoter(array(), array('ROLE_PREVIOUS_ADMIN'));
        $this->assertEquals($voter->vote($token, null, array('ROLE_FOO')), VoterInterface::ACCESS_GRANTED);
    }

    public function testIgnoresRolePreviousAdmin()
    {
        $token = $this->getToken(array('ROLE_IDDQD'));
        $voter = new IddqdVoter(array(), array('ROLE_USER', 'ROLE_PREVIOUS_ADMIN'));
        $this->assertEquals($voter->vote($token, null, array('ROLE_PREVIOUS_ADMIN')), VoterInterface::ACCESS_ABSTAIN);
    }

    public function testNotIgnoresRolePreviousAdmin()
    {
        $token = $this->getToken(array('ROLE_IDDQD'));
        $voter = new IddqdVoter(array(), array());
        $this->assertEquals($voter->vote($token, null, array('ROLE_PREVIOUS_ADMIN')), VoterInterface::ACCESS_GRANTED);
    }

    public function testRoleIddqdWithAlias()
    {
        $token = $this->getToken(array('ROLE_SUPER_ADMIN'));
        $voter = new IddqdVoter(array('ROLE_SUPER_ADMIN'), array());
        $this->assertEquals($voter->vote($token, null, array('ROLE_USER')), VoterInterface::ACCESS_GRANTED);
    }

    protected function getToken(array $roles)
    {
        $tokenRoles = array();
        foreach ($roles as $value) {
            $role = $this->getMock('Symfony\Component\Security\Core\Role\RoleInterface');
            $role
                ->expects($this->once())
                ->method('getRole')
                ->will($this->returnValue($value))
            ;
            $tokenRoles[] = $role;
        }

        $token = $this->getMock('Symfony\Component\Security\Core\Authentication\Token\TokenInterface');
        $token
            ->expects($this->once())
            ->method('getRoles')
            ->will($this->returnValue($tokenRoles))
        ;

        return $token;
    }
}