<?php
/**
 * This file is part of BraincraftedBootstrapBundle.
 *
 * (c) 2012-2013 by Florian Eckerstorfer
 */

namespace Braincrafted\Bundle\BootstrapBundle\Tests\Twig;

use Braincrafted\Bundle\BootstrapBundle\Twig\BootstrapLabelExtension;

/**
 * BootstrapLabelExtensionTest
 *
 * This test is only useful if you consider that it will be run by Travis on every supported PHP
 * configuration. We live in a world where should not have too manually test every commit with every
 * version of PHP. And I know exactly that I will commit short array syntax all the time and break
 * compatibility with PHP 5.3
 *
 * @category   Test
 * @package    BraincraftedBootstrapBundle
 * @subpackage Twig
 * @author     Florian Eckerstorfer <florian@eckerstorfer.co>
 * @copyright  2012-2013 Florian Eckerstorfer
 * @license    http://opensource.org/licenses/MIT The MIT License
 * @link       http://bootstrap.braincrafted.com Bootstrap for Symfony2
 * @group      unit
 */
class BootstrapLabelExtensionTest extends \PHPUnit_Framework_TestCase
{
    /** @var BootstrapLabelExtension */
    private $extension;

    /**
     * @return void
     */
    public function setUp()
    {
        $this->extension = new BootstrapLabelExtension();
    }

    /**
     * @covers Braincrafted\Bundle\BootstrapBundle\Twig\BootstrapLabelExtension::getFunctions()
     */
    public function testGetFunctions()
    {
        $this->assertCount(6, $this->extension->getFunctions());
    }

    /**
     * @covers Braincrafted\Bundle\BootstrapBundle\Twig\BootstrapLabelExtension::labelFunction
     */
    public function testLabelFunction()
    {
        $this->assertEquals(
            '<span class="label label-default">Hello World</span>',
            $this->extension->labelFunction('Hello World'),
            '->labelFunction() returns the HTML code for the given label.'
        );
        $this->assertEquals(
            '<span class="label label-success">Hello World</span>',
            $this->extension->labelFunction('Hello World', 'success'),
            '->labelFunction() returns the HTML code for the given success label.'
        );
    }

    /**
     * @covers Braincrafted\Bundle\BootstrapBundle\Twig\BootstrapLabelExtension::labelSuccessFunction
     */
    public function testLabelSuccessFunction()
    {
        $this->assertEquals(
            '<span class="label label-success">Foobar</span>',
            $this->extension->labelSuccessFunction('Foobar')
        );
    }

    /**
     * @covers Braincrafted\Bundle\BootstrapBundle\Twig\BootstrapLabelExtension::labelWarningFunction
     */
    public function testLabelWarningFunction()
    {
        $this->assertEquals(
            '<span class="label label-warning">Foobar</span>',
            $this->extension->labelWarningFunction('Foobar')
        );
    }

    /**
     * @covers Braincrafted\Bundle\BootstrapBundle\Twig\BootstrapLabelExtension::labelDangerFunction
     */
    public function testLabelDangerFunction()
    {
        $this->assertEquals(
            '<span class="label label-danger">Foobar</span>',
            $this->extension->labelDangerFunction('Foobar')
        );
    }

    /**
     * @covers Braincrafted\Bundle\BootstrapBundle\Twig\BootstrapLabelExtension::labelInfoFunction
     */
    public function testLabelInfoFunction()
    {
        $this->assertEquals(
            '<span class="label label-info">Foobar</span>',
            $this->extension->labelInfoFunction('Foobar')
        );
    }

    /**
     * @covers Braincrafted\Bundle\BootstrapBundle\Twig\BootstrapLabelExtension::labelPrimaryFunction
     */
    public function testLabelPrimaryFunction()
    {
        $this->assertEquals(
            '<span class="label label-primary">Foobar</span>',
            $this->extension->labelPrimaryFunction('Foobar')
        );
    }

    /**
     * @covers Braincrafted\Bundle\BootstrapBundle\Twig\BootstrapLabelExtension::getName()
     */
    public function testGetName()
    {
        $this->assertEquals('braincrafted_bootstrap_label', $this->extension->getName());
    }
}
