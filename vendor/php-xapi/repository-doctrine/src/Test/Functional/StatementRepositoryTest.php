<?php

/*
 * This file is part of the xAPI package.
 *
 * (c) Christian Flothmann <christian.flothmann@xabbuh.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace XApi\Repository\Doctrine\Test\Functional;

use Doctrine\Persistence\ObjectManager;
use XApi\Repository\Api\Test\Functional\StatementRepositoryTest as BaseStatementRepositoryTest;
use XApi\Repository\Doctrine\Repository\Mapping\StatementRepository as MappedStatementRepository;
use XApi\Repository\Doctrine\Repository\StatementRepository;
use XApi\Repository\Doctrine\Test\StatementRepository as FreshStatementRepository;

/**
 * @author Christian Flothmann <christian.flothmann@xabbuh.de>
 */
abstract class StatementRepositoryTest extends BaseStatementRepositoryTest
{
    /**
     * @var ObjectManager
     */
    protected $objectManager;

    /**
     * @var MappedStatementRepository
     */
    protected $repository;

    protected function setUp()
    {
        $this->objectManager = $this->createObjectManager();
        $this->repository = $this->createRepository();

        parent::setUp();
    }

    protected function createStatementRepository()
    {
        return new FreshStatementRepository(new StatementRepository($this->repository), $this->objectManager);
    }

    protected function cleanDatabase()
    {
        foreach ($this->repository->findStatements(array()) as $statement) {
            $this->objectManager->remove($statement);
        }

        $this->objectManager->flush();
    }

    /**
     * @return ObjectManager
     */
    abstract protected function createObjectManager();

    /**
     * @return string
     */
    abstract protected function getStatementClassName();

    private function createRepository()
    {
        return $this->objectManager->getRepository($this->getStatementClassName());
    }
}
