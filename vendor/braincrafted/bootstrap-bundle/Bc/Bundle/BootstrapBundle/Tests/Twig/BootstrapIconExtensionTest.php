<?php
/**
 * This file is part of BcBootstrapBundle.
 *
 * (c) 2012-2013 by Florian Eckerstorfer
 */

namespace Bc\Bundle\BootstrapBundle\Tests\Twig;

use Bc\Bundle\BootstrapBundle\Twig\BootstrapIconExtension;

/**
 * BootstrapIconExtensionTest
 *
 * @category   Test
 * @package    BraincraftedBootstrapBundle
 * @subpackage Twig
 * @author     Florian Eckerstorfer <florian@eckerstorfer.co>
 * @copyright  2012-2013 Florian Eckerstorfer
 * @license    http://opensource.org/licenses/MIT The MIT License
 * @link       http://bootstrap.braincrafted.com Bootstrap for Symfony2
 */
class BootstrapIconExtensionTest extends \PHPUnit_Framework_TestCase
{
    /** @var \Braincrafted\BootstrapBundle\Twig\BootstrapIconExtension */
    private $extension;

    /**
     * Set up
     */
    public function setUp()
    {
        $this->extension = new BootstrapIconExtension();
    }

    /**
     * @covers Braincrafted\BootstrapBundle\Twig\BootstrapIconExtension::iconFilter
     */
    public function testIconFilter()
    {
        $this->assertEquals(
            '<i class="icon-heart"></i>',
            $this->extension->iconFilter('heart'),
            '->iconFilter() returns the HTML code for the given icon.'
        );
        $this->assertEquals(
            '<i class="icon-white icon-heart"></i>',
            $this->extension->iconFilter('heart', 'white'),
            '->iconFilter() returns the HTML code for the given icon in white.'
        );
    }

    /**
     * @covers Braincrafted\BootstrapBundle\Twig\BootstrapIconExtension::parseIconsFilter
     */
    public function testParseIconsFilter()
    {
        $this->assertEquals(
            '<i class="icon-heart"></i> foobar',
            $this->extension->parseIconsFilter('.icon-heart foobar'),
            '->parseIconsFilter() returns the HTML code with the replaced icons.'
        );
        $this->assertEquals(
            '<i class="icon-white icon-heart"></i> foobar',
            $this->extension->parseIconsFilter('.icon-heart foobar', 'white'),
            '->parseIconsFilter() returns the HTML code with the replaced icons in white.'
        );
    }

    /**
     * @covers Braincrafted\BootstrapBundle\Twig\BootstrapIconExtension::getFilters
     */
    public function testGetFilters()
    {
        $filters = $this->extension->getFilters();
        $this->assertCount(2, $filters, '->getFilters() returns 2 filters.');
        $this->assertTrue(isset($filters['parse_icons']), '->getFilters() returns "parse_icons" filter.');
        $this->assertTrue(isset($filters['icon']), '->getFilters() returns "icon" filter.');
    }

    /**
     * @covers Braincrafted\BootstrapBundle\Twig\BootstrapIconExtension::getName
     */
    public function testGetName()
    {
        $this->assertEquals('bootstrap_icon_extension', $this->extension->getName(), '->getName() returns the name.');
    }
}
