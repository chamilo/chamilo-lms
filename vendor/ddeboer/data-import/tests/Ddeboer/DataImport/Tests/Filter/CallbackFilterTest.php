<?php

namespace Ddeboer\DataImport\Filter;

use Ddeboer\DataImport\Filter\CallbackFilter;

/**
 * @author Markus Bachmann <markus.bachmann@bachi.biz>
 */
class CallbackFilterTest extends \PHPUnit_Framework_TestCase
{
    public function testFilter()
    {
        $callback = function (array $item) {
            return false;
        };

        $filter = new CallbackFilter($callback);

        $this->assertFalse($filter->filter(array('foobar')));
    }

    /**
     * @expectedException RuntimeException
     */
    public function testFilterWithNotCallableArgument()
    {
        $filter = new CallbackFilter('string');
    }
}
