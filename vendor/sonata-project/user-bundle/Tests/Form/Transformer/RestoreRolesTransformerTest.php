<?php

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\UserBundle\Tests\Form\Transformer;

use Sonata\UserBundle\Form\Transformer\RestoreRolesTransformer;

class RestoreRolesTransformerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @expectedException RuntimeException
     */
    public function testInvalidStateTransform()
    {
        $roleBuilder = $this->getMockBuilder('Sonata\UserBundle\Security\EditableRolesBuilder')
            ->disableOriginalConstructor()
            ->getMock();

        $transformer = new RestoreRolesTransformer($roleBuilder);
        $transformer->transform(array());
    }

    /**
     * @expectedException RuntimeException
     */
    public function testInvalidStateReverseTransform()
    {
        $roleBuilder = $this->getMockBuilder('Sonata\UserBundle\Security\EditableRolesBuilder')
            ->disableOriginalConstructor()
            ->getMock();

        $transformer = new RestoreRolesTransformer($roleBuilder);
        $transformer->reverseTransform(array());
    }

    public function testValidTransform()
    {
        $roleBuilder = $this->getMockBuilder('Sonata\UserBundle\Security\EditableRolesBuilder')
            ->disableOriginalConstructor()
            ->getMock();

        $transformer = new RestoreRolesTransformer($roleBuilder);
        $transformer->setOriginalRoles(array());

        $data = array('ROLE_FOO');

        $this->assertEquals($data, $transformer->transform($data));
    }

    public function testValidReverseTransform()
    {
        $roleBuilder = $this->getMockBuilder('Sonata\UserBundle\Security\EditableRolesBuilder')
            ->disableOriginalConstructor()
            ->getMock();

        $roleBuilder->expects($this->once())->method('getRoles')->will($this->returnValue(array(array(), array())));

        $transformer = new RestoreRolesTransformer($roleBuilder);
        $transformer->setOriginalRoles(array('ROLE_HIDDEN'));

        $data = array('ROLE_FOO');

        $this->assertEquals(array('ROLE_FOO', 'ROLE_HIDDEN'), $transformer->reverseTransform($data));
    }

    public function testTransformAllowEmptyOriginalRoles()
    {
        $roleBuilder = $this->getMockBuilder('Sonata\UserBundle\Security\EditableRolesBuilder')
            ->disableOriginalConstructor()
            ->getMock();

        $transformer = new RestoreRolesTransformer($roleBuilder);
        $transformer->setOriginalRoles(null);

        $data = array('ROLE_FOO');

        $this->assertEquals($data, $transformer->transform($data));
    }

    public function testReverseTransformAllowEmptyOriginalRoles()
    {
        $roleBuilder = $this->getMockBuilder('Sonata\UserBundle\Security\EditableRolesBuilder')
            ->disableOriginalConstructor()
            ->getMock();

        $roleBuilder->expects($this->once())->method('getRoles')->will($this->returnValue(array(array(), array())));

        $transformer = new RestoreRolesTransformer($roleBuilder);
        $transformer->setOriginalRoles(null);

        $data = array('ROLE_FOO');

        $this->assertEquals(array('ROLE_FOO'), $transformer->reverseTransform($data));
    }

    public function testReverseTransformRevokedHierarchicalRole()
    {
        $roleBuilder = $this->getMockBuilder('Sonata\UserBundle\Security\EditableRolesBuilder')
                            ->disableOriginalConstructor()
                            ->getMock();

        $availableRoles = array(
            'ROLE_SONATA_ADMIN' => 'ROLE_SONATA_ADMIN',
            'ROLE_COMPANY_PERSONAL_MODERATOR' => 'ROLE_COMPANY_PERSONAL_MODERATOR: ROLE_COMPANY_USER',
            'ROLE_COMPANY_NEWS_MODERATOR' => 'ROLE_COMPANY_NEWS_MODERATOR: ROLE_COMPANY_USER',
            'ROLE_COMPANY_BOOKKEEPER' => 'ROLE_COMPANY_BOOKKEEPER: ROLE_COMPANY_USER',
            'ROLE_USER' => 'ROLE_USER',
        );
        $roleBuilder->expects($this->once())->method('getRoles')->will($this->returnValue(array($availableRoles, array())));

        // user roles
        $userRoles = array('ROLE_COMPANY_PERSONAL_MODERATOR', 'ROLE_COMPANY_NEWS_MODERATOR', 'ROLE_COMPANY_BOOKKEEPER');
        $transformer = new RestoreRolesTransformer($roleBuilder);
        $transformer->setOriginalRoles($userRoles);

        // now we want to revoke role ROLE_COMPANY_PERSONAL_MODERATOR
        $revokedRole = array_shift($userRoles);
        $processedRoles = $transformer->reverseTransform($userRoles);

        $this->assertNotContains($revokedRole, $processedRoles);
    }

    public function testReverseTransformHiddenRole()
    {
        $roleBuilder = $this->getMockBuilder('Sonata\UserBundle\Security\EditableRolesBuilder')
                            ->disableOriginalConstructor()
                            ->getMock();

        $availableRoles = array(
            'ROLE_SONATA_ADMIN' => 'ROLE_SONATA_ADMIN',
            'ROLE_ADMIN' => 'ROLE_ADMIN: ROLE_USER ROLE_COMPANY_ADMIN',
        );
        $roleBuilder->expects($this->once())->method('getRoles')->will($this->returnValue(array($availableRoles, array())));

        // user roles
        $userRoles = array('ROLE_USER', 'ROLE_SUPER_ADMIN');
        $transformer = new RestoreRolesTransformer($roleBuilder);
        $transformer->setOriginalRoles($userRoles);

        // add a new role
        array_push($userRoles, 'ROLE_SONATA_ADMIN');
        // remove existing user role that is not availableRoles
        unset($userRoles[array_search('ROLE_SUPER_ADMIN', $userRoles)]);
        $processedRoles = $transformer->reverseTransform($userRoles);

        $this->assertContains('ROLE_SUPER_ADMIN', $processedRoles);
    }
}
