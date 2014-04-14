<?php


namespace Braincrafted\Bundle\BootstrapBundle\Tests\Type;

use Braincrafted\Bundle\BootstrapBundle\Form\Type\FormActionsType;
use Mockery as m;
use Symfony\Component\Form\ButtonBuilder;
use Symfony\Component\Form\FormBuilder;

/**
 * Class FormActionsTypeTest
 *
 * @group unit
 */
class FormActionsTypeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var FormActionsType
     */
    protected $type;

    protected function setUp()
    {
        parent::setUp();
        $this->type = new FormActionsType();
    }

    public function testBuildForm()
    {
        $builder = m::mock('Symfony\Component\Form\FormBuilderInterface');

        $input  = array(
            'buttons' => array(
                'save' => array('type' => 'submit', 'options' => array('label' => 'button.save')),
                'cancel' => array('type' => 'button', 'options' => array('label' => 'button.cancel')),
            )
        );

        $buttonBuilder = new ButtonBuilder('name');
        $builder->shouldReceive('add')
            ->with(m::anyOf('save', 'cancel'), m::anyOf('submit', 'button'), m::hasKey('label'))
            ->twice()
            ->andReturn($buttonBuilder);

        $this->type = new FormActionsType();
        $this->type->buildForm($builder, $input);
    }

    public function testBuildView()
    {
        $view    = m::mock('Symfony\Component\Form\FormView');
        $form    = m::mock('Symfony\Component\Form\FormInterface');
        $button  = m::mock('Symfony\Component\Form\Button');
        $options = array();

        $buttons = array(
            $button,
            $button
        );

        $form->shouldReceive('count')->andReturn(2)->once();
        $form->shouldReceive('all')->andReturn($buttons)->once();

        $this->type = new FormActionsType();
        $this->type->buildView($view, $form, $options);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testBuildViewWithBadField()
    {
        $view    = m::mock('Symfony\Component\Form\FormView');
        $form    = m::mock('Symfony\Component\Form\FormInterface');
        $button  = m::mock('Symfony\Component\Form\Button');
        $input   = m::mock('Symfony\Component\Form\FormInterface');
        $options = array();

        $buttons = array(
            $button,
            $button,
            $input
        );

        $form->shouldReceive('count')->andReturn(2)->once();
        $form->shouldReceive('all')->andReturn($buttons)->once();

        $this->type = new FormActionsType();
        $this->type->buildView($view, $form, $options);
    }

    public function testSetDefaultOptions()
    {

        $defaults = array(
            'buttons'        => array(),
            'options'        => array(),
            'mapped'         => false,
        );

        $resolver = m::mock('Symfony\Component\OptionsResolver\OptionsResolverInterface');
        $resolver->shouldReceive('setDefaults')->with($defaults)->once();

        $this->type->setDefaultOptions($resolver);
    }

    public function testGetName()
    {
        $this->assertEquals('form_actions', $this->type->getName());
    }
}
