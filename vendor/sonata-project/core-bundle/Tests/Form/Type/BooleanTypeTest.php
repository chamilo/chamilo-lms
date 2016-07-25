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
use Symfony\Component\OptionsResolver\OptionsResolver;

class BooleanTypeTest extends TypeTestCase
{
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
            'choices'            => array(1 => 'foo_yes', 2 => 'foo_no'),
        ));

        $type->buildForm($builder, $resolvedOptions);

        $expectedOptions = array(
            'transform'          => false,
            'catalogue'          => 'SonataCoreBundle',
            'choices_as_value'   => true,
            'translation_domain' => 'fooTrans',
            'choices'            => array(1 => 'foo_yes', 2 => 'foo_no'),
        );

        if (!method_exists('Symfony\Component\Form\AbstractType', 'configureOptions')
            || !method_exists('Symfony\Component\Form\FormTypeInterface', 'setDefaultOptions')) {
            unset($expectedOptions['choices_as_value']);
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
            'choices'   => array(1 => 'foo_yes', 2 => 'foo_no'),
        ));

        $type->buildForm($builder, $resolvedOptions);

        $expectedOptions = array(
            'transform'          => false,
            'choices_as_value'   => true,
            'catalogue'          => 'fooTrans',
            'translation_domain' => 'fooTrans',
            'choices'            => array(1 => 'foo_yes', 2 => 'foo_no'),
        );

        if (!method_exists('Symfony\Component\Form\AbstractType', 'configureOptions')
            || !method_exists('Symfony\Component\Form\FormTypeInterface', 'setDefaultOptions')) {
            unset($expectedOptions['choices_as_value']);
        }

        // "sort" trick can be remove when SF 2.3 support will be drop
        // Reason: array order as not the same between SF versions. This is the easiest way to fix it.
        $this->assertSame(sort($expectedOptions), sort($resolvedOptions));
    }
}
