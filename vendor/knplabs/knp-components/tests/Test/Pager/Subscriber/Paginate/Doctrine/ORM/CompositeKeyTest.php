<?php

namespace Test\Pager\Subscriber\Paginate\Doctrine\ORM;

use Test\Tool\BaseTestCaseORM;
use Knp\Component\Pager\Paginator;
use Test\Fixture\Entity\Composite;
use Knp\Component\Pager\Event\Subscriber\Paginate\Doctrine\ORM\QuerySubscriber\UsesPaginator;
use Doctrine\ORM\Query;

class CompositeKeyTest extends BaseTestCaseORM
{
    /**
     * @test
     */
    function shouldBeHandledByQueryHintByPassingCount()
    {
        $p = new Paginator;
        $em = $this->getMockSqliteEntityManager();
        $this->populate($em);

        $count = $em
            ->createQuery('SELECT COUNT(c) FROM Test\Fixture\Entity\Composite c')
            ->getSingleScalarResult()
        ;

        $query = $em
            ->createQuery('SELECT c FROM Test\Fixture\Entity\Composite c')
            ->setHint('knp_paginator.count', $count)
        ;
        $query->setHint(UsesPaginator::HINT_FETCH_JOIN_COLLECTION, false);
        $view = $p->paginate($query, 1, 10, array('wrap-queries' => true));

        $items = $view->getItems();
        $this->assertEquals(4, count($items));
    }

    protected function getUsedEntityFixtures()
    {
        return array('Test\Fixture\Entity\Composite');
    }

    private function populate($em)
    {
        $summer = new Composite;
        $summer->setId(1);
        $summer->setTitle('summer');
        $summer->setUid(100);

        $winter = new Composite;
        $winter->setId(2);
        $winter->setTitle('winter');
        $winter->setUid(200);

        $autumn = new Composite;
        $autumn->setId(3);
        $autumn->setTitle('autumn');
        $autumn->setUid(300);

        $spring = new Composite;
        $spring->setId(4);
        $spring->setTitle('spring');
        $spring->setUid(400);

        $em->persist($summer);
        $em->persist($winter);
        $em->persist($autumn);
        $em->persist($spring);
        $em->flush();
    }

}
