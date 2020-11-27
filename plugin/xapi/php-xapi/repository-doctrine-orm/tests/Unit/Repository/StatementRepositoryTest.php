<?php

/*
 * This file is part of the xAPI package.
 *
 * (c) Christian Flothmann <christian.flothmann@xabbuh.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace XApi\Repository\ORM\Tests\Unit\Repository;

use XApi\Repository\Doctrine\Test\Unit\Repository\Mapping\StatementRepositoryTest as BaseStatementRepositoryTest;
use XApi\Repository\ORM\StatementRepository;

class StatementRepositoryTest extends BaseStatementRepositoryTest
{
    protected function getObjectManagerClass()
    {
        return 'Doctrine\ORM\EntityManager';
    }

    protected function getUnitOfWorkClass()
    {
        return 'Doctrine\ORM\UnitOfWork';
    }

    protected function getClassMetadataClass()
    {
        return 'Doctrine\ORM\Mapping\ClassMetadata';
    }

    protected function createMappedStatementRepository($objectManager, $unitOfWork, $classMetadata)
    {
        return new StatementRepository($objectManager, $classMetadata);
    }
}
