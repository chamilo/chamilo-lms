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

use Sonata\BlockBundle\Exception\Filter\KeepNoneFilter;

/**
 * Test the keep all exception filter.
 *
 * @author Olivier Paradis <paradis.olivier@gmail.com>
 */
class KeepNoneFilterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * test the filter with an exception.
     *
     * @param \Exception $exception
     *
     * @dataProvider getExceptions
     */
    public function testFilter(\Exception $exception)
    {
        // GIVEN
        $block = $this->getMock('Sonata\BlockBundle\Model\BlockInterface');
        $filter = new KeepNoneFilter();

        // WHEN
        $result = $filter->handle($exception, $block);

        // THEN
        $this->assertFalse($result, 'Should handle no exceptions');
    }

    /**
     * Returns exceptions to test.
     *
     * @return array
     */
    public function getExceptions()
    {
        return array(
            array($this->getMock('\Exception')),
            array($this->getMock('\RuntimeException')),
        );
    }
}
