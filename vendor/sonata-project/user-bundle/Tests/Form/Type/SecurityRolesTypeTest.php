<?php

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\UserBundle\Tests\Form\Type;

use Sonata\UserBundle\Form\Type\SecurityRolesType;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class SecurityRolesTypeTest.
 *
 *
 * @author Quentin Fahrner <quentfahrner@gmail.com>
 */
class SecurityRolesTypeTest extends TypeTestCase
{
    protected $roleBuilder;

    public function testGetDefaultOptions()
    {
        $type = new SecurityRolesType($this->roleBuilder);

        $optionResolver = new OptionsResolver();
        $type->setDefaultOptions($optionResolver);

        $options = $optionResolver->resolve();
        $this->assertCount(3, $options['choices']);
    }

    public function testGetName()
    {
        $type = new SecurityRolesType($this->roleBuilder);
        $this->assertEquals('sonata_security_roles', $type->getName());
    }

    public function testGetParent()
    {
        $type = new SecurityRolesType($this->roleBuilder);
        $this->assertEquals(
            method_exists('Symfony\Component\Form\FormTypeInterface', 'setDefaultOptions') ?
                'choice' :
                'Symfony\Component\Form\Extension\Core\Type\ChoiceType',
            $type->getParent()
        );
    }

    public function testSubmitValidData()
    {
        $form = $this->factory->create($this->getSecurityRolesTypeName(), null, array(
            'multiple' => true,
            'expanded' => true,
            'required' => false,
        ));

        $form->submit(array(0 => 'ROLE_FOO'));

        $this->assertTrue($form->isSynchronized());
        $this->assertCount(1, $form->getData());
        $this->assertTrue(in_array('ROLE_FOO', $form->getData()));
    }

    public function testSubmitInvalidData()
    {
        $form = $this->factory->create($this->getSecurityRolesTypeName(), null, array(
            'multiple' => true,
            'expanded' => true,
            'required' => false,
        ));

        $form->submit(array(0 => 'ROLE_NOT_EXISTS'));

        $this->assertFalse($form->isSynchronized());
        $this->assertNull($form->getData());
    }

    public function testSubmitWithHiddenRoleData()
    {
        $originalRoles = array('ROLE_SUPER_ADMIN', 'ROLE_USER');

        $form = $this->factory->create($this->getSecurityRolesTypeName(), $originalRoles, array(
            'multiple' => true,
            'expanded' => true,
            'required' => false,
        ));

        // we keep hidden ROLE_SUPER_ADMIN and delete available ROLE_USER
        $form->submit(array(0 => 'ROLE_ADMIN'));

        $this->assertTrue($form->isSynchronized());
        $this->assertCount(2, $form->getData());
        $this->assertContains('ROLE_SUPER_ADMIN', $form->getData());
    }

    protected function getExtensions()
    {
        $this->roleBuilder = $roleBuilder = $this->getMockBuilder('Sonata\UserBundle\Security\EditableRolesBuilder')
          ->disableOriginalConstructor()
          ->getMock();

        $this->roleBuilder->expects($this->any())->method('getRoles')->will($this->returnValue(array(
          0 => array(
            'ROLE_FOO' => 'ROLE_FOO',
            'ROLE_USER' => 'ROLE_USER',
            'ROLE_ADMIN' => 'ROLE_ADMIN: ROLE_USER',
          ),
          1 => array(),
        )));

        $childType = new SecurityRolesType($this->roleBuilder);

        return array(new PreloadedExtension(array(
          $childType->getName() => $childType,
        ), array()));
    }

    private function getSecurityRolesTypeName()
    {
        return
            method_exists('Symfony\Component\Form\FormTypeInterface', 'setDefaultOptions') ?
                'sonata_security_roles' :
                'Sonata\UserBundle\Form\Type\SecurityRolesType';
    }
}
