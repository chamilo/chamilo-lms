<?php

use Elastica\Query;
use Elastica\Query\Term;
use Elastica\Result;
use Elastica\Type;
use Knp\Component\Pager\Paginator;
use Knp\Component\Pager\Event\Subscriber\Paginate\ElasticaQuerySubscriber;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Test\Mock\PaginationSubscriber as MockPaginationSubscriber;
use Test\Tool\BaseTestCase;

class ElasticaTest extends BaseTestCase
{
    public function testElasticaSubscriber()
    {
        $dispatcher = new EventDispatcher;
        $dispatcher->addSubscriber(new ElasticaQuerySubscriber());
        $dispatcher->addSubscriber(new MockPaginationSubscriber); // pagination view
        $p = new Paginator($dispatcher);

        $query = Query::create(new Term(array(
            'name' => 'Fred',
        )));
        $response = $this->getMockBuilder('Elastica\\ResultSet')->disableOriginalConstructor()->getMock();
        $response->expects($this->once())
            ->method('getTotalHits')
            ->will($this->returnValue(2));
        $response->expects($this->once())
            ->method('getResults')
            ->will($this->returnValue(array(new Result(array()), new Result(array()))));
        $searchable = $this->getMockBuilder('Elastica\\SearchableInterface')->getMock();
        $searchable->expects($this->once())
            ->method('search')
            ->with($query)
            ->will($this->returnValue($response));

        $view = $p->paginate(array($searchable, $query), 1, 10);

        $this->assertEquals(0, $query->getParam('from'), 'Query offset set correctly');
        $this->assertEquals(10, $query->getParam('size'), 'Query limit set correctly');
        $this->assertSame($response, $view->getCustomParameter('resultSet'), 'Elastica ResultSet available in Paginator');

        $this->assertEquals(1, $view->getCurrentPageNumber());
        $this->assertEquals(10, $view->getItemNumberPerPage());
        $this->assertEquals(2, count($view->getItems()));
        $this->assertEquals(2, $view->getTotalItemCount());
    }

    /**
     * @test
     */
    function shouldSlicePaginateAnArray()
    {
        /*$dispatcher = new EventDispatcher;
        $dispatcher->addSubscriber(new ArraySubscriber);
        $dispatcher->addSubscriber(new MockPaginationSubscriber); // pagination view
        $p = new Paginator($dispatcher);

        $items = range('a', 'u');
        $view = $p->paginate($items, 2, 10);

        $this->assertEquals(2, $view->getCurrentPageNumber());
        $this->assertEquals(10, $view->getItemNumberPerPage());
        $this->assertEquals(10, count($view->getItems()));
        $this->assertEquals(21, $view->getTotalItemCount());*/
    }
}
