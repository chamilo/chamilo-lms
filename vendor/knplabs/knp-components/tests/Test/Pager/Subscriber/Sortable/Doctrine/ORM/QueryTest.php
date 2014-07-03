<?php

namespace Test\Pager\Subscriber\Sortable\Doctrine\ORM;

use Test\Tool\BaseTestCaseORM;
use Knp\Component\Pager\Paginator;
use Knp\Component\Pager\Pagination\SlidingPagination;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Knp\Component\Pager\Event\Subscriber\Paginate\Doctrine\ORM\QuerySubscriber;
use Knp\Component\Pager\Event\Subscriber\Paginate\PaginationSubscriber;
use Knp\Component\Pager\Event\Subscriber\Sortable\Doctrine\ORM\QuerySubscriber as Sortable;
use Test\Fixture\Entity\Article;

class QueryTest extends BaseTestCaseORM
{
    /**
     * @test
     */
    function shouldHandleApcQueryCache()
    {
        if (!extension_loaded('apc') || !ini_get('apc.enable_cli')) {
            $this->markTestSkipped('APC extension is not loaded.');
        }
        $config = new \Doctrine\ORM\Configuration();
        $config->setMetadataCacheImpl(new \Doctrine\Common\Cache\ApcCache);
        $config->setQueryCacheImpl(new \Doctrine\Common\Cache\ApcCache);
        $config->setProxyDir(__DIR__);
        $config->setProxyNamespace('Gedmo\Mapping\Proxy');
        $config->getAutoGenerateProxyClasses(false);
        $config->setMetadataDriverImpl($this->getMetadataDriverImplementation());

        $conn = array(
            'driver' => 'pdo_sqlite',
            'memory' => true,
        );

        $em = \Doctrine\ORM\EntityManager::create($conn, $config);
        $schema = array_map(function($class) use ($em) {
            return $em->getClassMetadata($class);
        }, (array)$this->getUsedEntityFixtures());

        $schemaTool = new \Doctrine\ORM\Tools\SchemaTool($em);
        $schemaTool->dropSchema(array());
        $schemaTool->createSchema($schema);
        $this->populate($em);

        $_GET['sort'] = 'a.title';
        $_GET['direction'] = 'asc';
        $query = $em->createQuery('SELECT a FROM Test\Fixture\Entity\Article a');

        $p = new Paginator;
        $view = $p->paginate($query, 1, 10);

        $query = $em->createQuery('SELECT a FROM Test\Fixture\Entity\Article a');
        $view = $p->paginate($query, 1, 10);
    }

    /**
     * @test
     */
    function shouldSortSimpleDoctrineQuery()
    {
        $em = $this->getMockSqliteEntityManager();
        $this->populate($em);

        $dispatcher = new EventDispatcher;
        $dispatcher->addSubscriber(new PaginationSubscriber);
        $dispatcher->addSubscriber(new Sortable);
        $p = new Paginator($dispatcher);

        $_GET['sort'] = 'a.title';
        $_GET['direction'] = 'asc';
        $this->startQueryLog();
        $query = $this->em->createQuery('SELECT a FROM Test\Fixture\Entity\Article a');
        $view = $p->paginate($query, 1, 10);

        $items = $view->getItems();
        $this->assertEquals(4, count($items));
        $this->assertEquals('autumn', $items[0]->getTitle());
        $this->assertEquals('spring', $items[1]->getTitle());
        $this->assertEquals('summer', $items[2]->getTitle());
        $this->assertEquals('winter', $items[3]->getTitle());

        $_GET['direction'] = 'desc';
        $view = $p->paginate($query, 1, 10);
        $items = $view->getItems();
        $this->assertEquals(4, count($items));
        $this->assertEquals('winter', $items[0]->getTitle());
        $this->assertEquals('summer', $items[1]->getTitle());
        $this->assertEquals('spring', $items[2]->getTitle());
        $this->assertEquals('autumn', $items[3]->getTitle());

        $this->assertEquals(6, $this->queryAnalyzer->getNumExecutedQueries());
        $executed = $this->queryAnalyzer->getExecutedQueries();
        $this->assertEquals('SELECT DISTINCT a0_.id AS id0, a0_.title AS title1 FROM Article a0_ ORDER BY a0_.title ASC LIMIT 10 OFFSET 0', $executed[1]);
        $this->assertEquals('SELECT DISTINCT a0_.id AS id0, a0_.title AS title1 FROM Article a0_ ORDER BY a0_.title DESC LIMIT 10 OFFSET 0', $executed[4]);
    }

    /**
     * @test
     * @expectedException UnexpectedValueException
     */
    function shouldValidateSortableParameters()
    {
        $_GET['sort'] = '"a.title\'';
        $_GET['direction'] = 'asc';
        $query = $this
            ->getMockSqliteEntityManager()
            ->createQuery('SELECT a FROM Test\Fixture\Entity\Article a')
        ;

        $p = new Paginator;
        $view = $p->paginate($query, 1, 10);
    }

    /**
     * @test
     */
    function shouldSortByAnyAvailableAlias()
    {
        $em = $this->getMockSqliteEntityManager();
        $this->populate($em);

        $_GET['sort'] = 'counter';
        $_GET['direction'] = 'asc';
        $dql = <<<___SQL
        SELECT a, COUNT(a) AS counter
        FROM Test\Fixture\Entity\Article a
___SQL;
        $query = $this->em->createQuery($dql);

        $p = new Paginator;
        $this->startQueryLog();
        $view = $p->paginate($query, 1, 10, array('distinct' => false));

        $this->assertEquals(2, $this->queryAnalyzer->getNumExecutedQueries());
        $executed = $this->queryAnalyzer->getExecutedQueries();
        $this->assertEquals('SELECT a0_.id AS id0, a0_.title AS title1, COUNT(a0_.id) AS sclr2 FROM Article a0_ ORDER BY sclr2 ASC LIMIT 10 OFFSET 0', $executed[1]);
    }

    /**
     * @test
     */
    function shouldWorkWithInitialPaginatorEventDispatcher()
    {
        $em = $this->getMockSqliteEntityManager();
        $this->populate($em);
        $_GET['sort'] = 'a.title';
        $_GET['direction'] = 'asc';
        $query = $this
            ->em
            ->createQuery('SELECT a FROM Test\Fixture\Entity\Article a')
        ;

        $p = new Paginator;
        $this->startQueryLog();
        $view = $p->paginate($query, 1, 10);
        $this->assertTrue($view instanceof SlidingPagination);

        $this->assertEquals(3, $this->queryAnalyzer->getNumExecutedQueries());
        $executed = $this->queryAnalyzer->getExecutedQueries();
        $this->assertEquals('SELECT DISTINCT a0_.id AS id0, a0_.title AS title1 FROM Article a0_ ORDER BY a0_.title ASC LIMIT 10 OFFSET 0', $executed[1]);
    }

    /**
     * @test
     */
    function shouldNotExecuteExtraQueriesWhenCountIsZero()
    {
        $_GET['sort'] = 'a.title';
        $_GET['direction'] = 'asc';
        $query = $this
            ->getMockSqliteEntityManager()
            ->createQuery('SELECT a FROM Test\Fixture\Entity\Article a')
        ;

        $p = new Paginator;
        $this->startQueryLog();
        $view = $p->paginate($query, 1, 10);
        $this->assertTrue($view instanceof SlidingPagination);

        $this->assertEquals(1, $this->queryAnalyzer->getNumExecutedQueries());
    }

    protected function getUsedEntityFixtures()
    {
        return array('Test\Fixture\Entity\Article');
    }

    private function populate($em)
    {
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

    private function getApcEntityManager()
    {
        $config = new \Doctrine\ORM\Configuration();
        $config->setMetadataCacheImpl(new \Doctrine\Common\Cache\ApcCache);
        $config->setQueryCacheImpl(new \Doctrine\Common\Cache\ApcCache);
        $config->setProxyDir(__DIR__);
        $config->setProxyNamespace('Gedmo\Mapping\Proxy');
        $config->setAutoGenerateProxyClasses(false);
        $config->setMetadataDriverImpl($this->getMetadataDriverImplementation());

        $conn = array(
            'driver' => 'pdo_sqlite',
            'memory' => true,
        );

        $em = \Doctrine\ORM\EntityManager::create($conn, $config);
        return $em;
    }
}
