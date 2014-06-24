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

class WhitelistTest extends BaseTestCaseMongoODM
{
    /**
     * @test
     * @expectedException UnexpectedValueException
     */
    function shouldWhitelistSortableFields()
    {
        $this->populate();
        $_GET['sort'] = 'title';
        $_GET['direction'] = 'asc';
        $query = $this->dm
            ->createQueryBuilder('Test\Fixture\Document\Article')
            ->getQuery()
        ;

        $p = new Paginator;
        $sortFieldWhitelist = array('title');
        $view = $p->paginate($query, 1, 10, compact('sortFieldWhitelist'));

        $items = array_values($view->getItems());
        $this->assertEquals(4, count($items));
        $this->assertEquals('autumn', $items[0]->getTitle());

        $_GET['sort'] = 'id';
        $view = $p->paginate($query, 1, 10, compact('sortFieldWhitelist'));
    }

    /**
     * @test
     */
    function shouldSortWithoutSpecificWhitelist()
    {
        $this->populate();
        $_GET['sort'] = 'title';
        $_GET['direction'] = 'asc';
        $query = $this->dm
            ->createQueryBuilder('Test\Fixture\Document\Article')
            ->getQuery()
        ;

        $p = new Paginator;
        $view = $p->paginate($query, 1, 10);

        $items = array_values($view->getItems());
        $this->assertEquals('autumn', $items[0]->getTitle());

        $_GET['sort'] = 'id';
        $view = $p->paginate($query, 1, 10);

        $items = array_values($view->getItems());
        $this->assertEquals('summer', $items[0]->getTitle());
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
