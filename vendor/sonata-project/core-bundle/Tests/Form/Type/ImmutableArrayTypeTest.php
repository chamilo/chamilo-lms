<?php

/*
 * This file is part of the Sonata package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\CoreBundle\Tests\Form\Type;

use Sonata\CoreBundle\Form\Type\ImmutableArrayType;

use Symfony\Component\Form\Tests\Extension\Core\Type\TypeTestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ImmutableArrayTypeTest extends TypeTestCase
{
    public function testGetDefaultOptions()
    {
        $type = new ImmutableArrayType();

        $this->assertEquals('sonata_type_immutable_array', $type->getName());
        $this->assertEquals('form', $type->getParent());

        $resolver = new OptionsResolver();
        $type->setDefaultOptions($resolver);

        $options = $resolver->resolve();

        $expected = array(
            'keys' => array()
        );

        $this->assertEquals($expected, $options);
    }

    public function testCallback()
    {
        $type = new ImmutableArrayType();

        $builder = $this->getMock('Symfony\Component\Form\Test\FormBuilderInterface');
        $builder->expects($this->once())->method('add')->with(
            $this->callback(function($name) {
                return $name == 'ttl';
            }),
            $this->callback(function($name) {
                return $name == 'text';
            }),
            $this->callback(function($name) {
                return $name == array(1 => 1);
            })
        );

        $self = $this;
        $optionsCallback = function ($builder, $name, $type, $extra) use ($self) {
            $self->assertEquals(array('foo', 'bar'), $extra);
            $self->assertEquals($name, 'ttl');
            $self->assertEquals($type, 'text');
            $self->assertInstanceOf('Symfony\Component\Form\Test\FormBuilderInterface', $builder);

            return array('1' => '1');
        };

        $options = array(
            'keys' => array(
                array('ttl', 'text', $optionsCallback, 'foo', 'bar')
            )
        );

        $type->buildForm($builder, $options);
    }
}
