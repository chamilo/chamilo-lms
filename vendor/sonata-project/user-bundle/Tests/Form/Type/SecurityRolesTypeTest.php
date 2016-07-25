<?php

/*
 * This file is part of the Sonata package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\UserBundle\Tests\Form\Type;

use Sonata\UserBundle\Form\Type\SecurityRolesType;
use Symfony\Component\Form\Tests\Extension\Core\Type\TypeTestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\PreloadedExtension;

/**
 * Class SecurityRolesTypeTest
 *
 * @package Sonata\UserBundle\Tests\Form\Type
 *
 * @author Quentin Fahrner <quentfahrner@gmail.com>
 */
class SecurityRolesTypeTest extends TypeTestCase
{
    protected $roleBuilder;

    protected function getExtensions()
    {
        $this->roleBuilder = $roleBuilder = $this->getMockBuilder('Sonata\UserBundle\Security\EditableRolesBuilder')
          ->disableOriginalConstructor()
          ->getMock();

        $this->roleBuilder->expects($this->any())->method('getRoles')->will($this->returnValue(array(
          0 => array(
            'ROLE_FOO'   => 'ROLE_FOO',
            'ROLE_USER'  => 'ROLE_USER',
            'ROLE_ADMIN' => 'ROLE_ADMIN: ROLE_USER'
          ),
          1 => array()
        )));

        $childType = new SecurityRolesType($this->roleBuilder);
        return array(new PreloadedExtension(array(
          $childType->getName() => $childType,
        ), array()));
    }

    public function testSubmitWithHiddenRoleData()
    {
        $originalRoles = array('ROLE_SUPER_ADMIN', 'ROLE_USER');

        $form = $this->factory->create('sonata_security_roles', $originalRoles, array(
            'multiple' => true,
            'expanded' => true,
            'required' => false
        ));

        // we keep hidden ROLE_SUPER_ADMIN and delete available ROLE_USER
        $form->bind(array(0 => 'ROLE_ADMIN'));

        $this->assertTrue($form->isSynchronized());
        $this->assertCount(2, $form->getData());
        $this->assertContains('ROLE_SUPER_ADMIN', $form->getData());
    }
}
