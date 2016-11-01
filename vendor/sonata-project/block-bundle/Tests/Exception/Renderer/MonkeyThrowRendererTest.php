<?php

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\BlockBundle\Tests\Exception\Renderer;

use Sonata\BlockBundle\Exception\Renderer\MonkeyThrowRenderer;

/**
 * Test the monkey throw exception renderer.
 *
 * @author Olivier Paradis <paradis.olivier@gmail.com>
 */
class MonkeyThrowRendererTest extends \PHPUnit_Framework_TestCase
{
    /**
     * test the render() method with a standard Exception.
     *
     * @expectedException \Exception
     */
    public function testRenderWithStandardException()
    {
        // GIVEN
        $exception = new \Exception();
        $block = $this->getMock('Sonata\BlockBundle\Model\BlockInterface');
        $renderer = new MonkeyThrowRenderer();

        // WHEN
        $renderer->render($exception, $block);

        // THEN
        // exception expected
    }

    /**
     * test the render() method with another exception to ensure it correctly throws the provided exception.
     *
     * @expectedException \RuntimeException
     */
    public function testRenderWithRuntimeException()
    {
        // GIVEN
        $exception = new \RuntimeException();
        $block = $this->getMock('Sonata\BlockBundle\Model\BlockInterface');
        $renderer = new MonkeyThrowRenderer();

        // WHEN
        $renderer->render($exception, $block);

        // THEN
        // exception expected
    }
}
