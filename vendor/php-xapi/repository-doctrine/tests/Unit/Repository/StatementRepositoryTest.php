<?php

/*
 * This file is part of the xAPI package.
 *
 * (c) Christian Flothmann <christian.flothmann@xabbuh.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace XApi\Repository\Doctrine\Tests\Unit\Repository;

use PHPUnit\Framework\TestCase;
use Rhumsaa\Uuid\Uuid as RhumsaUuid;
use Xabbuh\XApi\DataFixtures\StatementFixtures;
use Xabbuh\XApi\DataFixtures\VerbFixtures;
use Xabbuh\XApi\Model\StatementId;
use Xabbuh\XApi\Model\StatementsFilter;
use Xabbuh\XApi\Model\Uuid as ModelUuid;
use XApi\Repository\Doctrine\Mapping\Statement as MappedStatement;
use XApi\Repository\Doctrine\Repository\StatementRepository;

/**
 * @author Christian Flothmann <christian.flothmann@xabbuh.de>
 */
class StatementRepositoryTest extends TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\XApi\Repository\Doctrine\Repository\Mapping\StatementRepository
     */
    private $mappedStatementRepository;

    /**
     * @var StatementRepository
     */
    private $statementRepository;

    protected function setUp()
    {
        $this->mappedStatementRepository = $this->createMappedStatementRepositoryMock();
        $this->statementRepository = new StatementRepository($this->mappedStatementRepository);
    }

    public function testFindStatementById()
    {
        if (class_exists('Xabbuh\XApi\Model\Uuid')) {
            $statementId = StatementId::fromUuid(ModelUuid::uuid4());
        } else {
            $statementId = StatementId::fromUuid(RhumsaUuid::uuid4());
        }

        $this
            ->mappedStatementRepository
            ->expects($this->once())
            ->method('findStatement')
            ->with(array('id' => $statementId->getValue()))
            ->will($this->returnValue(MappedStatement::fromModel(StatementFixtures::getMinimalStatement())));

        $this->statementRepository->findStatementById($statementId);
    }

    public function testFindStatementsByCriteria()
    {
        $verb = VerbFixtures::getTypicalVerb();

        $this
            ->mappedStatementRepository
            ->expects($this->once())
            ->method('findStatements')
            ->with($this->equalTo(array('verb' => $verb->getId()->getValue())))
            ->will($this->returnValue(array()));

        $filter = new StatementsFilter();
        $filter->byVerb($verb);
        $this->statementRepository->findStatementsBy($filter);
    }

    public function testSave()
    {
        $statement = StatementFixtures::getMinimalStatement();
        $this
            ->mappedStatementRepository
            ->expects($this->once())
            ->method('storeStatement')
            ->with(
                $this->callback(function (MappedStatement $mappedStatement) use ($statement) {
                    $expected = MappedStatement::fromModel($statement);
                    $actual = clone $mappedStatement;
                    $actual->stored = null;

                    return $expected == $actual;
                }),
                true
            );

        $this->statementRepository->storeStatement($statement);
    }

    public function testSaveWithoutFlush()
    {
        $statement = StatementFixtures::getMinimalStatement();
        $this
            ->mappedStatementRepository
            ->expects($this->once())
            ->method('storeStatement')
            ->with(
                $this->callback(function (MappedStatement $mappedStatement) use ($statement) {
                    $expected = MappedStatement::fromModel($statement);
                    $actual = clone $mappedStatement;
                    $actual->stored = null;

                    return $expected == $actual;
                }),
                false
            );

        $this->statementRepository->storeStatement($statement, false);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\XApi\Repository\Doctrine\Repository\Mapping\StatementRepository
     */
    protected function createMappedStatementRepositoryMock()
    {
        return $this
            ->getMockBuilder('\XApi\Repository\Doctrine\Repository\Mapping\StatementRepository')
            ->getMock();
    }
}
