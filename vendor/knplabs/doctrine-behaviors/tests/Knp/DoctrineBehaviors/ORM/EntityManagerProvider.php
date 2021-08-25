<?php

namespace tests\Knp\DoctrineBehaviors\ORM;

use Doctrine\ORM\Configuration;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Repository\DefaultRepositoryFactory;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Common\EventManager;
use Doctrine\ORM\Mapping\DefaultQuoteStrategy;

trait EntityManagerProvider
{
    private $em;

    abstract protected function getUsedEntityFixtures();

    /**
     * EntityManager mock object together with
     * annotation mapping driver and pdo_sqlite
     * database in memory
     *
     * @param  EventManager  $evm
     * @return EntityManager
     */
    protected function getEntityManager(EventManager $evm = null, Configuration $config = null, array $conn = [])
    {
        if (null !== $this->em) {
            return $this->em;
        }

        $conn = array_merge(array(
            'driver' => 'pdo_sqlite',
            'memory' => true,
        ), $conn);

        $config = is_null($config) ? $this->getAnnotatedConfig() : $config;
        $em = EntityManager::create($conn, $config, $evm ?: $this->getEventManager());

        $schema = array_map(function ($class) use ($em) {
            return $em->getClassMetadata($class);
        }, (array) $this->getUsedEntityFixtures());

        $schemaTool = new SchemaTool($em);
        $schemaTool->dropSchema($schema);
        $schemaTool->createSchema($schema);

        return $this->em = $em;
    }

    /**
     * EntityManager mock object together with
     * annotation mapping driver and engine given
     * by DB_ENGINE (pdo_mysql or pdo_pgsql)
     * database in memory
     *
     * @return \Doctrine\ORM\EntityManager
     */
    protected function getDBEngineEntityManager()
    {
        if (DB_ENGINE == "pgsql") {
            return $this->getEntityManager(
                null,
                null,
                [
                    'driver' => 'pdo_pgsql',
                    'host' => DB_HOST,
                    'dbname' => DB_NAME,
                    'user' => DB_USER,
                    'password' => DB_PASSWD
                ]
            );
        } else {
            return $this->getEntityManager(
                null,
                null,
                [
                    'driver' => 'pdo_mysql',
                    'host' => DB_HOST,
                    'dbname' => DB_NAME,
                    'user' => DB_USER,
                    'password' => DB_PASSWD
                ]
            );
        }
    }

    /**
     * Get annotation mapping configuration
     *
     * @return \Doctrine\ORM\Configuration
     */
    protected function getAnnotatedConfig()
    {
        // We need to mock every method except the ones which
        // handle the filters
        $configurationClass = 'Doctrine\ORM\Configuration';
        $refl = new \ReflectionClass($configurationClass);
        $methods = $refl->getMethods();

        $mockMethods = array();

        foreach ($methods as $method) {
            if (!in_array($method->name, ['addFilter', 'getFilterClassName', 'addCustomNumericFunction', 'getCustomNumericFunction'])) {
                $mockMethods[] = $method->name;
            }
        }

        $config = $this->getMock($configurationClass, $mockMethods);

        $config
            ->expects($this->once())
            ->method('getProxyDir')
            ->will($this->returnValue(TESTS_TEMP_DIR))
        ;

        $config
            ->expects($this->once())
            ->method('getProxyNamespace')
            ->will($this->returnValue('Proxy'))
        ;

        $config
            ->expects($this->once())
            ->method('getAutoGenerateProxyClasses')
            ->will($this->returnValue(true))
        ;

        $config
            ->expects($this->once())
            ->method('getClassMetadataFactoryName')
            ->will($this->returnValue('Doctrine\\ORM\\Mapping\\ClassMetadataFactory'))
        ;

        $mappingDriver = $this->getMetadataDriverImplementation();

        $config
            ->expects($this->any())
            ->method('getMetadataDriverImpl')
            ->will($this->returnValue($mappingDriver))
        ;

        $config
            ->expects($this->any())
            ->method('getDefaultRepositoryClassName')
            ->will($this->returnValue('Doctrine\\ORM\\EntityRepository'))
        ;

        if (class_exists('Doctrine\ORM\Mapping\DefaultQuoteStrategy')) {
            $config
                ->expects($this->any())
                ->method('getQuoteStrategy')
                ->will($this->returnValue(new DefaultQuoteStrategy))
            ;
        }

        if (class_exists('Doctrine\ORM\Repository\DefaultRepositoryFactory')) {
            $config
                ->expects($this->any())
                ->method('getRepositoryFactory')
                ->will($this->returnValue(new DefaultRepositoryFactory()))
            ;
        }

        $config
            ->expects($this->any())
            ->method('getDefaultQueryHints')
            ->will($this->returnValue([]))
        ;

        return $config;
    }

    /**
     * Creates default mapping driver
     *
     * @return \Doctrine\ORM\Mapping\Driver\Driver
     */
    protected function getMetadataDriverImplementation()
    {
        return new AnnotationDriver($_ENV['annotation_reader']);
    }

    /**
     * Build event manager
     *
     * @return EventManager
     */
    protected function getEventManager()
    {
        return new EventManager;
    }
}
