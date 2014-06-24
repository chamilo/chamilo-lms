<?php

namespace Test\Pager\Subscriber\Paginate\Doctrine\ORM;

use Test\Tool\BaseTestCaseORM;
use Knp\Component\Pager\Paginator;
use Test\Fixture\Entity\Composite;

class CompositeKeyTest extends BaseTestCaseORM
{
    /**
     * @test
     * @expectedException Doctrine\ORM\Mapping\MappingException
     */
    function shouldNotBeAbleToCountCompositeKeyOnByCountQueryWalker()
    {
        $p = new Paginator;
        $em = $this->getMockSqliteEntityManager();

        $query = $em->createQuery('SELECT c FROM Test\Fixture\Entity\Composite c');
        $view = $p->paginate($query);
    }

    /**
     * @test
     */
    function shouldBeHandledByQueryHintByPassingCount()
    {
        $p = new Paginator;
        $em = $this->getMockSqliteEntityManager();

        $count = $em
            ->createQuery('SELECT COUNT(c) FROM Test\Fixture\Entity\Composite c')
            ->getSingleScalarResult()
        ;

        $query = $em
            ->createQuery('SELECT c FROM Test\Fixture\Entity\Composite c')
            ->setHint('knp_paginator.count', $count)
        ;
        $view = $p->paginate($query);

        $items = $view->getItems();
        $this->assertEquals(0, count($items));
    }

    protected function getUsedEntityFixtures()
    {
        return array('Test\Fixture\Entity\Composite');
    }
}
