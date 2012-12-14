<?php

/*
 * This file is a part of dflydev/doctrine-orm-service-provider.
 *
 * (c) Dragonfly Development Inc.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dflydev\Silex\Provider\DoctrineOrm;

use Silex\Application;

/**
 * DoctrineOrmServiceProvider Test.
 *
 * @author Beau Simensen <beau@dflydev.com>
 */
class DoctrineOrmServiceProviderTest extends \PHPUnit_Framework_TestCase
{
    protected function createMockDefaultAppAndDeps()
    {
        $app = new Application;

        $eventManager = $this->getMock('Doctrine\Common\EventManager');
        $connection = $this
            ->getMockBuilder('Doctrine\DBAL\Connection')
            ->disableOriginalConstructor()
            ->getMock();

        $connection
            ->expects($this->any())
            ->method('getEventManager')
            ->will($this->returnValue($eventManager));

        $app['dbs'] = new \Pimple(array(
            'default' => $connection,
        ));

        $app['dbs.event_manager'] = new \Pimple(array(
            'default' => $eventManager,
        ));

        return array($app, $connection, $eventManager);;
    }

    protected function createMockDefaultApp()
    {
        list ($app, $connection, $eventManager) = $this->createMockDefaultAppAndDeps();

        return $app;
    }

    /**
     * Test registration
     */
    public function testRegister()
    {
        $app = $this->createMockDefaultApp();

        $app->register(new DoctrineOrmServiceProvider);

        $this->assertEquals($app['orm.em'], $app['orm.ems']['default']);
        $this->assertInstanceOf('Doctrine\Common\Cache\ArrayCache', $app['orm.em.config']->getQueryCacheImpl());
        $this->assertInstanceOf('Doctrine\Common\Cache\ArrayCache', $app['orm.em.config']->getResultCacheImpl());
        $this->assertInstanceOf('Doctrine\Common\Cache\ArrayCache', $app['orm.em.config']->getMetadataCacheImpl());
        $this->assertInstanceOf('Doctrine\Common\Persistence\Mapping\Driver\MappingDriverChain', $app['orm.em.config']->getMetadataDriverImpl());
    }
}
