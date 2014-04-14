<?php

namespace Braincrafted\Bundle\BootstrapBundle\Tests\Type;

use \Mockery as m;

use Braincrafted\Bundle\BootstrapBundle\Form\Type\BootstrapCollectionType;

/**
 * BootstrapCollectionTypeTest
 *
 * @group unit
 */
class BootstrapCollectionTypeTest extends \PHPUnit_Framework_TestCase
{
    /** @var BootstrapCollectionType */
    private $type;

    public function setUp()
    {
        $this->type = new BootstrapCollectionType;
    }

    /**
     * @covers Braincrafted\Bundle\BootstrapBundle\Form\Type\BootstrapCollectionType::buildView()
     */
    public function testBuildView()
    {
        $view = m::mock('Symfony\Component\Form\FormView');

        $prototype = m::mock('Symfony\Component\Form\FormInterface');
        $prototype->shouldReceive('createView')->with($view);

        $config = m::mock('Symfony\Component\Form\FormConfigInterface');
        $config->shouldReceive('hasAttribute')->andReturn(true);
        $config->shouldReceive('getAttribute')->andReturn($prototype);

        $form = m::mock('Symfony\Component\Form\FormInterface');
        $form->shouldReceive('getConfig')->andReturn($config);

        $this->type->buildView($view, $form, array(
            'allow_add'             => true,
            'allow_delete'          => false,
            'add_button_text'       => 'Add',
            'delete_button_text'    => 'Delete',
            'sub_widget_col'        => 2,
            'button_col'            => 2,
            'prototype_name'        => '___name___'
        ));
    }

    /**
     * @covers Braincrafted\Bundle\BootstrapBundle\Form\Type\BootstrapCollectionType::setDefaultOptions()
     */
    public function testSetDefaultOptions()
    {
        $resolver = m::mock('Symfony\Component\OptionsResolver\OptionsResolverInterface');
        $resolver->shouldReceive('setDefaults');
        $resolver->shouldReceive('setNormalizers');

        $this->type->setDefaultOptions($resolver);
    }

    /**
     * @covers Braincrafted\Bundle\BootstrapBundle\Form\Type\BootstrapCollectionType::getParent()
     */
    public function testGetParent()
    {
        $this->assertEquals('collection', $this->type->getParent());
    }

    /**
     * @covers Braincrafted\Bundle\BootstrapBundle\Form\Type\BootstrapCollectionType::getName()
     */
    public function testGetName()
    {
        $this->assertEquals('bootstrap_collection', $this->type->getName());
    }
}
