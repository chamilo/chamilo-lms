<?php

namespace Test\Pager\Subscriber\Filtration\Doctrine\ORM;

use Test\Tool\BaseTestCaseORM;
use Knp\Component\Pager\Paginator;
use Knp\Component\Pager\Pagination\SlidingPagination;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Knp\Component\Pager\Event\Subscriber\Paginate\PaginationSubscriber;
use Knp\Component\Pager\Event\Subscriber\Filtration\FiltrationSubscriber as Filtration;
use Test\Fixture\Entity\Article;
use Knp\Component\Pager\Event\Subscriber\Paginate\Doctrine\ORM\QuerySubscriber\UsesPaginator;
use Knp\Component\Pager\PaginatorInterface;

class QueryTest extends BaseTestCaseORM
{
    /**
     * @test
     */
    public function shouldHandleApcQueryCache()
    {
        if (!extension_loaded('apc') || !ini_get('apc.enable_cli')) {
            $this->markTestSkipped('APC extension is not loaded.');
        }
        $config = new \Doctrine\ORM\Configuration();
        $config->setMetadataCacheImpl(new \Doctrine\Common\Cache\ApcCache());
        $config->setQueryCacheImpl(new \Doctrine\Common\Cache\ApcCache());
        $config->setProxyDir(__DIR__);
        $config->setProxyNamespace('Gedmo\Mapping\Proxy');
        $config->getAutoGenerateProxyClasses(false);
        $config->setMetadataDriverImpl($this->getMetadataDriverImplementation());

        $conn = array(
            'driver' => 'pdo_sqlite',
            'memory' => true,
        );

        $em = \Doctrine\ORM\EntityManager::create($conn, $config);
        $schema = array_map(function ($class) use ($em) {
            return $em->getClassMetadata($class);
        }, (array) $this->getUsedEntityFixtures());

        $schemaTool = new \Doctrine\ORM\Tools\SchemaTool($em);
        $schemaTool->dropSchema(array());
        $schemaTool->createSchema($schema);
        $this->populate($em);

        $_GET['filterField'] = 'a.title';
        $_GET['filterValue'] = 'summer';
        $query = $em->createQuery('SELECT a FROM Test\Fixture\Entity\Article a');

        $p = new Paginator();
        $view = $p->paginate($query, 1, 10);

        $query = $em->createQuery('SELECT a FROM Test\Fixture\Entity\Article a');
        $view = $p->paginate($query, 1, 10);
    }

    /**
     * @test
     */
    public function shouldFilterSimpleDoctrineQuery()
    {
        $em = $this->getMockSqliteEntityManager();
        $this->populate($em);

        $dispatcher = new EventDispatcher();
        $dispatcher->addSubscriber(new PaginationSubscriber());
        $dispatcher->addSubscriber(new Filtration());
        $p = new Paginator($dispatcher);

        $_GET['filterParam'] = 'a.title';
        $_GET['filterValue'] = '*er';
        $this->startQueryLog();
        $query = $this->em->createQuery('SELECT a FROM Test\Fixture\Entity\Article a');
        $query->setHint(UsesPaginator::HINT_FETCH_JOIN_COLLECTION, false);
        $view = $p->paginate($query, 1, 10);

        $items = $view->getItems();
        $this->assertEquals(2, count($items));
        $this->assertEquals('summer', $items[0]->getTitle());
        $this->assertEquals('winter', $items[1]->getTitle());

        $_GET['filterValue'] = 'summer';
        $view = $p->paginate($query, 1, 10);
        $items = $view->getItems();
        $this->assertEquals(1, count($items));
        $this->assertEquals('summer', $items[0]->getTitle());

        $this->assertEquals(4, $this->queryAnalyzer->getNumExecutedQueries());
        $executed = $this->queryAnalyzer->getExecutedQueries();

        // Different aliases separators according to Doctrine version
        if (version_compare(\Doctrine\ORM\Version::VERSION, '2.5', '<')) {
            $this->assertEquals('SELECT a0_.id AS id0, a0_.title AS title1, a0_.enabled AS enabled2 FROM Article a0_ WHERE a0_.title LIKE \'%er\' LIMIT 10 OFFSET 0', $executed[1]);
            $this->assertEquals('SELECT a0_.id AS id0, a0_.title AS title1, a0_.enabled AS enabled2 FROM Article a0_ WHERE a0_.title LIKE \'summer\' LIMIT 10 OFFSET 0', $executed[3]);
        } else {
            $this->assertEquals('SELECT a0_.id AS id_0, a0_.title AS title_1, a0_.enabled AS enabled_2 FROM Article a0_ WHERE a0_.title LIKE \'%er\' LIMIT 10 OFFSET 0', $executed[1]);
            $this->assertEquals('SELECT a0_.id AS id_0, a0_.title AS title_1, a0_.enabled AS enabled_2 FROM Article a0_ WHERE a0_.title LIKE \'summer\' LIMIT 10 OFFSET 0', $executed[3]);
        }
    }

    /**
     * @test
     */
    public function shouldFilterBooleanFilterValues()
    {
        $em = $this->getMockSqliteEntityManager();
        $this->populate($em);

        $dispatcher = new EventDispatcher();
        $dispatcher->addSubscriber(new PaginationSubscriber());
        $dispatcher->addSubscriber(new Filtration());
        $p = new Paginator($dispatcher);
        $this->startQueryLog();

        $_GET['filterParam'] = 'a.enabled';
        $_GET['filterValue'] = '1';
        $query = $this->em->createQuery('SELECT a FROM Test\Fixture\Entity\Article a');
        $query->setHint(UsesPaginator::HINT_FETCH_JOIN_COLLECTION, false);
        $view = $p->paginate($query, 1, 10);
        $items = $view->getItems();
        $this->assertEquals(2, count($items));
        $this->assertEquals('summer', $items[0]->getTitle());
        $this->assertEquals('winter', $items[1]->getTitle());

        $_GET['filterValue'] = 'true';
        $query = $this->em->createQuery('SELECT a FROM Test\Fixture\Entity\Article a');
        $query->setHint(UsesPaginator::HINT_FETCH_JOIN_COLLECTION, false);
        $view = $p->paginate($query, 1, 10);
        $items = $view->getItems();
        $this->assertEquals(2, count($items));
        $this->assertEquals('summer', $items[0]->getTitle());
        $this->assertEquals('winter', $items[1]->getTitle());

        $_GET['filterValue'] = '0';
        $view = $p->paginate($query, 1, 10);
        $items = $view->getItems();
        $this->assertEquals(2, count($items));
        $this->assertEquals('autumn', $items[0]->getTitle());
        $this->assertEquals('spring', $items[1]->getTitle());

        $_GET['filterValue'] = 'false';
        $view = $p->paginate($query, 1, 10);
        $items = $view->getItems();
        $this->assertEquals(2, count($items));
        $this->assertEquals('autumn', $items[0]->getTitle());
        $this->assertEquals('spring', $items[1]->getTitle());

        $this->assertEquals(8, $this->queryAnalyzer->getNumExecutedQueries());
        $executed = $this->queryAnalyzer->getExecutedQueries();

        // Different aliases separators according to Doctrine version
        if (version_compare(\Doctrine\ORM\Version::VERSION, '2.5', '<')) {
            $this->assertEquals('SELECT a0_.id AS id0, a0_.title AS title1, a0_.enabled AS enabled2 FROM Article a0_ WHERE a0_.enabled = 1 LIMIT 10 OFFSET 0', $executed[1]);
            $this->assertEquals('SELECT a0_.id AS id0, a0_.title AS title1, a0_.enabled AS enabled2 FROM Article a0_ WHERE a0_.enabled = 1 LIMIT 10 OFFSET 0', $executed[3]);
            $this->assertEquals('SELECT a0_.id AS id0, a0_.title AS title1, a0_.enabled AS enabled2 FROM Article a0_ WHERE a0_.enabled = 0 LIMIT 10 OFFSET 0', $executed[5]);
            $this->assertEquals('SELECT a0_.id AS id0, a0_.title AS title1, a0_.enabled AS enabled2 FROM Article a0_ WHERE a0_.enabled = 0 LIMIT 10 OFFSET 0', $executed[7]);
        } else {
            $this->assertEquals('SELECT a0_.id AS id_0, a0_.title AS title_1, a0_.enabled AS enabled_2 FROM Article a0_ WHERE a0_.enabled = 1 LIMIT 10 OFFSET 0', $executed[1]);
            $this->assertEquals('SELECT a0_.id AS id_0, a0_.title AS title_1, a0_.enabled AS enabled_2 FROM Article a0_ WHERE a0_.enabled = 1 LIMIT 10 OFFSET 0', $executed[3]);
            $this->assertEquals('SELECT a0_.id AS id_0, a0_.title AS title_1, a0_.enabled AS enabled_2 FROM Article a0_ WHERE a0_.enabled = 0 LIMIT 10 OFFSET 0', $executed[5]);
            $this->assertEquals('SELECT a0_.id AS id_0, a0_.title AS title_1, a0_.enabled AS enabled_2 FROM Article a0_ WHERE a0_.enabled = 0 LIMIT 10 OFFSET 0', $executed[7]);
        }
    }

    /**
     * @test
     */
    public function shouldNotFilterInvalidBooleanFilterValues()
    {
        $em = $this->getMockSqliteEntityManager();
        $this->populate($em);

        $dispatcher = new EventDispatcher();
        $dispatcher->addSubscriber(new PaginationSubscriber());
        $dispatcher->addSubscriber(new Filtration());
        $p = new Paginator($dispatcher);
        $this->startQueryLog();

        $_GET['filterParam'] = 'a.enabled';
        $_GET['filterValue'] = 'invalid_boolean';
        $query = $this->em->createQuery('SELECT a FROM Test\Fixture\Entity\Article a');
        $query->setHint(UsesPaginator::HINT_FETCH_JOIN_COLLECTION, false);
        $view = $p->paginate($query, 1, 10);
        $items = $view->getItems();
        $this->assertEquals(4, count($items));

        $this->assertEquals(2, $this->queryAnalyzer->getNumExecutedQueries());
        $executed = $this->queryAnalyzer->getExecutedQueries();

        // Different aliases separators according to Doctrine version
        if (version_compare(\Doctrine\ORM\Version::VERSION, '2.5', '<')) {
            $this->assertEquals('SELECT a0_.id AS id0, a0_.title AS title1, a0_.enabled AS enabled2 FROM Article a0_ LIMIT 10 OFFSET 0', $executed[1]);
        } else {
            $this->assertEquals('SELECT a0_.id AS id_0, a0_.title AS title_1, a0_.enabled AS enabled_2 FROM Article a0_ LIMIT 10 OFFSET 0', $executed[1]);
        }
    }

    /**
     * @test
     */
    public function shouldFilterNumericFilterValues()
    {
        $em = $this->getMockSqliteEntityManager();
        $this->populateNumeric($em);

        $dispatcher = new EventDispatcher();
        $dispatcher->addSubscriber(new PaginationSubscriber());
        $dispatcher->addSubscriber(new Filtration());
        $p = new Paginator($dispatcher);
        $this->startQueryLog();

        $_GET['filterParam'] = 'a.title';
        $_GET['filterValue'] = '0';
        $query = $this->em->createQuery('SELECT a FROM Test\Fixture\Entity\Article a');
        $query->setHint(UsesPaginator::HINT_FETCH_JOIN_COLLECTION, false);
        $view = $p->paginate($query, 1, 10);
        $items = $view->getItems();
        $this->assertEquals(1, count($items));
        $this->assertEquals('0', $items[0]->getTitle());

        $_GET['filterValue'] = '1';
        $query = $this->em->createQuery('SELECT a FROM Test\Fixture\Entity\Article a');
        $query->setHint(UsesPaginator::HINT_FETCH_JOIN_COLLECTION, false);
        $view = $p->paginate($query, 1, 10);
        $items = $view->getItems();
        $this->assertEquals(1, count($items));
        $this->assertEquals('1', $items[0]->getTitle());

        $this->assertEquals(4, $this->queryAnalyzer->getNumExecutedQueries());
        $executed = $this->queryAnalyzer->getExecutedQueries();

        // Different aliases separators according to Doctrine version
        if (version_compare(\Doctrine\ORM\Version::VERSION, '2.5', '<')) {
            $this->assertEquals('SELECT a0_.id AS id0, a0_.title AS title1, a0_.enabled AS enabled2 FROM Article a0_ WHERE a0_.title = 0 LIMIT 10 OFFSET 0', $executed[1]);
            $this->assertEquals('SELECT a0_.id AS id0, a0_.title AS title1, a0_.enabled AS enabled2 FROM Article a0_ WHERE a0_.title = 1 LIMIT 10 OFFSET 0', $executed[3]);
        } else {
            $this->assertEquals('SELECT a0_.id AS id_0, a0_.title AS title_1, a0_.enabled AS enabled_2 FROM Article a0_ WHERE a0_.title = 0 LIMIT 10 OFFSET 0', $executed[1]);
            $this->assertEquals('SELECT a0_.id AS id_0, a0_.title AS title_1, a0_.enabled AS enabled_2 FROM Article a0_ WHERE a0_.title = 1 LIMIT 10 OFFSET 0', $executed[3]);
        }
    }

    /**
     * @test
     */
    public function shouldFilterComplexDoctrineQuery()
    {
        $em = $this->getMockSqliteEntityManager();
        $this->populate($em);

        $dispatcher = new EventDispatcher();
        $dispatcher->addSubscriber(new PaginationSubscriber());
        $dispatcher->addSubscriber(new Filtration());
        $p = new Paginator($dispatcher);

        $_GET['filterParam'] = 'a.title';
        $_GET['filterValue'] = '*er';
        $this->startQueryLog();
        $query = $this->em->createQuery('SELECT a FROM Test\Fixture\Entity\Article a WHERE a.title <> \'\' AND (a.title LIKE \'summer\' OR a.title LIKE \'spring\')');
        $query->setHint(UsesPaginator::HINT_FETCH_JOIN_COLLECTION, false);
        $view = $p->paginate($query, 1, 10);
        $items = $view->getItems();
        $this->assertEquals(1, count($items));
        $this->assertEquals('summer', $items[0]->getTitle());
        $_GET['filterParam'] = 'a.id,a.title';
        $view = $p->paginate($query, 1, 10);
        $items = $view->getItems();
        $this->assertEquals(1, count($items));
        $this->assertEquals('summer', $items[0]->getTitle());
        $executed = $this->queryAnalyzer->getExecutedQueries();
        $query = $this->em->createQuery('SELECT a FROM Test\Fixture\Entity\Article a WHERE a.title <> \'\' OR (a.title LIKE \'summer\' OR a.title LIKE \'spring\')');
        $query->setHint(UsesPaginator::HINT_FETCH_JOIN_COLLECTION, false);
        $view = $p->paginate($query, 1, 10);
        $items = $view->getItems();
        $this->assertEquals(2, count($items));
        $this->assertEquals('summer', $items[0]->getTitle());
        $this->assertEquals('winter', $items[1]->getTitle());
        $executed = $this->queryAnalyzer->getExecutedQueries();
        $query = $this->em->createQuery('SELECT a FROM Test\Fixture\Entity\Article a WHERE a.title <> \'\'');
        $query->setHint(UsesPaginator::HINT_FETCH_JOIN_COLLECTION, false);
        $view = $p->paginate($query, 1, 10);
        $items = $view->getItems();
        $this->assertEquals(2, count($items));
        $this->assertEquals('summer', $items[0]->getTitle());
        $this->assertEquals('winter', $items[1]->getTitle());
        $executed = $this->queryAnalyzer->getExecutedQueries();
        $_GET['filterParam'] = 'a.title';
        $view = $p->paginate($query, 1, 10);
        $items = $view->getItems();
        $this->assertEquals(2, count($items));
        $this->assertEquals('summer', $items[0]->getTitle());
        $this->assertEquals('winter', $items[1]->getTitle());
        $executed = $this->queryAnalyzer->getExecutedQueries();

        // Different aliases separators according to Doctrine version
        if (version_compare(\Doctrine\ORM\Version::VERSION, '2.5', '<')) {
            $this->assertEquals('SELECT a0_.id AS id0, a0_.title AS title1, a0_.enabled AS enabled2 FROM Article a0_ WHERE a0_.title LIKE \'%er\' AND a0_.title <> \'\' AND (a0_.title LIKE \'summer\' OR a0_.title LIKE \'spring\') LIMIT 10 OFFSET 0', $executed[1]);
            $this->assertEquals('SELECT a0_.id AS id0, a0_.title AS title1, a0_.enabled AS enabled2 FROM Article a0_ WHERE (a0_.id LIKE \'%er\' OR a0_.title LIKE \'%er\') AND a0_.title <> \'\' AND (a0_.title LIKE \'summer\' OR a0_.title LIKE \'spring\') LIMIT 10 OFFSET 0', $executed[3]);
            $this->assertEquals('SELECT a0_.id AS id0, a0_.title AS title1, a0_.enabled AS enabled2 FROM Article a0_ WHERE (a0_.id LIKE \'%er\' OR a0_.title LIKE \'%er\') AND (a0_.title <> \'\' OR (a0_.title LIKE \'summer\' OR a0_.title LIKE \'spring\')) LIMIT 10 OFFSET 0', $executed[5]);
            $this->assertEquals('SELECT a0_.id AS id0, a0_.title AS title1, a0_.enabled AS enabled2 FROM Article a0_ WHERE (a0_.id LIKE \'%er\' OR a0_.title LIKE \'%er\') AND a0_.title <> \'\' LIMIT 10 OFFSET 0', $executed[7]);
            $this->assertEquals('SELECT a0_.id AS id0, a0_.title AS title1, a0_.enabled AS enabled2 FROM Article a0_ WHERE a0_.title LIKE \'%er\' AND a0_.title <> \'\' LIMIT 10 OFFSET 0', $executed[9]);
        } else {
            $this->assertEquals('SELECT a0_.id AS id_0, a0_.title AS title_1, a0_.enabled AS enabled_2 FROM Article a0_ WHERE a0_.title LIKE \'%er\' AND a0_.title <> \'\' AND (a0_.title LIKE \'summer\' OR a0_.title LIKE \'spring\') LIMIT 10 OFFSET 0', $executed[1]);
            $this->assertEquals('SELECT a0_.id AS id_0, a0_.title AS title_1, a0_.enabled AS enabled_2 FROM Article a0_ WHERE (a0_.id LIKE \'%er\' OR a0_.title LIKE \'%er\') AND a0_.title <> \'\' AND (a0_.title LIKE \'summer\' OR a0_.title LIKE \'spring\') LIMIT 10 OFFSET 0', $executed[3]);
            $this->assertEquals('SELECT a0_.id AS id_0, a0_.title AS title_1, a0_.enabled AS enabled_2 FROM Article a0_ WHERE (a0_.id LIKE \'%er\' OR a0_.title LIKE \'%er\') AND (a0_.title <> \'\' OR (a0_.title LIKE \'summer\' OR a0_.title LIKE \'spring\')) LIMIT 10 OFFSET 0', $executed[5]);
            $this->assertEquals('SELECT a0_.id AS id_0, a0_.title AS title_1, a0_.enabled AS enabled_2 FROM Article a0_ WHERE (a0_.id LIKE \'%er\' OR a0_.title LIKE \'%er\') AND a0_.title <> \'\' LIMIT 10 OFFSET 0', $executed[7]);
            $this->assertEquals('SELECT a0_.id AS id_0, a0_.title AS title_1, a0_.enabled AS enabled_2 FROM Article a0_ WHERE a0_.title LIKE \'%er\' AND a0_.title <> \'\' LIMIT 10 OFFSET 0', $executed[9]);
        }
    }

    /**
     * @test
     */
    public function shouldFilterSimpleDoctrineQueryWithMultipleProperties()
    {
        $em = $this->getMockSqliteEntityManager();
        $this->populate($em);

        $dispatcher = new EventDispatcher();
        $dispatcher->addSubscriber(new PaginationSubscriber());
        $dispatcher->addSubscriber(new Filtration());
        $p = new Paginator($dispatcher);

        $_GET['filterParam'] = 'a.id,a.title';
        $_GET['filterValue'] = '*er';
        $this->startQueryLog();
        $query = $this->em->createQuery('SELECT a FROM Test\Fixture\Entity\Article a');
        $query->setHint(UsesPaginator::HINT_FETCH_JOIN_COLLECTION, false);
        $view = $p->paginate($query, 1, 10);
        $items = $view->getItems();
        $this->assertEquals(2, count($items));
        $this->assertEquals('summer', $items[0]->getTitle());
        $this->assertEquals('winter', $items[1]->getTitle());

        $_GET['filterParam'] = array('a.id', 'a.title');
        $view = $p->paginate($query, 1, 10);
        $items = $view->getItems();
        $this->assertEquals(2, count($items));
        $this->assertEquals('summer', $items[0]->getTitle());
        $this->assertEquals('winter', $items[1]->getTitle());
        $executed = $this->queryAnalyzer->getExecutedQueries();

        // Different aliases separators according to Doctrine version
        if (version_compare(\Doctrine\ORM\Version::VERSION, '2.5', '<')) {
            $this->assertEquals('SELECT a0_.id AS id0, a0_.title AS title1, a0_.enabled AS enabled2 FROM Article a0_ WHERE a0_.id LIKE \'%er\' OR a0_.title LIKE \'%er\' LIMIT 10 OFFSET 0', $executed[1]);
            $this->assertEquals('SELECT a0_.id AS id0, a0_.title AS title1, a0_.enabled AS enabled2 FROM Article a0_ WHERE a0_.id LIKE \'%er\' OR a0_.title LIKE \'%er\' LIMIT 10 OFFSET 0', $executed[3]);
        } else {
            $this->assertEquals('SELECT a0_.id AS id_0, a0_.title AS title_1, a0_.enabled AS enabled_2 FROM Article a0_ WHERE a0_.id LIKE \'%er\' OR a0_.title LIKE \'%er\' LIMIT 10 OFFSET 0', $executed[1]);
            $this->assertEquals('SELECT a0_.id AS id_0, a0_.title AS title_1, a0_.enabled AS enabled_2 FROM Article a0_ WHERE a0_.id LIKE \'%er\' OR a0_.title LIKE \'%er\' LIMIT 10 OFFSET 0', $executed[3]);
        }
    }

    /**
     * @test
     */
    public function shouldFilterComplexDoctrineQueryWithMultipleProperties()
    {
        $em = $this->getMockSqliteEntityManager();
        $this->populate($em);

        $dispatcher = new EventDispatcher();
        $dispatcher->addSubscriber(new PaginationSubscriber());
        $dispatcher->addSubscriber(new Filtration());
        $p = new Paginator($dispatcher);

        $_GET['filterParam'] = 'a.id,a.title';
        $_GET['filterValue'] = '*er';
        $this->startQueryLog();
        $query = $this->em->createQuery('SELECT a FROM Test\Fixture\Entity\Article a WHERE a.title <> \'\' AND (a.title LIKE \'summer\' OR a.title LIKE \'spring\')');
        $query->setHint(UsesPaginator::HINT_FETCH_JOIN_COLLECTION, false);
        $view = $p->paginate($query, 1, 10);
        $items = $view->getItems();
        $this->assertEquals(1, count($items));
        $this->assertEquals('summer', $items[0]->getTitle());

        $executed = $this->queryAnalyzer->getExecutedQueries();

        // Different aliases separators according to Doctrine version
        if (version_compare(\Doctrine\ORM\Version::VERSION, '2.5', '<')) {
            $this->assertEquals('SELECT a0_.id AS id0, a0_.title AS title1, a0_.enabled AS enabled2 FROM Article a0_ WHERE (a0_.id LIKE \'%er\' OR a0_.title LIKE \'%er\') AND a0_.title <> \'\' AND (a0_.title LIKE \'summer\' OR a0_.title LIKE \'spring\') LIMIT 10 OFFSET 0', $executed[1]);
        } else {
            $this->assertEquals('SELECT a0_.id AS id_0, a0_.title AS title_1, a0_.enabled AS enabled_2 FROM Article a0_ WHERE (a0_.id LIKE \'%er\' OR a0_.title LIKE \'%er\') AND a0_.title <> \'\' AND (a0_.title LIKE \'summer\' OR a0_.title LIKE \'spring\') LIMIT 10 OFFSET 0', $executed[1]);
        }
    }

    /**
     * @test
     * @expectedException UnexpectedValueException
     */
    public function shouldValidateFiltrationParameter()
    {
        $_GET['filterParam'] = '"a.title\'';
        $_GET['filterValue'] = 'summer';
        $query = $this
            ->getMockSqliteEntityManager()
            ->createQuery('SELECT a FROM Test\Fixture\Entity\Article a')
        ;

        $dispatcher = new EventDispatcher();
        $dispatcher->addSubscriber(new PaginationSubscriber());
        $dispatcher->addSubscriber(new Filtration());
        $p = new Paginator($dispatcher);
        $view = $p->paginate($query, 1, 10);
        $items = $view->getItems();
    }

    /**
     * @test
     * @expectedException UnexpectedValueException
     */
    public function shouldValidateFiltrationParameterWithoutAlias()
    {
        $_GET['filterParam'] = 'title';
        $_GET['filterValue'] = 'summer';
        $query = $this
            ->getMockSqliteEntityManager()
            ->createQuery('SELECT a FROM Test\Fixture\Entity\Article a')
        ;

        $dispatcher = new EventDispatcher();
        $dispatcher->addSubscriber(new PaginationSubscriber());
        $dispatcher->addSubscriber(new Filtration());
        $p = new Paginator($dispatcher);
        $view = $p->paginate($query, 1, 10);
        $items = $view->getItems();
    }

    /**
     * @test
     * @expectedException UnexpectedValueException
     */
    public function shouldValidateFiltrationParameterExistance()
    {
        $_GET['filterParam'] = 'a.nonExistantField';
        $_GET['filterValue'] = 'summer';
        $query = $this
            ->getMockSqliteEntityManager()
            ->createQuery('SELECT a FROM Test\Fixture\Entity\Article a')
        ;

        $dispatcher = new EventDispatcher();
        $dispatcher->addSubscriber(new PaginationSubscriber());
        $dispatcher->addSubscriber(new Filtration());
        $p = new Paginator($dispatcher);
        $view = $p->paginate($query, 1, 10);
        $items = $view->getItems();
    }

    /**
     * @test
     */
    public function shouldFilterByAnyAvailableAlias()
    {
        $em = $this->getMockSqliteEntityManager();
        $this->populate($em);

        $_GET['filterParam'] = 'test_alias';
        $_GET['filterValue'] = '*er';
        $dql = <<<___SQL
        SELECT a, a.title AS test_alias
        FROM Test\Fixture\Entity\Article a
___SQL;
        $query = $this->em->createQuery($dql);
        $query->setHint(UsesPaginator::HINT_FETCH_JOIN_COLLECTION, false);

        $dispatcher = new EventDispatcher();
        $dispatcher->addSubscriber(new PaginationSubscriber());
        $dispatcher->addSubscriber(new Filtration());
        $p = new Paginator($dispatcher);
        $this->startQueryLog();
        $view = $p->paginate($query, 1, 10, array(PaginatorInterface::DISTINCT => false));
        $items = $view->getItems();
        $this->assertEquals(2, count($items));
        $this->assertEquals('summer', $items[0][0]->getTitle());
        $this->assertEquals('winter', $items[1][0]->getTitle());

        $this->assertEquals(2, $this->queryAnalyzer->getNumExecutedQueries());
        $executed = $this->queryAnalyzer->getExecutedQueries();

        // Different aliases separators according to Doctrine version
        if (version_compare(\Doctrine\ORM\Version::VERSION, '2.5', '<')) {
            $this->assertEquals('SELECT a0_.id AS id0, a0_.title AS title1, a0_.enabled AS enabled2, a0_.title AS title3 FROM Article a0_ WHERE a0_.title LIKE \'%er\' LIMIT 10 OFFSET 0', $executed[1]);
        } else {
            $this->assertEquals('SELECT a0_.id AS id_0, a0_.title AS title_1, a0_.enabled AS enabled_2, a0_.title AS title_3 FROM Article a0_ WHERE a0_.title LIKE \'%er\' LIMIT 10 OFFSET 0', $executed[1]);
        }
    }

    /**
     * @test
     */
    public function shouldNotWorkWithInitialPaginatorEventDispatcher()
    {
        $em = $this->getMockSqliteEntityManager();
        $this->populate($em);
        $_GET['filterParam'] = 'a.title';
        $_GET['filterValue'] = 'summer';
        $query = $this
            ->em
            ->createQuery('SELECT a FROM Test\Fixture\Entity\Article a')
        ;
        $query->setHint(UsesPaginator::HINT_FETCH_JOIN_COLLECTION, false);

        $p = new Paginator();
        $this->startQueryLog();
        $view = $p->paginate($query, 1, 10);
        $this->assertTrue($view instanceof SlidingPagination);

        $this->assertEquals(2, $this->queryAnalyzer->getNumExecutedQueries());
        $executed = $this->queryAnalyzer->getExecutedQueries();

        // Different aliases separators according to Doctrine version
        if (version_compare(\Doctrine\ORM\Version::VERSION, '2.5', '<')) {
            $this->assertEquals('SELECT a0_.id AS id0, a0_.title AS title1, a0_.enabled AS enabled2 FROM Article a0_ LIMIT 10 OFFSET 0', $executed[1]);
        } else {
            $this->assertEquals('SELECT a0_.id AS id_0, a0_.title AS title_1, a0_.enabled AS enabled_2 FROM Article a0_ LIMIT 10 OFFSET 0', $executed[1]);
        }
    }

    /**
     * @test
     */
    public function shouldNotExecuteExtraQueriesWhenCountIsZero()
    {
        $_GET['filterParam'] = 'a.title';
        $_GET['filterValue'] = 'asc';
        $query = $this
            ->getMockSqliteEntityManager()
            ->createQuery('SELECT a FROM Test\Fixture\Entity\Article a')
        ;

        $p = new Paginator();
        $this->startQueryLog();
        $view = $p->paginate($query, 1, 10);
        $this->assertTrue($view instanceof SlidingPagination);

        $this->assertEquals(2, $this->queryAnalyzer->getNumExecutedQueries());
    }

    /**
     * @test
     */
    public function shouldFilterWithEmptyParametersAndDefaults()
    {
        $em = $this->getMockSqliteEntityManager();
        $this->populate($em);

        $dispatcher = new EventDispatcher();
        $dispatcher->addSubscriber(new PaginationSubscriber());
        $dispatcher->addSubscriber(new Filtration());
        $p = new Paginator($dispatcher);

        $_GET['filterParam'] = '';
        $_GET['filterValue'] = 'summer';
        $this->startQueryLog();
        $query = $this->em->createQuery('SELECT a FROM Test\Fixture\Entity\Article a');
        $query->setHint(UsesPaginator::HINT_FETCH_JOIN_COLLECTION, false);
        $defaultFilterFields = 'a.title';
        $view = $p->paginate($query, 1, 10, compact(PaginatorInterface::DEFAULT_FILTER_FIELDS));
        $items = $view->getItems();
        $this->assertEquals(1, count($items));
        $this->assertEquals('summer', $items[0]->getTitle());
        $defaultFilterFields = 'a.id,a.title';
        $view = $p->paginate($query, 1, 10, compact(PaginatorInterface::DEFAULT_FILTER_FIELDS));
        $items = $view->getItems();
        $this->assertEquals(1, count($items));
        $this->assertEquals('summer', $items[0]->getTitle());
        $defaultFilterFields = array('a.id', 'a.title');
        $view = $p->paginate($query, 1, 10, compact(PaginatorInterface::DEFAULT_FILTER_FIELDS));
        $items = $view->getItems();
        $this->assertEquals(1, count($items));
        $this->assertEquals('summer', $items[0]->getTitle());
        $executed = $this->queryAnalyzer->getExecutedQueries();

        // Different aliases separators according to Doctrine version
        if (version_compare(\Doctrine\ORM\Version::VERSION, '2.5', '<')) {
            $this->assertEquals('SELECT a0_.id AS id0, a0_.title AS title1, a0_.enabled AS enabled2 FROM Article a0_ WHERE a0_.title LIKE \'summer\' LIMIT 10 OFFSET 0', $executed[1]);
            $this->assertEquals('SELECT a0_.id AS id0, a0_.title AS title1, a0_.enabled AS enabled2 FROM Article a0_ WHERE a0_.id LIKE \'summer\' OR a0_.title LIKE \'summer\' LIMIT 10 OFFSET 0', $executed[3]);
            $this->assertEquals('SELECT a0_.id AS id0, a0_.title AS title1, a0_.enabled AS enabled2 FROM Article a0_ WHERE a0_.id LIKE \'summer\' OR a0_.title LIKE \'summer\' LIMIT 10 OFFSET 0', $executed[5]);
        } else {
            $this->assertEquals('SELECT a0_.id AS id_0, a0_.title AS title_1, a0_.enabled AS enabled_2 FROM Article a0_ WHERE a0_.title LIKE \'summer\' LIMIT 10 OFFSET 0', $executed[1]);
            $this->assertEquals('SELECT a0_.id AS id_0, a0_.title AS title_1, a0_.enabled AS enabled_2 FROM Article a0_ WHERE a0_.id LIKE \'summer\' OR a0_.title LIKE \'summer\' LIMIT 10 OFFSET 0', $executed[3]);
            $this->assertEquals('SELECT a0_.id AS id_0, a0_.title AS title_1, a0_.enabled AS enabled_2 FROM Article a0_ WHERE a0_.id LIKE \'summer\' OR a0_.title LIKE \'summer\' LIMIT 10 OFFSET 0', $executed[5]);
        }
    }

    /**
     * @test
     */
    public function shouldNotFilterWithEmptyParametersAndDefaults()
    {
        $em = $this->getMockSqliteEntityManager();
        $this->populate($em);

        $dispatcher = new EventDispatcher();
        $dispatcher->addSubscriber(new PaginationSubscriber());
        $dispatcher->addSubscriber(new Filtration());
        $p = new Paginator($dispatcher);

        $_GET['filterParam'] = 'a.title';
        $_GET['filterValue'] = '';
        $this->startQueryLog();
        $query = $this->em->createQuery('SELECT a FROM Test\Fixture\Entity\Article a');
        $query->setHint(UsesPaginator::HINT_FETCH_JOIN_COLLECTION, false);
        $view = $p->paginate($query, 1, 10);
        $items = $view->getItems();
        $this->assertEquals(4, count($items));
        $_GET['filterParam'] = '';
        $_GET['filterValue'] = 'summer';
        $view = $p->paginate($query, 1, 10);
        $items = $view->getItems();
        $this->assertEquals(4, count($items));
        $_GET['filterParam'] = '';
        $_GET['filterValue'] = '';
        $view = $p->paginate($query, 1, 10);
        $items = $view->getItems();
        $this->assertEquals(4, count($items));
        $executed = $this->queryAnalyzer->getExecutedQueries();

        // Different aliases separators according to Doctrine version
        if (version_compare(\Doctrine\ORM\Version::VERSION, '2.5', '<')) {
            $this->assertEquals('SELECT a0_.id AS id0, a0_.title AS title1, a0_.enabled AS enabled2 FROM Article a0_ LIMIT 10 OFFSET 0', $executed[1]);
            $this->assertEquals('SELECT a0_.id AS id0, a0_.title AS title1, a0_.enabled AS enabled2 FROM Article a0_ LIMIT 10 OFFSET 0', $executed[3]);
            $this->assertEquals('SELECT a0_.id AS id0, a0_.title AS title1, a0_.enabled AS enabled2 FROM Article a0_ LIMIT 10 OFFSET 0', $executed[5]);
        } else {
            $this->assertEquals('SELECT a0_.id AS id_0, a0_.title AS title_1, a0_.enabled AS enabled_2 FROM Article a0_ LIMIT 10 OFFSET 0', $executed[1]);
            $this->assertEquals('SELECT a0_.id AS id_0, a0_.title AS title_1, a0_.enabled AS enabled_2 FROM Article a0_ LIMIT 10 OFFSET 0', $executed[3]);
            $this->assertEquals('SELECT a0_.id AS id_0, a0_.title AS title_1, a0_.enabled AS enabled_2 FROM Article a0_ LIMIT 10 OFFSET 0', $executed[5]);
        }
    }

    protected function getUsedEntityFixtures()
    {
        return array('Test\Fixture\Entity\Article');
    }

    private function populate($em)
    {
        $summer = new Article();
        $summer->setTitle('summer');
        $summer->setEnabled(true);

        $winter = new Article();
        $winter->setTitle('winter');
        $winter->setEnabled(true);

        $autumn = new Article();
        $autumn->setTitle('autumn');
        $autumn->setEnabled(false);

        $spring = new Article();
        $spring->setTitle('spring');
        $spring->setEnabled(false);

        $em->persist($summer);
        $em->persist($winter);
        $em->persist($autumn);
        $em->persist($spring);
        $em->flush();
    }

    private function populateNumeric($em)
    {
        $zero = new Article();
        $zero->setTitle('0');
        $zero->setEnabled(true);

        $one = new Article();
        $one->setTitle('1');
        $one->setEnabled(true);

        $lower = new Article();
        $lower->setTitle('123');
        $lower->setEnabled(false);

        $upper = new Article();
        $upper->setTitle('234');
        $upper->setEnabled(false);

        $em->persist($zero);
        $em->persist($one);
        $em->persist($lower);
        $em->persist($upper);
        $em->flush();
    }

    private function getApcEntityManager()
    {
        $config = new \Doctrine\ORM\Configuration();
        $config->setMetadataCacheImpl(new \Doctrine\Common\Cache\ApcCache());
        $config->setQueryCacheImpl(new \Doctrine\Common\Cache\ApcCache());
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
