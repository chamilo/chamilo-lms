<?php

/*
 * This file is part of the xAPI package.
 *
 * (c) Christian Flothmann <christian.flothmann@xabbuh.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace XApi\Repository\ORM\Tests\Functional;

use Doctrine\Common\Persistence\Mapping\Driver\SymfonyFileLocator;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\Driver\XmlDriver;
use Doctrine\ORM\Tools\SchemaTool;
use XApi\Repository\Doctrine\Test\Functional\StatementRepositoryTest as BaseStatementRepositoryTest;

class StatementRepositoryTest extends BaseStatementRepositoryTest
{
    protected function createObjectManager()
    {
        $config = new Configuration();
        $config->setProxyDir(__DIR__.'/../proxies');
        $config->setProxyNamespace('Proxy');
        $fileLocator = new SymfonyFileLocator(
            array(__DIR__.'/../../metadata' => 'XApi\Repository\Doctrine\Mapping'),
            '.orm.xml'
        );
        $driver = new XmlDriver($fileLocator);
        $config->setMetadataDriverImpl($driver);

        return EntityManager::create(array('driver' => 'pdo_sqlite', 'path' => __DIR__.'/../data/db.sqlite'), $config);
    }

    protected function getStatementClassName()
    {
        return 'XApi\Repository\Doctrine\Mapping\Statement';
    }

    protected function cleanDatabase()
    {
        $metadata = $this->objectManager->getMetadataFactory()->getAllMetadata();
        $tool = new SchemaTool($this->objectManager);
        $tool->dropDatabase();
        $tool->createSchema($metadata);

        parent::cleanDatabase();
    }
}
