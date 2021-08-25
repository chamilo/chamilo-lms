<?php

/*
 * This file is part of the xAPI package.
 *
 * (c) Christian Flothmann <christian.flothmann@xabbuh.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace XApi\Repository\Doctrine\Repository;

use Rhumsaa\Uuid\Uuid as RhumsaaUuid;
use Xabbuh\XApi\Common\Exception\NotFoundException;
use Xabbuh\XApi\Model\Actor;
use Xabbuh\XApi\Model\Statement;
use Xabbuh\XApi\Model\StatementId;
use Xabbuh\XApi\Model\StatementsFilter;
use Xabbuh\XApi\Model\Uuid as ModelUuid;
use XApi\Repository\Api\StatementRepositoryInterface;
use XApi\Repository\Doctrine\Mapping\Statement as MappedStatement;
use XApi\Repository\Doctrine\Repository\Mapping\StatementRepository as MappedStatementRepository;

/**
 * Doctrine based {@link Statement} repository.
 *
 * @author Christian Flothmann <christian.flothmann@xabbuh.de>
 */
final class StatementRepository implements StatementRepositoryInterface
{
    private $repository;

    public function __construct(MappedStatementRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * {@inheritdoc}
     */
    public function findStatementById(StatementId $statementId, Actor $authority = null)
    {
        $criteria = array('id' => $statementId->getValue());

        if (null !== $authority) {
            $criteria['authority'] = $authority;
        }

        $mappedStatement = $this->repository->findStatement($criteria);

        if (null === $mappedStatement) {
            throw new NotFoundException('No statements could be found matching the given criteria.');
        }

        $statement = $mappedStatement->getModel();

        if ($statement->isVoidStatement()) {
            throw new NotFoundException('The stored statement is a voiding statement.');
        }

        return $statement;
    }

    /**
     * {@inheritdoc}
     */
    public function findVoidedStatementById(StatementId $voidedStatementId, Actor $authority = null)
    {
        $criteria = array('id' => $voidedStatementId->getValue());

        if (null !== $authority) {
            $criteria['authority'] = $authority;
        }

        $mappedStatement = $this->repository->findStatement($criteria);

        if (null === $mappedStatement) {
            throw new NotFoundException('No voided statements could be found matching the given criteria.');
        }

        $statement = $mappedStatement->getModel();

        if (!$statement->isVoidStatement()) {
            throw new NotFoundException('The stored statement is no voiding statement.');
        }

        return $statement;
    }

    /**
     * {@inheritdoc}
     */
    public function findStatementsBy(StatementsFilter $criteria, Actor $authority = null)
    {
        $criteria = $criteria->getFilter();

        if (null !== $authority) {
            $criteria['authority'] = $authority;
        }

        $mappedStatements = $this->repository->findStatements($criteria);
        $statements = array();

        foreach ($mappedStatements as $mappedStatement) {
            $statements[] = $mappedStatement->getModel();
        }

        return $statements;
    }

    /**
     * {@inheritdoc}
     */
    public function storeStatement(Statement $statement, $flush = true)
    {
        if (null === $statement->getId()) {
            if (class_exists('Xabbuh\XApi\Model\Uuid')) {
                $uuid = ModelUuid::uuid4();
            } else {
                $uuid = RhumsaaUuid::uuid4();
            }

            $statement = $statement->withId(StatementId::fromUuid($uuid));
        }

        $mappedStatement = MappedStatement::fromModel($statement);
        $mappedStatement->stored = time();

        $this->repository->storeStatement($mappedStatement, $flush);

        return $statement->getId();
    }
}
