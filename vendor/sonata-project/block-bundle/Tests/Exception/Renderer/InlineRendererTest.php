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

use Sonata\BlockBundle\Exception\Renderer\InlineRenderer;

/**
 * Test the inline exception renderer.
 *
 * @author Olivier Paradis <paradis.olivier@gmail.com>
 */
class InlineRendererTest extends \PHPUnit_Framework_TestCase
{
    /**
     * test the render() method.
     */
    public function testRender()
    {
        // GIVEN
        $template = 'test-template';

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
                $this->equalTo(array(
                    'exception' => $exception,
                    'block'     => $block, ))
            )
            ->will($this->returnValue('html'));

        // create renderer to test
        $renderer = new InlineRenderer($templating, $template);

        // WHEN
        $response = $renderer->render($exception, $block);

        // THEN
        $this->assertInstanceOf('Symfony\Component\HttpFoundation\Response', $response, 'Should return a Response');
        $this->assertEquals('html', $response->getContent(), 'Should contain the templating html result');
    }
}
