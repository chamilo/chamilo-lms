<?php

/*
 * This file is part of the Sonata package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\UserBundle\Tests\Security\Authorization\Voter;

use Sonata\UserBundle\Security\EditableRolesBuilder;

class EditableRolesBuilderTest extends \PHPUnit_Framework_TestCase
{
    public function testRolesFromHierarchy()
    {
        $token = $this->getMock('Symfony\Component\Security\Core\Authentication\Token\TokenInterface');

        $security = $this->getMock('Symfony\Component\Security\Core\SecurityContextInterface');
        $security->expects($this->any())->method('isGranted')->will($this->returnValue(true));
        $security->expects($this->any())->method('getToken')->will($this->returnValue($token));

        $pool = $this->getMockBuilder('Sonata\AdminBundle\Admin\Pool')
                ->disableOriginalConstructor()
                ->getMock();

        $pool->expects($this->once())->method('getAdminServiceIds')->will($this->returnValue(array()));

        $rolesHierarchy = array(
            'ROLE_ADMIN' => array(
                0 => 'ROLE_USER',
            ),
            'ROLE_SUPER_ADMIN' => array(
                0 => 'ROLE_USER',
                1 => 'ROLE_SONATA_ADMIN',
                2 => 'ROLE_ADMIN',
                3 => 'ROLE_ALLOWED_TO_SWITCH',
                4 => 'ROLE_SONATA_PAGE_ADMIN_PAGE_EDIT',
                5 => 'ROLE_SONATA_PAGE_ADMIN_BLOCK_EDIT',
            ),
            'SONATA' => array()
        );

        $expected = array (
            'ROLE_ADMIN' => 'ROLE_ADMIN: ROLE_USER',
            'ROLE_USER' => 'ROLE_USER',
            'ROLE_SUPER_ADMIN' => 'ROLE_SUPER_ADMIN: ROLE_USER, ROLE_SONATA_ADMIN, ROLE_ADMIN, ROLE_ALLOWED_TO_SWITCH, ROLE_SONATA_PAGE_ADMIN_PAGE_EDIT, ROLE_SONATA_PAGE_ADMIN_BLOCK_EDIT',
            'ROLE_SONATA_ADMIN' => 'ROLE_SONATA_ADMIN',
            'ROLE_ALLOWED_TO_SWITCH' => 'ROLE_ALLOWED_TO_SWITCH',
            'ROLE_SONATA_PAGE_ADMIN_PAGE_EDIT' => 'ROLE_SONATA_PAGE_ADMIN_PAGE_EDIT',
            'ROLE_SONATA_PAGE_ADMIN_BLOCK_EDIT' => 'ROLE_SONATA_PAGE_ADMIN_BLOCK_EDIT',
            'SONATA' => 'SONATA: ',
        );

        $builder = new EditableRolesBuilder($security, $pool, $rolesHierarchy);
        list($roles, $rolesReadOnly) = $builder->getRoles();

        $this->assertEmpty($rolesReadOnly);
        $this->assertEquals($expected, $roles);
    }

    public function testRolesFromAdminWithMasterAdmin()
    {
        $securityHandler = $this->getMock('Sonata\AdminBundle\Security\Handler\SecurityHandlerInterface');
        $securityHandler->expects($this->once())->method('getBaseRole')->will($this->returnValue('ROLE_FOO_%s'));

        $admin = $this->getMock('Sonata\AdminBundle\Admin\AdminInterface');
        $admin->expects($this->once())->method('isGranted')->will($this->returnValue(true));
        $admin->expects($this->once())->method('getSecurityInformation')->will($this->returnValue(array('GUEST' => array(0 => 'VIEW', 1 => 'LIST'), 'STAFF' => array(0 => 'EDIT', 1 => 'LIST', 2 => 'CREATE'), 'EDITOR' => array(0 => 'OPERATOR', 1 => 'EXPORT'), 'ADMIN' => array(0 => 'MASTER'))));
        $admin->expects($this->once())->method('getSecurityHandler')->will($this->returnValue($securityHandler));

        $token = $this->getMock('Symfony\Component\Security\Core\Authentication\Token\TokenInterface');

        $security = $this->getMock('Symfony\Component\Security\Core\SecurityContextInterface');
        $security->expects($this->any())->method('isGranted')->will($this->returnValue(true));
        $security->expects($this->any())->method('getToken')->will($this->returnValue($token));

        $pool = $this->getMockBuilder('Sonata\AdminBundle\Admin\Pool')
                ->disableOriginalConstructor()
                ->getMock();

        $pool->expects($this->once())->method('getInstance')->will($this->returnValue($admin));
        $pool->expects($this->once())->method('getAdminServiceIds')->will($this->returnValue(array('myadmin')));

        $builder = new EditableRolesBuilder($security, $pool, array());

        $expected = array (
          'ROLE_FOO_GUEST' => 'ROLE_FOO_GUEST',
          'ROLE_FOO_STAFF' => 'ROLE_FOO_STAFF',
          'ROLE_FOO_EDITOR' => 'ROLE_FOO_EDITOR',
          'ROLE_FOO_ADMIN' => 'ROLE_FOO_ADMIN',
        );

        list($roles, $rolesReadOnly) = $builder->getRoles();
        $this->assertEmpty($rolesReadOnly);
        $this->assertEquals($expected, $roles);
    }

    public function testWithNoSecurityToken()
    {
        $security = $this->getMock('Symfony\Component\Security\Core\SecurityContextInterface');
        $security->expects($this->any())->method('getToken')->will($this->returnValue(null));

        $pool = $this->getMockBuilder('Sonata\AdminBundle\Admin\Pool')
                ->disableOriginalConstructor()
                ->getMock();

        $builder = new EditableRolesBuilder($security, $pool, array());


        list($roles, $rolesReadOnly) = $builder->getRoles();

        $this->assertEmpty($roles);
        $this->assertEmpty($rolesReadOnly);

    }
}
