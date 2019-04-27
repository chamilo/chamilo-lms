<?php

use Test\Tool\BaseTestCase;
use Knp\Component\Pager\Paginator;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Test\Mock\PaginationSubscriber as MockPaginationSubscriber;
use Test\Mock\CustomParameterSubscriber;

class CustomParameterTest extends BaseTestCase
{
    /**
     * @test
     */
    function shouldGiveCustomParametersToPaginationView()
    {
        $dispatcher = new EventDispatcher;
        $dispatcher->addSubscriber(new CustomParameterSubscriber);
        $dispatcher->addSubscriber(new MockPaginationSubscriber); // pagination view
        $p = new Paginator($dispatcher);

        $items = array('first', 'second');
        $view = $p->paginate($items, 1, 10);

        $this->assertEquals('val', $view->getCustomParameter('test'));
        $this->assertNull($view->getCustomParameter('nonExisting'));
    }
}
