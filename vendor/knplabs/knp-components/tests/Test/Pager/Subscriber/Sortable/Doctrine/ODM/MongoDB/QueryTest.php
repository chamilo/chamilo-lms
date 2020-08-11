<?php

namespace Test\Pager\Subscriber\Sortable\Doctrine\ODM\MongoDB;

use Test\Tool\BaseTestCaseMongoODM;
use Knp\Component\Pager\Paginator;
use Knp\Component\Pager\Pagination\SlidingPagination;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Knp\Component\Pager\Event\Subscriber\Paginate\PaginationSubscriber;
use Knp\Component\Pager\Event\Subscriber\Sortable\Doctrine\ODM\MongoDB\QuerySubscriber as Sortable;
use Test\Fixture\Document\Article;

class QueryTest extends BaseTestCaseMongoODM
{
    /**
     * @test
     */
    function shouldSortSimpleDoctrineQuery()
    {
        $this->populate();

        $dispatcher = new EventDispatcher;
        $dispatcher->addSubscriber(new PaginationSubscriber);
        $dispatcher->addSubscriber(new Sortable);
        $p = new Paginator($dispatcher);

        $_GET['sort'] = 'title';
        $_GET['direction'] = 'asc';
        $qb = $this->dm->createQueryBuilder('Test\Fixture\Document\Article');
        $query = $qb->getQuery();
        $view = $p->paginate($query, 1, 10);

        $items = array_values($view->getItems());
        $this->assertEquals(4, count($items));
        $this->assertEquals('autumn', $items[0]->getTitle());
        $this->assertEquals('spring', $items[1]->getTitle());
        $this->assertEquals('summer', $items[2]->getTitle());
        $this->assertEquals('winter', $items[3]->getTitle());

        $_GET['direction'] = 'desc';
        $view = $p->paginate($query, 1, 10);
        $items = array_values($view->getItems());
        $this->assertEquals(4, count($items));
        $this->assertEquals('winter', $items[0]->getTitle());
        $this->assertEquals('summer', $items[1]->getTitle());
        $this->assertEquals('spring', $items[2]->getTitle());
        $this->assertEquals('autumn', $items[3]->getTitle());
    }

    /**
     * @test
     */
    function shouldSortOnAnyField()
    {
        $_GET['sort'] = '"title\'';
        $_GET['direction'] = 'asc';
        $query = $this
            ->getMockDocumentManager()
            ->createQueryBuilder('Test\Fixture\Document\Article')
            ->getQuery()
        ;

        $p = new Paginator;
        $view = $p->paginate($query, 1, 10);
    }

    private function populate()
    {
        $em = $this->getMockDocumentManager();
        $summer = new Article;
        $summer->setTitle('summer');

        $winter = new Article;
        $winter->setTitle('winter');

        $autumn = new Article;
        $autumn->setTitle('autumn');

        $spring = new Article;
        $spring->setTitle('spring');

        $em->persist($summer);
        $em->persist($winter);
        $em->persist($autumn);
        $em->persist($spring);
        $em->flush();
    }
}
