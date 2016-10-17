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
use Sonata\CoreBundle\Form\Type\BooleanType;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class BooleanTypeTest extends TypeTestCase
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

        $type = new BooleanType();

        $type->buildForm($formBuilder, array(
            'transform' => false,

            // @deprecated Deprecated as of SonataCoreBundle 2.3.10, to be removed in 4.0.
            'catalogue' => 'SonataCoreBundle',

            // Use directly translation_domain in SonataCoreBundle 4.0
            'translation_domain' => function (Options $options) {
                if ($options['catalogue']) {
                    return $options['catalogue'];
                }

                return $options['translation_domain'];
            },
        ));
    }

    public function testGetParent()
    {
        $form = new BooleanType();

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
        $type = new BooleanType();

        $this->assertSame(
            method_exists('Symfony\Component\Form\AbstractType', 'getBlockPrefix') ?
                'Symfony\Component\Form\Extension\Core\Type\ChoiceType' :
                'choice',
            $type->getParent()
        );

        FormHelper::configureOptions($type, $optionResolver = new OptionsResolver());

        $options = $optionResolver->resolve();

        $this->assertSame(2, count($options['choices']));
    }

    public function testAddTransformerCall()
    {
        $type = new BooleanType();

        FormHelper::configureOptions($type, $optionResolver = new OptionsResolver());

        $builder = $this->getMock('Symfony\Component\Form\Test\FormBuilderInterface');
        $builder->expects($this->once())->method('addModelTransformer');

        $type->buildForm($builder, $optionResolver->resolve(array(
            'transform' => true,
        )));
    }

    /**
     * The default behavior is not to transform to real boolean value .... don't ask.
     */
    public function testDefaultBehavior()
    {
        $type = new BooleanType();

        FormHelper::configureOptions($type, $optionResolver = new OptionsResolver());

        $builder = $this->getMock('Symfony\Component\Form\Test\FormBuilderInterface');
        $builder->expects($this->never())->method('addModelTransformer');

        $type->buildForm($builder, $optionResolver->resolve(array()));
    }

    public function testOptions()
    {
        $type = new BooleanType();

        FormHelper::configureOptions($type, $optionResolver = new OptionsResolver());

        $builder = $this->getMock('Symfony\Component\Form\Test\FormBuilderInterface');
        $builder->expects($this->never())->method('addModelTransformer');

        $resolvedOptions = $optionResolver->resolve(array(
            'translation_domain' => 'fooTrans',
            'choices' => array(1 => 'foo_yes', 2 => 'foo_no'),
        ));

        $type->buildForm($builder, $resolvedOptions);

        $expectedOptions = array(
            'transform' => false,
            'catalogue' => 'SonataCoreBundle',
            'choices_as_values' => true,
            'translation_domain' => 'fooTrans',
            'choices' => array(1 => 'foo_yes', 2 => 'foo_no'),
        );

        if (!method_exists('Symfony\Component\Form\AbstractType', 'configureOptions')
            || !method_exists('Symfony\Component\Form\FormTypeInterface', 'setDefaultOptions')) {
            unset($expectedOptions['choices_as_values']);
        }

        $this->assertSame($expectedOptions, $resolvedOptions);
    }

    public function testLegacyDeprecatedCatalogueOption()
    {
        $type = new BooleanType();

        FormHelper::configureOptions($type, $optionResolver = new OptionsResolver());

        $builder = $this->getMock('Symfony\Component\Form\Test\FormBuilderInterface');
        $builder->expects($this->never())->method('addModelTransformer');

        $resolvedOptions = $optionResolver->resolve(array(
            'catalogue' => 'fooTrans',
            'choices' => array(1 => 'foo_yes', 2 => 'foo_no'),
        ));

        $type->buildForm($builder, $resolvedOptions);

        $expectedOptions = array(
            'transform' => false,
            'choices_as_values' => true,
            'catalogue' => 'fooTrans',
            'translation_domain' => 'fooTrans',
            'choices' => array(1 => 'foo_yes', 2 => 'foo_no'),
        );

        if (!method_exists('Symfony\Component\Form\AbstractType', 'configureOptions')
            || !method_exists('Symfony\Component\Form\FormTypeInterface', 'setDefaultOptions')) {
            unset($expectedOptions['choices_as_values']);
        }

        // "sort" trick can be remove when SF 2.3 support will be drop
        // Reason: array order as not the same between SF versions. This is the easiest way to fix it.
        $this->assertSame(sort($expectedOptions), sort($resolvedOptions));
    }
}
