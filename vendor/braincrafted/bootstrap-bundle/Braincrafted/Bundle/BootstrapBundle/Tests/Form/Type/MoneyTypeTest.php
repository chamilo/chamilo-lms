<?php

namespace Braincrafted\Bundle\BootstrapBundle\Tests\Type;

use \Mockery as m;

use Braincrafted\Bundle\BootstrapBundle\Form\Type\MoneyType;

/**
 * MoneyTypeTest
 *
 * @group unit
 */
class MoneyTypeTest extends \PHPUnit_Framework_TestCase
{
    /** @var MoneyType */
    private $type;

    public function setUp()
    {
        $this->type = new MoneyType;
    }

    /**
     * @covers Braincrafted\Bundle\BootstrapBundle\Form\Type\MoneyType::buildView()
     * @covers Braincrafted\Bundle\BootstrapBundle\Form\Type\MoneyType::getPattern()
     * @covers Braincrafted\Bundle\BootstrapBundle\Form\Type\MoneyType::parsePatternMatches()
     */
    public function testBuildViewLeftSide()
    {
        $view = m::mock('Symfony\Component\Form\FormView');
        $form = m::mock('Symfony\Component\Form\FormInterface');

        $this->type->buildView($view, $form, array('currency' => 'EUR'));
    }

    /**
     * @covers Braincrafted\Bundle\BootstrapBundle\Form\Type\MoneyType::buildView()
     * @covers Braincrafted\Bundle\BootstrapBundle\Form\Type\MoneyType::getPattern()
     * @covers Braincrafted\Bundle\BootstrapBundle\Form\Type\MoneyType::parsePatternMatches()
     */
    public function testBuildViewRightSide()
    {
        $view = m::mock('Symfony\Component\Form\FormView');
        $form = m::mock('Symfony\Component\Form\FormInterface');

        $default = \Locale::getDefault();
        \Locale::setDefault('fr-CA');
        $this->type->buildView($view, $form, array('currency' => 'EUR'));
        \Locale::setDefault($default);
    }

    /**
     * @covers Braincrafted\Bundle\BootstrapBundle\Form\Type\MoneyType::buildView()
     * @covers Braincrafted\Bundle\BootstrapBundle\Form\Type\MoneyType::getPattern()
     */
    public function testGetPatternEmpty()
    {
        $view = m::mock('Symfony\Component\Form\FormView');
        $form = m::mock('Symfony\Component\Form\FormInterface');

        $this->type->buildView($view, $form, array('currency' => null));
    }

    /**
     * @covers Braincrafted\Bundle\BootstrapBundle\Form\Type\MoneyType::getName()
     */
    public function testGetName()
    {
        $this->assertEquals('money', $this->type->getName());
    }
}
