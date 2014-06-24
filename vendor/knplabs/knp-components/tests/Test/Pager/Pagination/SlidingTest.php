<?php

use Test\Tool\BaseTestCase;
use Knp\Component\Pager\Paginator;
use Knp\Component\Pager\Pagination\SlidingPagination;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Knp\Component\Pager\Event\Subscriber\Paginate\PaginationSubscriber;

class SlidingTest extends BaseTestCase
{
    /**
     * @test
     */
    function shouldBeAbleToProducePagination()
    {
        $p = new Paginator;

        $items = range(1, 23);
        $view = $p->paginate($items, 1, 10);

        $view->renderer = function($data) {
            return 'custom';
        };
        $this->assertEquals('custom', (string)$view);

        $pagination = $view->getPaginationData();
        $this->assertEquals(3, $pagination['last']);
        $this->assertEquals(1, $pagination['first']);
        $this->assertEquals(1, $pagination['current']);
        $this->assertEquals(10, $pagination['numItemsPerPage']);
        $this->assertEquals(3, $pagination['pageCount']);
        $this->assertEquals(23, $pagination['totalCount']);
        $this->assertEquals(2, $pagination['next']);
        $this->assertEquals(array(1, 2, 3), $pagination['pagesInRange']);
        $this->assertEquals(1, $pagination['firstPageInRange']);
        $this->assertEquals(3, $pagination['lastPageInRange']);
        $this->assertEquals(10, $pagination['currentItemCount']);
        $this->assertEquals(1, $pagination['firstItemNumber']);
        $this->assertEquals(10, $pagination['lastItemNumber']);
    }

    /**
     * @test
     */
    function shouldBeAbleToProduceWiderPagination()
    {
        $p = new Paginator;

        $items = range(1, 43);
        $view = $p->paginate($items, 4, 5);
        $pagination = $view->getPaginationData();

        $this->assertEquals(9, $pagination['last']);
        $this->assertEquals(1, $pagination['first']);
        $this->assertEquals(4, $pagination['current']);
        $this->assertEquals(5, $pagination['numItemsPerPage']);
        $this->assertEquals(9, $pagination['pageCount']);
        $this->assertEquals(43, $pagination['totalCount']);
        $this->assertEquals(5, $pagination['next']);
        $this->assertEquals(3, $pagination['previous']);
        $this->assertEquals(array(2, 3, 4, 5, 6), $pagination['pagesInRange']);
        $this->assertEquals(2, $pagination['firstPageInRange']);
        $this->assertEquals(6, $pagination['lastPageInRange']);
        $this->assertEquals(5, $pagination['currentItemCount']);
        $this->assertEquals(16, $pagination['firstItemNumber']);
        $this->assertEquals(20, $pagination['lastItemNumber']);
    }

    /**
     * @test
     */
    function shouldHandleOddAndEvenRange()
    {
        $p = new Paginator;

        $items = range(1, 43);
        $view = $p->paginate($items, 4, 5);
        $view->setPageRange(4);
        $pagination = $view->getPaginationData();

        $this->assertEquals(3, $pagination['previous']);
        $this->assertEquals(array(3, 4, 5, 6), $pagination['pagesInRange']);
        $this->assertEquals(3, $pagination['firstPageInRange']);
        $this->assertEquals(6, $pagination['lastPageInRange']);

        $view->setPageRange(3);
        $pagination = $view->getPaginationData();

        $this->assertEquals(3, $pagination['previous']);
        $this->assertEquals(array(3, 4, 5), $pagination['pagesInRange']);
        $this->assertEquals(3, $pagination['firstPageInRange']);
        $this->assertEquals(5, $pagination['lastPageInRange']);
    }

    /**
     * @test
     */
    function shouldNotFallbackToPageInCaseIfExceedsItemLimit()
    {
        $p = new Paginator;

        $view = $p->paginate(range(1, 9), 2, 10);
        $items = $view->getItems();
        $this->assertTrue(empty($items));
    }
}
