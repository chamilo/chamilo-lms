<?php

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\CoreBundle\Tests\Form\Type;

use Sonata\CoreBundle\Form\FormHelper;
use Sonata\CoreBundle\Form\Type\ImmutableArrayType;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ImmutableArrayTypeTest extends TypeTestCase
{
    public function testBuildForm()
    {
        // NEXT_MAJOR: Hack for php 5.3 only, remove it when requirement of PHP is >= 5.4
        $that = $this;

        $formBuilder = $this->getMockBuilder('Symfony\Component\Form\FormBuilder')->disableOriginalConstructor()->getMock();
        $formBuilder
            ->expects($this->any())
            ->method('add')
            ->will($this->returnCallback(function ($name, $type = null) use ($that) {
                // NEXT_MAJOR: Remove this "if" (when requirement of Symfony is >= 2.8)
                if (method_exists('Symfony\Component\Form\AbstractType', 'getBlockPrefix')) {
                    if (null !== $type) {
                        $isFQCN = class_exists($type);
                        if (!$isFQCN && method_exists('Symfony\Component\Form\AbstractType', 'getName')) {
                            // 2.8
                            @trigger_error(
                                sprintf(
                                    'Accessing type "%s" by its string name is deprecated since version 2.8 and will be removed in 3.0.'
                                    .' Use the fully-qualified type class name instead.',
                                    $type
                                ),
                                E_USER_DEPRECATED)
                            ;
                        }

                        $that->assertTrue($isFQCN, sprintf('Unable to ensure %s is a FQCN', $type));
                    }
                }
            }));

        $type = new ImmutableArrayType();
        $type->buildForm($formBuilder, array(
            'keys' => array(),
        ));
    }

    public function testGetParent()
    {
        $form = new ImmutableArrayType();

        // NEXT_MAJOR: Remove this "if" (when requirement of Symfony is >= 2.8)
        if (method_exists('Symfony\Component\Form\AbstractType', 'getBlockPrefix')) {
            $parentRef = $form->getParent();

            $isFQCN = class_exists($parentRef);
            if (!$isFQCN && method_exists('Symfony\Component\Form\AbstractType', 'getName')) {
                // 2.8
                @trigger_error(
                    sprintf(
                        'Accessing type "%s" by its string name is deprecated since version 2.8 and will be removed in 3.0.'
                        .' Use the fully-qualified type class name instead.',
                        $parentRef
                    ),
                    E_USER_DEPRECATED)
                ;
            }

            $this->assertTrue($isFQCN, sprintf('Unable to ensure %s is a FQCN', $parentRef));
        }
    }

    public function testGetDefaultOptions()
    {
        $type = new ImmutableArrayType();

        $this->assertSame('sonata_type_immutable_array', $type->getName());

        $this->assertSame(version_compare(Kernel::VERSION, '2.8', '<') ? 'form' : 'Symfony\Component\Form\Extension\Core\Type\FormType', $type->getParent());

        FormHelper::configureOptions($type, $resolver = new OptionsResolver());

        $options = $resolver->resolve();

        $expected = array(
            'keys' => array(),
        );

        $this->assertSame($expected, $options);
    }

    public function testCallback()
    {
        $type = new ImmutableArrayType();

        $builder = $this->getMock('Symfony\Component\Form\Test\FormBuilderInterface');
        $builder->expects($this->once())->method('add')->with(
            $this->callback(function ($name) {
                return $name === 'ttl';
            }),
            $this->callback(function ($name) {
                // NEXT_MAJOR: Remove ternary and keep 'Symfony\Component\Form\Extension\Core\Type\TextType'
                // (when requirement of Symfony is >= 2.8)
                return $name === method_exists('Symfony\Component\Form\AbstractType', 'getBlockPrefix')
                    ? 'Symfony\Component\Form\Extension\Core\Type\TextType'
                    : 'text';
            }),
            $this->callback(function ($name) {
                return $name === array(1 => '1');
            })
        );

        $self = $this;
        $optionsCallback = function ($builder, $name, $type, $extra) use ($self) {
            $self->assertEquals(array('foo', 'bar'), $extra);
            $self->assertEquals($name, 'ttl');
            $self->assertEquals(
                $type,
                // NEXT_MAJOR: Remove ternary and keep 'Symfony\Component\Form\Extension\Core\Type\TextType'
                // (when requirement of Symfony is >= 2.8)
                method_exists('Symfony\Component\Form\AbstractType', 'getBlockPrefix')
                    ? 'Symfony\Component\Form\Extension\Core\Type\TextType'
                    : 'text'
            );
            $self->assertInstanceOf('Symfony\Component\Form\Test\FormBuilderInterface', $builder);

            return array('1' => '1');
        };

        $options = array(
            'keys' => array(
                array(
                    'ttl',
                    // NEXT_MAJOR: Remove ternary and keep 'Symfony\Component\Form\Extension\Core\Type\TextType'
                    // (when requirement of Symfony is >= 2.8)
                    method_exists('Symfony\Component\Form\AbstractType', 'getBlockPrefix')
                        ? 'Symfony\Component\Form\Extension\Core\Type\TextType'
                        : 'text',
                    $optionsCallback,
                    'foo',
                    'bar',
                ),
            ),
        );

        $type->buildForm($builder, $options);
    }
}
