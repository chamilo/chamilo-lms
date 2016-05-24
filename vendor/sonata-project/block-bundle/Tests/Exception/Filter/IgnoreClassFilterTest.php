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

use Sonata\BlockBundle\Exception\Filter\IgnoreClassFilter;

/**
 * Test the ignore class exception filter.
 *
 * @author Olivier Paradis <paradis.olivier@gmail.com>
 */
class IgnoreClassFilterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * test the filter with a inherited exception.
     */
    public function testWithInheritedException()
    {
        // GIVEN
        $exception = $this->getMock('Symfony\Component\HttpKernel\Exception\NotFoundHttpException');
        $block     = $this->getMock('Sonata\BlockBundle\Model\BlockInterface');
        $filter = new IgnoreClassFilter('\RuntimeException');

        // WHEN
        $result = $filter->handle($exception, $block);

        // THEN
        $this->assertFalse($result, 'Should NOT handle it since NotFoundHttpException inherits RuntimeException');
    }

    /**
     * test the the filter with a non-inherited exception.
     */
    public function testWithNonInheritedException()
    {
        // GIVEN
        $exception = $this->getMock('\Exception');
        $block     = $this->getMock('Sonata\BlockBundle\Model\BlockInterface');
        $filter = new IgnoreClassFilter('\RuntimeException');

        // WHEN
        $result = $filter->handle($exception, $block);

        // THEN
        $this->assertTrue($result, 'Should handle it since an \Exception does not inherit RuntimeException');
    }
}
