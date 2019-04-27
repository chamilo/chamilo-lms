<?php

namespace Test\Pager\Subscriber\Paginate;

use Knp\Component\Pager\Paginator;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Knp\Component\Pager\Event\Subscriber\Paginate\ArraySubscriber;
use Knp\Component\Pager\Event\Subscriber\Paginate\SolariumQuerySubscriber;

use Test\Mock\PaginationSubscriber as MockPaginationSubscriber;

class SolariumQuerySubscriberTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     * @expectedException RuntimeException
     * @expectedExceptionMessage One of listeners must count and slice given target
     */
    function testArrayShouldNotBeHandled()
    {
        $array = array(1 => 'foo', 2 => 'bar');

        $dispatcher = new EventDispatcher;
        $dispatcher->addSubscriber(new SolariumQuerySubscriber);
        $dispatcher->addSubscriber(new MockPaginationSubscriber);

        $p = new Paginator($dispatcher);
        $p->paginate($array, 1, 10);
    }
}
