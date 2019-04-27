<?php

namespace Test\Pager\Subscriber\Sortable\Doctrine\ORM;

use Knp\Component\Pager\PaginatorInterface;
use Test\Tool\BaseTestCaseORM;
use Knp\Component\Pager\Paginator;
use Knp\Component\Pager\Pagination\SlidingPagination;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Knp\Component\Pager\Event\Subscriber\Paginate\Doctrine\ORM\QuerySubscriber;
use Knp\Component\Pager\Event\Subscriber\Paginate\PaginationSubscriber;
use Knp\Component\Pager\Event\Subscriber\Sortable\Doctrine\ORM\QuerySubscriber as Sortable;
use Test\Fixture\Entity\Article;

class WhitelistTest extends BaseTestCaseORM
{
    /**
     * @test
     * @expectedException UnexpectedValueException
     */
    function shouldWhitelistSortableFields()
    {
        $this->populate();
        $_GET['sort'] = 'a.title';
        $_GET['direction'] = 'asc';
        $query = $this->em->createQuery('SELECT a FROM Test\Fixture\Entity\Article a');

        $p = new Paginator;
        $sortFieldWhitelist = array('a.title');
        $view = $p->paginate($query, 1, 10, compact(PaginatorInterface::SORT_FIELD_WHITELIST));

        $items = $view->getItems();
        $this->assertEquals(4, count($items));
        $this->assertEquals('autumn', $items[0]->getTitle());

        $_GET['sort'] = 'a.id';
        $view = $p->paginate($query, 1, 10, compact(PaginatorInterface::SORT_FIELD_WHITELIST));
    }

    /**
     * @test
     */
    function shouldSortWithoutSpecificWhitelist()
    {
        $this->populate();
        $_GET['sort'] = 'a.title';
        $_GET['direction'] = 'asc';
        $query = $this->em->createQuery('SELECT a FROM Test\Fixture\Entity\Article a');

        $p = new Paginator;
        $view = $p->paginate($query, 1, 10);

        $items = $view->getItems();
        $this->assertEquals('autumn', $items[0]->getTitle());

        $_GET['sort'] = 'a.id';
        $view = $p->paginate($query, 1, 10);

        $items = $view->getItems();
        $this->assertEquals('summer', $items[0]->getTitle());
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
