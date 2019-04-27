<?php

namespace Test\Pager\Subscriber\Paginate\Doctrine\ODM\MongoDB;

use Test\Tool\BaseTestCaseMongoODM;
use Knp\Component\Pager\Paginator;
use Knp\Component\Pager\Pagination\SlidingPagination;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Knp\Component\Pager\Event\Subscriber\Paginate\Doctrine\ODM\MongoDB\QuerySubscriber;
use Knp\Component\Pager\Event\Subscriber\Paginate\PaginationSubscriber;
use Test\Fixture\Document\Article;

class QueryTest extends BaseTestCaseMongoODM
{
    /**
     * @test
     */
    function shouldPaginateSimpleDoctrineMongoDBQuery()
    {
        $this->populate();

        $dispatcher = new EventDispatcher;
        $dispatcher->addSubscriber(new QuerySubscriber);
        $dispatcher->addSubscriber(new PaginationSubscriber); // pagination view
        $p = new Paginator($dispatcher);

        $qb = $this->dm->createQueryBuilder('Test\Fixture\Document\Article');
        $query = $qb->getQuery();
        $pagination = $p->paginate($query, 1, 2);

        $this->assertTrue($pagination instanceof SlidingPagination);
        $this->assertEquals(1, $pagination->getCurrentPageNumber());
        $this->assertEquals(2, $pagination->getItemNumberPerPage());
        $this->assertEquals(4, $pagination->getTotalItemCount());

        $items = array_values($pagination->getItems());
        $this->assertEquals(2, count($items));
        $this->assertEquals('summer', $items[0]->getTitle());
        $this->assertEquals('winter', $items[1]->getTitle());
    }

    /**
     * @test
     */
    function shouldSupportPaginateStrategySubscriber()
    {
        $query = $this
            ->getMockDocumentManager()
            ->createQueryBuilder('Test\Fixture\Document\Article')
            ->getQuery()
        ;
        $p = new Paginator;
        $pagination = $p->paginate($query, 1, 10);
        $this->assertTrue($pagination instanceof SlidingPagination);
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
