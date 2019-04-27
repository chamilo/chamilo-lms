<?php

use Test\Tool\BaseTestCase;
use Knp\Component\Pager\Paginator;
use Knp\Component\Pager\Pagination\SlidingPagination;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Knp\Component\Pager\Event\Subscriber\Paginate\PaginationSubscriber;

class TraversableItemsTest extends BaseTestCase
{
    /**
     * @test
     */
    function shouldBeAbleToUseTraversableItems()
    {
        $p = new Paginator;

        $items = new \ArrayObject(range(1, 23));
        $view = $p->paginate($items, 3, 10);

        $view->renderer = function($data) {
            return 'custom';
        };
        $this->assertEquals('custom', (string)$view);

        $items = $view->getItems();
        $this->assertTrue($items instanceof \ArrayObject);
        $i = 21;
        foreach ($view as $item) {
            $this->assertEquals($i++, $item);
        }
    }
}
