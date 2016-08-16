<?php

namespace Ddeboer\DataImport\Filter;

use Ddeboer\DataImport\Filter\OffsetFilter;

/**
 * @author Ville Mattila <ville@eventio.fi>
 */
class OffsetFilterTest extends \PHPUnit_Framework_TestCase
{

    private function applyFilter(OffsetFilter $filter, array $items) {
        $result = array();
        foreach ($items as $item) {
            if (true === call_user_func($filter, array($item))) {
                $result[] = $item;
            }
        }

        return $result;
    }

    public function testDefaultFilter()
    {
        $items = array('first','second','third','fourth');
        $resultItems = $this->applyFilter(new OffsetFilter(), $items);
        $this->assertEquals($resultItems, $items);
    }

    public function testStartOffset()
    {
        $items = array('first','second','third','fourth');
        $resultItems = $this->applyFilter(new OffsetFilter(1), $items);
        $this->assertEquals($resultItems, array('second','third','fourth'));
    }

    public function testMaxCount()
    {
        $items = array('first','second','third','fourth');
        $resultItems = $this->applyFilter(new OffsetFilter(0, 2), $items);
        $this->assertEquals($resultItems, array('first','second'));
    }

    public function testOffsetWithMaxCount()
    {
        $items = array('first','second','third','fourth');
        $resultItems = $this->applyFilter(new OffsetFilter(1, 1), $items);
        $this->assertEquals($resultItems, array('second'));
    }
}
