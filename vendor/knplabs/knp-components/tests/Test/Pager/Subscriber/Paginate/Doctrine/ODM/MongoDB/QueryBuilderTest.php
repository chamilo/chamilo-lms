<?php

namespace Test\Pager\Subscriber\Paginate\Doctrine\ODM\MongoDB;

use Test\Tool\BaseTestCaseMongoODM;
use Knp\Component\Pager\Paginator;
use Test\Fixture\Document\Article;

class QueryBuilderTest extends BaseTestCaseMongoODM
{
    /**
     * @test
     */
    function shouldSupportPaginateStrategySubscriber()
    {
        $this->populate();
        $qb = $this
            ->getMockDocumentManager()
            ->createQueryBuilder('Test\Fixture\Document\Article')
        ;
        $p = new Paginator;
        $pagination = $p->paginate($qb, 1, 2);
        $this->assertEquals(1, $pagination->getCurrentPageNumber());
        $this->assertEquals(2, $pagination->getItemNumberPerPage());
        $this->assertEquals(4, $pagination->getTotalItemCount());

        $items = array_values($pagination->getItems());
        $this->assertEquals(2, count($items));
        $this->assertEquals('summer', $items[0]->getTitle());
        $this->assertEquals('winter', $items[1]->getTitle());
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
