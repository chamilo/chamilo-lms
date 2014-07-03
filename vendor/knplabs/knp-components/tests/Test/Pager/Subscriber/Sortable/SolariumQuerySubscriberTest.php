<?php

namespace Test\Pager\Subscriber\Sortable;

use Knp\Component\Pager\Paginator;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Knp\Component\Pager\Event\Subscriber\Sortable\SolariumQuerySubscriber;

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
        $array = array(
            'results' => array(
                0 => array(
                    'city'   => 'Lyon',
                    'market' => 'E'
                ),
                1 => array(
                    'city'   => 'Paris',
                    'market' => 'G'
                ),
            ),
            'nbTotalResults' => 2
        );

        $dispatcher = new EventDispatcher;
        $dispatcher->addSubscriber(new SolariumQuerySubscriber);
        $dispatcher->addSubscriber(new MockPaginationSubscriber);

        $p = new Paginator($dispatcher);
        $p->paginate($array, 1, 10);
    }
}
