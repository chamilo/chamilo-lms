<?php

/*
 * This file is part of the Sonata package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\BlockBundle\Tests\Exception\Renderer;

use Sonata\BlockBundle\Exception\Renderer\InlineDebugRenderer;

/**
 * Test the inline debug exception renderer.
 *
 * @author Olivier Paradis <paradis.olivier@gmail.com>
 */
class InlineDebugRendererTest extends \PHPUnit_Framework_TestCase
{
    /**
     * test the renderer without debug mode.
     */
    public function testRenderWithoutDebug()
    {
        // GIVEN
        $template   = 'test-template';
        $debug      = false;
        $exception  = $this->getMock('\Exception');
        $block      = $this->getMock('Sonata\BlockBundle\Model\BlockInterface');
        $templating = $this->getMock('Symfony\Bundle\FrameworkBundle\Templating\EngineInterface');

        $renderer = new InlineDebugRenderer($templating, $template, $debug);

        // WHEN
        $response = $renderer->render($exception, $block);

        // THEN
        $this->assertInstanceOf('Symfony\Component\HttpFoundation\Response', $response, 'Should return a Response');
        $this->assertEmpty($response->getContent(), 'Should have no content');
    }

    /**
     * test the render() method with debug enabled.
     */
    public function testRenderWithDebugEnabled()
    {
        // GIVEN
        $template = 'test-template';
        $debug    = true;

        // mock an exception to render
        $exception = $this->getMock('\Exception');

        // mock a block instance that provoked the exception
        $block = $this->getMock('Sonata\BlockBundle\Model\BlockInterface');

        // mock the templating render() to return an html result
        $templating = $this->getMock('Symfony\Bundle\FrameworkBundle\Templating\EngineInterface');
        $templating->expects($this->once())
            ->method('render')
            ->with(
                $this->equalTo($template),
                $this->logicalAnd(
                    $this->arrayHasKey('exception'),
                    $this->arrayHasKey('status_code'),
                    $this->arrayHasKey('status_text'),
                    $this->arrayHasKeyValue('logger', false),
                    $this->arrayHasKeyValue('currentContent', false),
                    $this->arrayHasKeyValue('block', $block),
                    $this->arrayHasKeyValue('forceStyle', true)
                )
            )
            ->will($this->returnValue('html'));

        // create renderer to test
        $renderer = new InlineDebugRenderer($templating, $template, $debug);

        // WHEN
        $response = $renderer->render($exception, $block);

        // THEN
        $this->assertInstanceOf('Symfony\Component\HttpFoundation\Response', $response, 'Should return a Response');
        $this->assertEquals('html', $response->getContent(), 'Should contain the templating html result');
    }

    /**
     * Returns a PHPUnit Constraint that ensures that an array has a key with given value.
     *
     * @param mixed $key   Key to be found in array
     * @param mixed $value Value to be found in array
     *
     * @return \PHPUnit_Framework_Constraint_Callback
     */
    public function arrayHasKeyValue($key, $value)
    {
        return new \PHPUnit_Framework_Constraint_Callback(function ($test) use ($key, $value) {
            return is_array($test) && array_key_exists($key, $test) && $test[$key] === $value;
        });
    }
}
