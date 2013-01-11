<?php

/*
 * This file is a part of dflydev/doctrine-orm-service-provider.
 *
 * (c) Dragonfly Development Inc.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dflydev\Pimple\Provider\DoctrineOrm;

/**
 * DoctrineOrmServiceProvider Test.
 *
 * @author Beau Simensen <beau@dflydev.com>
 */
class DoctrineOrmServiceProviderTest extends \PHPUnit_Framework_TestCase
{
    protected function createMockDefaultAppAndDeps()
    {
        $app = new \Pimple;

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
     * Test registration (test expected class for default implementations)
     */
    public function testRegisterDefaultImplementations()
    {
        $app = $this->createMockDefaultApp();

        $doctrineOrmServiceProvider = new DoctrineOrmServiceProvider;
        $doctrineOrmServiceProvider->register($app);

        $this->assertEquals($app['orm.em'], $app['orm.ems']['default']);
        $this->assertInstanceOf('Doctrine\Common\Cache\ArrayCache', $app['orm.em.config']->getQueryCacheImpl());
        $this->assertInstanceOf('Doctrine\Common\Cache\ArrayCache', $app['orm.em.config']->getResultCacheImpl());
        $this->assertInstanceOf('Doctrine\Common\Cache\ArrayCache', $app['orm.em.config']->getMetadataCacheImpl());
        $this->assertInstanceOf('Doctrine\Common\Persistence\Mapping\Driver\MappingDriverChain', $app['orm.em.config']->getMetadataDriverImpl());
    }

    /**
     * Test registration (test equality for defined implementations)
     */
    public function testRegisterDefinedImplementations()
    {
        $app = $this->createMockDefaultApp();

        $queryCache = $this->getMock('Doctrine\Common\Cache\ArrayCache');
        $resultCache = $this->getMock('Doctrine\Common\Cache\ArrayCache');
        $metadataCache = $this->getMock('Doctrine\Common\Cache\ArrayCache');

        $mappingDriverChain = $this->getMock('Doctrine\Common\Persistence\Mapping\Driver\MappingDriverChain');

        $app['orm.cache.instances.default.query'] = $queryCache;
        $app['orm.cache.instances.default.result'] = $resultCache;
        $app['orm.cache.instances.default.metadata'] = $metadataCache;

        $app['orm.mapping_driver_chain.instances.default'] = $mappingDriverChain;

        $doctrineOrmServiceProvider = new DoctrineOrmServiceProvider;
        $doctrineOrmServiceProvider->register($app);

        $this->assertEquals($app['orm.em'], $app['orm.ems']['default']);
        $this->assertEquals($queryCache, $app['orm.em.config']->getQueryCacheImpl());
        $this->assertEquals($resultCache, $app['orm.em.config']->getResultCacheImpl());
        $this->assertEquals($metadataCache, $app['orm.em.config']->getMetadataCacheImpl());
        $this->assertEquals($mappingDriverChain, $app['orm.em.config']->getMetadataDriverImpl());
    }

    /**
     * Test proxy configuration (defaults)
     */
    public function testProxyConfigurationDefaults()
    {
        $app = $this->createMockDefaultApp();

        $doctrineOrmServiceProvider = new DoctrineOrmServiceProvider;
        $doctrineOrmServiceProvider->register($app);

        $this->assertContains('/../../../../../../../cache/doctrine/proxies', $app['orm.em.config']->getProxyDir());
        $this->assertEquals('DoctrineProxy', $app['orm.em.config']->getProxyNamespace());
        $this->assertTrue($app['orm.em.config']->getAutoGenerateProxyClasses());
    }

    /**
     * Test proxy configuration (defined)
     */
    public function testProxyConfigurationDefined()
    {
        $app = $this->createMockDefaultApp();

        $doctrineOrmServiceProvider = new DoctrineOrmServiceProvider;
        $doctrineOrmServiceProvider->register($app);

        $app['orm.proxies_dir'] = '/path/to/proxies';
        $app['orm.proxies_namespace'] = 'TestDoctrineOrmProxiesNamespace';
        $app['orm.auto_generate_proxies'] = false;

        $this->assertEquals('/path/to/proxies', $app['orm.em.config']->getProxyDir());
        $this->assertEquals('TestDoctrineOrmProxiesNamespace', $app['orm.em.config']->getProxyNamespace());
        $this->assertFalse($app['orm.em.config']->getAutoGenerateProxyClasses());
    }

    /**
     * Test Driver Chain locator
     */
    public function testMappingDriverChainLocator()
    {
        $app = $this->createMockDefaultApp();

        $doctrineOrmServiceProvider = new DoctrineOrmServiceProvider;
        $doctrineOrmServiceProvider->register($app);

        $default = $app['orm.mapping_driver_chain.locator']();
        $this->assertEquals($default, $app['orm.mapping_driver_chain.locator']('default'));
        $this->assertEquals($default, $app['orm.em.config']->getMetadataDriverImpl());
    }

    /**
     * Test adding a mapping driver (use default entity manager)
     */
    public function testAddMappingDriverDefault()
    {
        $app = $this->createMockDefaultApp();

        $mappingDriver = $this->getMock('Doctrine\Common\Persistence\Mapping\Driver\MappingDriver');

        $mappingDriverChain = $this->getMock('Doctrine\Common\Persistence\Mapping\Driver\MappingDriverChain');
        $mappingDriverChain
            ->expects($this->once())
            ->method('addDriver')
            ->with($mappingDriver, 'Test\Namespace');

        $app['orm.mapping_driver_chain.instances.default'] = $mappingDriverChain;

        $doctrineOrmServiceProvider = new DoctrineOrmServiceProvider;
        $doctrineOrmServiceProvider->register($app);

        $app['orm.add_mapping_driver']($mappingDriver, 'Test\Namespace');
    }

    /**
     * Test adding a mapping driver (specify default entity manager by name)
     */
    public function testAddMappingDriverNamedEntityManager()
    {
        $app = $this->createMockDefaultApp();

        $mappingDriver = $this->getMock('Doctrine\Common\Persistence\Mapping\Driver\MappingDriver');

        $mappingDriverChain = $this->getMock('Doctrine\Common\Persistence\Mapping\Driver\MappingDriverChain');
        $mappingDriverChain
            ->expects($this->once())
            ->method('addDriver')
            ->with($mappingDriver, 'Test\Namespace');

        $app['orm.mapping_driver_chain.instances.default'] = $mappingDriverChain;

        $doctrineOrmServiceProvider = new DoctrineOrmServiceProvider;
        $doctrineOrmServiceProvider->register($app);

        $app['orm.add_mapping_driver']($mappingDriver, 'Test\Namespace');
    }

    /**
     * Test specifying an invalid cache type (just named)
     */
    public function testInvalidCacheTypeNamed()
    {
        $app = $this->createMockDefaultApp();

        $doctrineOrmServiceProvider = new DoctrineOrmServiceProvider;
        $doctrineOrmServiceProvider->register($app);

        $app['orm.em.options'] = array(
            'query_cache' => 'INVALID',
        );

        try {
            $app['orm.em'];

            $this->fail('Expected invalid query cache driver exception');
        } catch (\RuntimeException $e) {
            $this->assertEquals("Unsupported cache type 'INVALID' specified", $e->getMessage());
        }
    }

    /**
     * Test specifying an invalid cache type (driver as option)
     */
    public function testInvalidCacheTypeDriverAsOption()
    {
        $app = $this->createMockDefaultApp();

        $doctrineOrmServiceProvider = new DoctrineOrmServiceProvider;
        $doctrineOrmServiceProvider->register($app);

        $app['orm.em.options'] = array(
            'query_cache' => array(
                'driver' => 'INVALID',
            ),
        );

        try {
            $app['orm.em'];

            $this->fail('Expected invalid query cache driver exception');
        } catch (\RuntimeException $e) {
            $this->assertEquals("Unsupported cache type 'INVALID' specified", $e->getMessage());
        }
    }

    /**
     * Test orm.em_name_from_param_key ()
     */
    public function testNameFromParamKey()
    {
        $app = $this->createMockDefaultApp();

        $app['my.baz'] = 'baz';

        $doctrineOrmServiceProvider = new DoctrineOrmServiceProvider;
        $doctrineOrmServiceProvider->register($app);

        $app['orm.ems.default'] = 'foo';

        $this->assertEquals('foo', $app['orm.ems.default']);
        $this->assertEquals('foo', $app['orm.em_name_from_param_key']('my.bar'));
        $this->assertEquals('baz', $app['orm.em_name_from_param_key']('my.baz'));
    }
}
