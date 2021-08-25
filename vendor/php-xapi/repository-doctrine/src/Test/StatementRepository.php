<?php

/*
 * This file is part of the xAPI package.
 *
 * (c) Christian Flothmann <christian.flothmann@xabbuh.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace XApi\Repository\Doctrine\Test;

use Doctrine\Common\Persistence\ObjectManager as LegacyObjectManager;
use Doctrine\Persistence\ObjectManager;
use Xabbuh\XApi\Model\Actor;
use Xabbuh\XApi\Model\Statement;
use Xabbuh\XApi\Model\StatementId;
use Xabbuh\XApi\Model\StatementsFilter;
use XApi\Repository\Api\StatementRepositoryInterface;

/**
 * Statement repository clearing the object manager between read and write operations.
 *
 * @author Christian Flothmann <christian.flothmann@xabbuh.de>
 */
final class StatementRepository implements StatementRepositoryInterface
{
    private $repository;
    private $objectManager;

    public function __construct(StatementRepositoryInterface $repository, $objectManager)
    {
        if (!$objectManager instanceof ObjectManager && !$objectManager instanceof LegacyObjectManager) {
            throw new \TypeError(sprintf('The second argument of %s() must be an instance of %s (%s given).', __METHOD__, ObjectManager::class, is_object($objectManager) ? get_class($objectManager) : gettype($objectManager)));
        }

        $this->repository = $repository;
        $this->objectManager = $objectManager;
    }

    /**
     * {@inheritdoc}
     */
    public function findStatementById(StatementId $statementId, Actor $authority = null)
    {
        $statement = $this->repository->findStatementById($statementId, $authority);
        $this->objectManager->clear();

        return $statement;
    }

    /**
     * {@inheritdoc}
     */
    public function findVoidedStatementById(StatementId $voidedStatementId, Actor $authority = null)
    {
        $statement = $this->repository->findVoidedStatementById($voidedStatementId, $authority);
        $this->objectManager->clear();

        return $statement;
    }

    /**
     * {@inheritdoc}
     */
    public function findStatementsBy(StatementsFilter $criteria, Actor $authority = null)
    {
        $statements = $this->repository->findStatementsBy($criteria, $authority);
        $this->objectManager->clear();

        return $statements;
    }

    /**
     * {@inheritdoc}
     */
    public function storeStatement(Statement $statement, $flush = true)
    {
        $statementId = $this->repository->storeStatement($statement);
        $this->objectManager->clear();

        return $statementId;
    }
}
