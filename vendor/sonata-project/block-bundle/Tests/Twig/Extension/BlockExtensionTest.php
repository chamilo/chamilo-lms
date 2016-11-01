<?php

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\BlockBundle\Twig\Extension;

use Sonata\BlockBundle\Templating\Helper\BlockHelper;

class BlockExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|BlockHelper
     */
    protected $blockHelper;

    /**
     * @var BlockExtension
     */
    protected $blockExtension;

    /**
     * @var \Twig_Environment
     */
    protected $env;

    public function setUp()
    {
        $this->blockHelper = $this->getMockBuilder(
            'Sonata\BlockBundle\Templating\Helper\BlockHelper'
        )->disableOriginalConstructor()->getMock();

        $loader = $this->getMock('Twig_LoaderInterface');

        $this->blockExtension = new BlockExtension($this->blockHelper);

        $this->env = new \Twig_Environment($loader);
        $this->env->addExtension($this->blockExtension);
    }

    public function provideFunction()
    {
        return array(
            array('sonata_block_render', array(
                'foobar', array('bar' => 'foo'),    // arguments
            ), 'render'),
            array('sonata_block_include_javascripts', array(
                'screen',                         // arguments
            ), 'includeJavascripts'),
            array('sonata_block_include_stylesheets', array(
                'foo',                            // arguments
            ), 'includeStylesheets'),
            array('sonata_block_render_event', array(
                'event.name', array(),            // arguments
            ), 'renderEvent'),
        );
    }

    /**
     * @dataProvider provideFunction
     */
    public function testFunction($name, $args, $expectedMethod)
    {
        $this->blockHelper->expects($this->once())
            ->method($expectedMethod);

        $func = $this->env->getFunction($name);
        $this->assertInstanceOf('Twig_SimpleFunction', $func);
        call_user_func_array($func->getCallable(), $args);
    }
}
