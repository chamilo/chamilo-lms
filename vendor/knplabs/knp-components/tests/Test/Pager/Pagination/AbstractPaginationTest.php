<?php

use Test\Tool\BaseTestCase;
use Knp\Component\Pager\Paginator;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Test\Mock\PaginationSubscriber as MockPaginationSubscriber;
use Test\Mock\CustomParameterSubscriber;
use Knp\Component\Pager\Event\Subscriber\Paginate\ArraySubscriber;

class AbstractPaginationTest extends BaseTestCase
{
    /**
     * @test
     */
    function shouldCustomizeParameterNames()
    {
        $dispatcher = new EventDispatcher;
        $dispatcher->addSubscriber(new MockPaginationSubscriber); // pagination view
        $dispatcher->addSubscriber(new ArraySubscriber);
        $p = new Paginator($dispatcher);

        $items = array('first', 'second');
        $view = $p->paginate($items, 1, 10);

        // test default names first
        $this->assertEquals('page', $view->getPaginatorOption('pageParameterName'));
        $this->assertEquals('sort', $view->getPaginatorOption('sortFieldParameterName'));
        $this->assertEquals('direction', $view->getPaginatorOption('sortDirectionParameterName'));
        $this->assertTrue($view->getPaginatorOption('distinct'));
        $this->assertNull($view->getPaginatorOption('sortFieldWhitelist'));

        // now customize
        $options = array(
            'pageParameterName' => 'p',
            'sortFieldParameterName' => 's',
            'sortDirectionParameterName' => 'd',
            'distinct' => false,
            'sortFieldWhitelist' => array('a.f', 'a.d')
        );

        $view = $p->paginate($items, 1, 10, $options);

        $this->assertEquals('p', $view->getPaginatorOption('pageParameterName'));
        $this->assertEquals('s', $view->getPaginatorOption('sortFieldParameterName'));
        $this->assertEquals('d', $view->getPaginatorOption('sortDirectionParameterName'));
        $this->assertFalse($view->getPaginatorOption('distinct'));
        $this->assertEquals(array('a.f', 'a.d'), $view->getPaginatorOption('sortFieldWhitelist'));

        // change default paginator options
        $p->setDefaultPaginatorOptions(array(
            'pageParameterName' => 'pg',
            'sortFieldParameterName' => 'srt',
            'sortDirectionParameterName' => 'dir'
        ));
        $view = $p->paginate($items, 1, 10);

        $this->assertEquals('pg', $view->getPaginatorOption('pageParameterName'));
        $this->assertEquals('srt', $view->getPaginatorOption('sortFieldParameterName'));
        $this->assertEquals('dir', $view->getPaginatorOption('sortDirectionParameterName'));
        $this->assertTrue($view->getPaginatorOption('distinct'));
    }
}
