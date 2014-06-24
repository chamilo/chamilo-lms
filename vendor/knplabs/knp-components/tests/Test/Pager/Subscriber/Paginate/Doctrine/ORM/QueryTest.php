<?php

namespace Test\Pager\Subscriber\Paginate\Doctrine\ORM;

use Test\Tool\BaseTestCaseORM;
use Knp\Component\Pager\Paginator;
use Knp\Component\Pager\Pagination\SlidingPagination;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Knp\Component\Pager\Event\Subscriber\Paginate\Doctrine\ORM\QuerySubscriber;
use Knp\Component\Pager\Event\Subscriber\Paginate\PaginationSubscriber;
use Test\Fixture\Entity\Article;

class QueryTest extends BaseTestCaseORM
{
    /**
     * @test
     */
    function shouldPaginateSimpleDoctrineQuery()
    {
        $this->populate();

        $dispatcher = new EventDispatcher;
        $dispatcher->addSubscriber(new QuerySubscriber);
        $dispatcher->addSubscriber(new PaginationSubscriber); // pagination view
        $p = new Paginator($dispatcher);

        $query = $this->em->createQuery('SELECT a FROM Test\Fixture\Entity\Article a');
        $view = $p->paginate($query, 1, 2);

        $this->assertTrue($view instanceof PaginationInterface);
        $this->assertEquals(1, $view->getCurrentPageNumber());
        $this->assertEquals(2, $view->getItemNumberPerPage());
        $this->assertEquals(4, $view->getTotalItemCount());

        $items = $view->getItems();
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
            ->getMockSqliteEntityManager()
            ->createQuery('SELECT a FROM Test\Fixture\Entity\Article a')
        ;
        $p = new Paginator;
        $view = $p->paginate($query, 1, 10);
        $this->assertTrue($view instanceof PaginationInterface);
    }

    protected function getUsedEntityFixtures()
    {
        return array('Test\Fixture\Entity\Article');
    }

    private function populate()
    {
        $em = $this->getMockSqliteEntityManager();
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
