<?php

namespace Pagerfanta\Tests\Adapter;

use Pagerfanta\Adapter\CallbackAdapter;

class CallbackAdapterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @expectedException Pagerfanta\Exception\InvalidArgumentException
     * @dataProvider notCallbackProvider
     */
    public function testConstructorShouldThrowAnInvalidArgumentExceptionIfTheGetNbResultsCallbackIsNotACallback($value)
    {
        new CallbackAdapter($value, function () {});
    }

    /**
     * @expectedException Pagerfanta\Exception\InvalidArgumentException
     * @dataProvider notCallbackProvider
     */
    public function testConstructorShouldThrowAnInvalidArgumentExceptionIfTheGetSliceCallbackIsNotACallback($value)
    {
        new CallbackAdapter(function () {}, $value);
    }

    public function notCallbackProvider()
    {
        return array(
            array('foo'),
            array(1),
        );
    }

    public function testGetNbResultShouldReturnTheGetNbResultsCallbackReturnValue()
    {
        $getNbResultsCallback = function () {
            return 42;
        };
        $adapter = new CallbackAdapter($getNbResultsCallback, function () {});

        $this->assertEquals(42, $adapter->getNbResults());
    }

    public function testGetSliceShouldReturnTheGetSliceCallbackReturnValue()
    {
        $results = new \ArrayObject();
        $getSliceCallback = function () use ($results) {
            return $results;
        };

        $adapter = new CallbackAdapter(function () {}, $getSliceCallback);

        $this->assertSame($results, $adapter->getSlice(1, 1));
    }

    public function testGetSliceShouldPassTheOffsetAndLengthToTheGetSliceCallback()
    {
        $testCase = $this;
        $getSliceCallback = function ($offset, $length) use ($testCase) {
            $testCase->assertSame(10, $offset);
            $testCase->assertSame(18, $length);
        };

        $adapter = new CallbackAdapter(function () {}, $getSliceCallback);
        $adapter->getSlice(10, 18);
    }
}
