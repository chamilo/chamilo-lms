<?php

/*
 * This file is part of the xAPI package.
 *
 * (c) Christian Flothmann <christian.flothmann@xabbuh.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace XApi\Repository\Api\Test\Functional;

use PHPUnit\Framework\TestCase;
use Xabbuh\XApi\DataFixtures\StatementFixtures;
use Xabbuh\XApi\Model\Statement;
use Xabbuh\XApi\Model\StatementId;
use XApi\Repository\Api\StatementRepository;

/**
 * @author Christian Flothmann <christian.flothmann@xabbuh.de>
 */
abstract class StatementRepositoryTest extends TestCase
{
    const UUID_REGEXP = '/^[a-f0-9]{8}-[a-f0-9]{4}-[1-5][a-f0-9]{3}-[89ab][a-f0-9]{3}-[a-f0-9]{12}$/i';

    /**
     * @var StatementRepository
     */
    private $statementRepository;

    protected function setUp()
    {
        $this->statementRepository = $this->createStatementRepository();
        $this->cleanDatabase();
    }

    protected function tearDown()
    {
        $this->cleanDatabase();
    }

    /**
     * @expectedException \Xabbuh\XApi\Common\Exception\NotFoundException
     */
    public function testFetchingNonExistingStatementThrowsException()
    {
        $this->statementRepository->findStatementById(StatementId::fromString('12345678-1234-5678-8234-567812345678'));
    }

    /**
     * @expectedException \Xabbuh\XApi\Common\Exception\NotFoundException
     */
    public function testFetchingStatementAsVoidedStatementThrowsException()
    {
        $statement = StatementFixtures::getTypicalStatement()->withId(null);
        $statementId = $this->statementRepository->storeStatement($statement);

        $this->statementRepository->findVoidedStatementById($statementId);
    }

    /**
     * @dataProvider getStatementsWithoutId
     */
    public function testUuidIsGeneratedForNewStatementIfNotPresent(Statement $statement)
    {
        $statement = $statement->withId(null);
        $statementId = $this->statementRepository->storeStatement($statement);

        $this->assertNull($statement->getId());
        $this->assertRegExp(self::UUID_REGEXP, $statementId->getValue());
    }

    /**
     * @dataProvider getStatementsWithId
     */
    public function testUuidIsNotGeneratedForNewStatementIfPresent(Statement $statement)
    {
        $statementId = $this->statementRepository->storeStatement($statement);

        $this->assertEquals($statement->getId(), $statementId);
    }

    /**
     * @dataProvider getStatementsWithId
     */
    public function testCreatedStatementCanBeRetrievedByOriginalId(Statement $statement)
    {
        $this->statementRepository->storeStatement($statement);

        if ($statement->getVerb()->isVoidVerb()) {
            $fetchedStatement = $this->statementRepository->findVoidedStatementById($statement->getId());
        } else {
            $fetchedStatement = $this->statementRepository->findStatementById($statement->getId());
        }

        $this->assertTrue($statement->equals($fetchedStatement));
    }

    /**
     * @dataProvider getStatementsWithoutId
     */
    public function testCreatedStatementCanBeRetrievedByGeneratedId(Statement $statement)
    {
        $statement  =$statement->withId(null);
        $statementId = $this->statementRepository->storeStatement($statement);

        if ($statement->getVerb()->isVoidVerb()) {
            $fetchedStatement = $this->statementRepository->findVoidedStatementById($statementId);
        } else {
            $fetchedStatement = $this->statementRepository->findStatementById($statementId);
        }

        $this->assertNull($statement->getId());
        $this->assertTrue($statement->equals($fetchedStatement->withId(null)));
    }

    public function getStatementsWithId()
    {
        $fixtures = array();

        foreach (get_class_methods('Xabbuh\XApi\DataFixtures\StatementFixtures') as $method) {
            $statement = call_user_func(array('Xabbuh\XApi\DataFixtures\StatementFixtures', $method));

            if ($statement instanceof Statement) {
                $fixtures[$method] = array($statement->withId(StatementId::fromString(StatementFixtures::DEFAULT_STATEMENT_ID)));
            }
        }

        return $fixtures;
    }

    public function getStatementsWithoutId()
    {
        $fixtures = array();

        foreach (get_class_methods('Xabbuh\XApi\DataFixtures\StatementFixtures') as $method) {
            $statement = call_user_func(array('Xabbuh\XApi\DataFixtures\StatementFixtures', $method));

            if ($statement instanceof Statement) {
                $fixtures[$method] = array($statement->withId(null));
            }
        }

        return $fixtures;
    }

    /**
     * @expectedException \Xabbuh\XApi\Common\Exception\NotFoundException
     */
    public function testFetchingNonExistingVoidStatementThrowsException()
    {
        $this->statementRepository->findVoidedStatementById(StatementId::fromString('12345678-1234-5678-8234-567812345678'));
    }

    /**
     * @expectedException \Xabbuh\XApi\Common\Exception\NotFoundException
     */
    public function testFetchingVoidStatementAsStatementThrowsException()
    {
        $statement = StatementFixtures::getVoidingStatement()->withId(null);
        $statementId = $this->statementRepository->storeStatement($statement);

        $this->statementRepository->findStatementById($statementId);
    }

    public function testUuidIsGeneratedForNewVoidStatementIfNotPresent()
    {
        $statement = StatementFixtures::getVoidingStatement()->withId(null);
        $statementId = $this->statementRepository->storeStatement($statement);

        $this->assertNull($statement->getId());
        $this->assertRegExp(self::UUID_REGEXP, $statementId->getValue());
    }

    public function testUuidIsNotGeneratedForNewVoidStatementIfPresent()
    {
        $statement = StatementFixtures::getVoidingStatement();
        $statementId = $this->statementRepository->storeStatement($statement);

        $this->assertEquals($statement->getId(), $statementId);
    }

    public function testCreatedVoidStatementCanBeRetrievedByOriginalId()
    {
        $statement = StatementFixtures::getVoidingStatement();
        $this->statementRepository->storeStatement($statement);
        $fetchedStatement = $this->statementRepository->findVoidedStatementById($statement->getId());

        $this->assertTrue($statement->equals($fetchedStatement));
    }

    public function testCreatedVoidStatementCanBeRetrievedByGeneratedId()
    {
        $statement = StatementFixtures::getVoidingStatement()->withId(null);
        $statementId = $this->statementRepository->storeStatement($statement);
        $fetchedStatement = $this->statementRepository->findVoidedStatementById($statementId);

        $this->assertNull($statement->getId());
        $this->assertTrue($statement->equals($fetchedStatement->withId(null)));
    }

    abstract protected function createStatementRepository();

    abstract protected function cleanDatabase();
}
