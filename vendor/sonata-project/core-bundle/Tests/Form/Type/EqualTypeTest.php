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
use Sonata\CoreBundle\Form\Type\EqualType;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EqualTypeTest extends TypeTestCase
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

        $type = new EqualType($this->getMock('Symfony\Component\Translation\TranslatorInterface'));
        $type->buildForm($formBuilder, array(
            'choices' => array(),
        ));
    }

    public function testGetParent()
    {
        $form = new EqualType($this->getMock('Symfony\Component\Translation\TranslatorInterface'));

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
        $mock = $this->getMock('Symfony\Component\Translation\TranslatorInterface');

        $mock->expects($this->exactly(0))
            ->method('trans')
            ->will($this->returnCallback(function ($arg) {
                return $arg;
            })
            );

        $type = new EqualType($mock);

        $this->assertSame('sonata_type_equal', $type->getName());
        $this->assertSame(
            method_exists('Symfony\Component\Form\AbstractType', 'getBlockPrefix') ?
                'Symfony\Component\Form\Extension\Core\Type\ChoiceType' :
                'choice',
            $type->getParent()
        );

        FormHelper::configureOptions($type, $resolver = new OptionsResolver());

        $options = $resolver->resolve();

        $choices = array(1 => 'label_type_equals', 2 => 'label_type_not_equals');

        if (method_exists('Symfony\Component\Form\AbstractType', 'configureOptions')) {
            $choices = array_flip($choices);
        }

        $expected = array(
            'choice_translation_domain' => 'SonataCoreBundle',
            'choices_as_values' => true,
            'choices' => $choices,
        );

        if (!method_exists('Symfony\Component\Form\AbstractType', 'configureOptions')
            || !method_exists('Symfony\Component\Form\FormTypeInterface', 'setDefaultOptions')) {
            unset($expected['choices_as_values']);
        }

        $this->assertSame($expected, $options);
    }
}
