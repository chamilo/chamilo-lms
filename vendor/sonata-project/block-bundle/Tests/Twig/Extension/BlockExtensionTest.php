<?php

namespace Sonata\BlockBundle\Twig\Extension;

class BlockExtensionTest extends \PHPUnit_Framework_TestCase
{
    protected $blockHelper;
    protected $blockExtension;
    protected $env;

    public function setUp()
    {
        $this->blockHelper = $this->getMockBuilder(
            'Sonata\BlockBundle\Templating\Helper\BlockHelper'
        )->disableOriginalConstructor()->getMock();

        $this->blockExtension = new BlockExtension($this->blockHelper);
        $this->env = new \Twig_Environment();
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
