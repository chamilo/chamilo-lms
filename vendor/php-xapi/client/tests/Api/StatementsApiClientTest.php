<?php

/*
 * This file is part of the xAPI package.
 *
 * (c) Christian Flothmann <christian.flothmann@xabbuh.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Xabbuh\XApi\Client\Tests\Api;

use Xabbuh\XApi\Client\Api\StatementsApiClient;
use Xabbuh\XApi\Common\Exception\NotFoundException;
use Xabbuh\XApi\DataFixtures\StatementFixtures;
use Xabbuh\XApi\Model\Agent;
use Xabbuh\XApi\Model\InverseFunctionalIdentifier;
use Xabbuh\XApi\Model\IRI;
use Xabbuh\XApi\Model\IRL;
use Xabbuh\XApi\Model\Statement;
use Xabbuh\XApi\Model\StatementId;
use Xabbuh\XApi\Model\StatementReference;
use Xabbuh\XApi\Model\StatementResult;
use Xabbuh\XApi\Model\StatementsFilter;
use Xabbuh\XApi\Model\Verb;
use Xabbuh\XApi\Serializer\Symfony\ActorSerializer;
use Xabbuh\XApi\Serializer\Symfony\StatementResultSerializer;
use Xabbuh\XApi\Serializer\Symfony\StatementSerializer;

/**
 * @author Christian Flothmann <christian.flothmann@xabbuh.de>
 */
class StatementsApiClientTest extends ApiClientTest
{
    /**
     * @var StatementsApiClient
     */
    private $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = new StatementsApiClient(
            $this->requestHandler,
            '1.0.1',
            new StatementSerializer($this->serializer),
            new StatementResultSerializer($this->serializer),
            new ActorSerializer($this->serializer)
        );
    }

    public function testStoreStatement()
    {
        $statementId = '12345678-1234-5678-1234-567812345678';
        $statement = $this->createStatement();
        $this->validateStoreApiCall(
            'post',
            'statements',
            array(),
            200,
            '["'.$statementId.'"]',
            $this->createStatement()
        );
        $returnedStatement = $this->client->storeStatement($statement);
        $expectedStatement = $this->createStatement($statementId);

        $this->assertEquals($expectedStatement, $returnedStatement);
    }

    public function testStoreStatementWithId()
    {
        $statementId = '12345678-1234-5678-1234-567812345678';
        $statement = $this->createStatement($statementId);
        $this->validateStoreApiCall(
            'put',
            'statements',
            array('statementId' => $statementId),
            204,
            '["'.$statementId.'"]',
            $statement
        );

        $this->assertEquals($statement, $this->client->storeStatement($statement));
    }

    public function testStoreStatementWithIdEnsureThatTheIdIsNotOverwritten()
    {
        $statementId = '12345678-1234-5678-1234-567812345678';
        $statement = $this->createStatement($statementId);
        $this->validateStoreApiCall(
            'put',
            'statements',
            array('statementId' => $statementId),
            204,
            '',
            $statement
        );
        $storedStatement = $this->client->storeStatement($statement);

        $this->assertEquals($statementId, $storedStatement->getId()->getValue());
    }

    public function testStoreStatements()
    {
        $statementId1 = '12345678-1234-5678-1234-567812345678';
        $statementId2 = '12345678-1234-5678-1234-567812345679';
        $statement1 = $this->createStatement();
        $statement2 = $this->createStatement();
        $this->validateStoreApiCall(
            'post',
            'statements',
            array(),
            '200',
            '["'.$statementId1.'","'.$statementId2.'"]',
            array($this->createStatement(), $this->createStatement())
        );
        $statements = $this->client->storeStatements(array($statement1, $statement2));
        $expectedStatement1 = $this->createStatement($statementId1);
        $expectedStatement2 = $this->createStatement($statementId2);
        $expectedStatements = array($expectedStatement1, $expectedStatement2);

        $this->assertNotContains($statements[0], array($statement1, $statement2));
        $this->assertNotContains($statements[1], array($statement1, $statement2));
        $this->assertEquals($expectedStatements, $statements);
        $this->assertEquals($statementId1, $statements[0]->getId()->getValue());
        $this->assertEquals($statementId2, $statements[1]->getId()->getValue());
    }

    public function testStoreStatementsWithNonStatementObject()
    {
        $this->expectException(\InvalidArgumentException::class);

        $statement1 = $this->createStatement();
        $statement2 = $this->createStatement();

        $this->client->storeStatements(array($statement1, new \stdClass(), $statement2));
    }

    public function testStoreStatementsWithNonObject()
    {
        $this->expectException(\InvalidArgumentException::class);

        $statement1 = $this->createStatement();
        $statement2 = $this->createStatement();

        $this->client->storeStatements(array($statement1, 'foo', $statement2));
    }

    public function testStoreStatementsWithId()
    {
        $this->expectException(\InvalidArgumentException::class);

        $statement1 = $this->createStatement();
        $statement2 = $this->createStatement('12345678-1234-5678-1234-567812345679');

        $this->client->storeStatements(array($statement1, $statement2));
    }

    public function testVoidStatement()
    {
        $voidedStatementId = '12345678-1234-5678-1234-567812345679';
        $voidingStatementId = '12345678-1234-5678-1234-567812345678';
        $agent = new Agent(InverseFunctionalIdentifier::withMbox(IRI::fromString('mailto:john.doe@example.com')));
        $statementReference = new StatementReference(StatementId::fromString($voidedStatementId));
        $voidingStatement = new Statement(null, $agent, Verb::createVoidVerb(), $statementReference);
        $voidedStatement = $this->createStatement($voidedStatementId);
        $this->validateStoreApiCall(
            'post',
            'statements',
            array(),
            200,
            '["'.$voidingStatementId.'"]',
            $voidingStatement
        );
        $returnedVoidingStatement = $this->client->voidStatement($voidedStatement, $agent);
        $expectedVoidingStatement = new Statement(
            StatementId::fromString($voidingStatementId),
            $agent,
            Verb::createVoidVerb(),
            $statementReference
        );

        $this->assertEquals($expectedVoidingStatement, $returnedVoidingStatement);
    }

    public function testGetStatement()
    {
        $statementId = '12345678-1234-5678-1234-567812345678';
        $statement = $this->createStatement();
        $this->validateRetrieveApiCall(
            'get',
            'statements',
            array('statementId' => $statementId, 'attachments' => 'true'),
            200,
            'Statement',
            $statement
        );

        $this->client->getStatement(StatementId::fromString($statementId));
    }

    public function testGetStatementWithNotExistingStatement()
    {
        $this->expectException(NotFoundException::class);

        $statementId = '12345678-1234-5678-1234-567812345678';
        $this->validateRetrieveApiCall(
            'get',
            'statements',
            array('statementId' => $statementId, 'attachments' => 'true'),
            404,
            'Statement',
            'There is no statement associated with this id'
        );

        $this->client->getStatement(StatementId::fromString($statementId));
    }

    public function testGetVoidedStatement()
    {
        $statementId = '12345678-1234-5678-1234-567812345678';
        $statement = $this->createStatement();
        $this->validateRetrieveApiCall(
            'get',
            'statements',
            array('voidedStatementId' => $statementId, 'attachments' => 'true'),
            200,
            'Statement',
            $statement
        );

        $this->client->getVoidedStatement(StatementId::fromString($statementId));
    }

    public function testGetVoidedStatementWithNotExistingStatement()
    {
        $this->expectException(NotFoundException::class);

        $statementId = '12345678-1234-5678-1234-567812345678';
        $this->validateRetrieveApiCall(
            'get',
            'statements',
            array('voidedStatementId' => $statementId, 'attachments' => 'true'),
            404,
            'Statement',
            'There is no statement associated with this id'
        );

        $this->client->getVoidedStatement(StatementId::fromString($statementId));
    }

    public function testGetStatements()
    {
        $statementResult = $this->createStatementResult();
        $this->validateRetrieveApiCall(
            'get',
            'statements',
            array(),
            200,
            'StatementResult',
            $statementResult
        );

        $this->assertEquals($statementResult, $this->client->getStatements());
    }

    public function testGetStatementsWithStatementsFilter()
    {
        $filter = new StatementsFilter();
        $filter->limit(10)->ascending();
        $statementResult = $this->createStatementResult();
        $this->validateRetrieveApiCall(
            'get',
            'statements',
            array('limit' => 10, 'ascending' => 'true'),
            200,
            'StatementResult',
            $statementResult
        );

        $this->assertEquals($statementResult, $this->client->getStatements($filter));
    }

    public function testGetStatementsWithAgentInStatementsFilter()
    {
        // {"mbox":"mailto:alice@example.com","objectType":"Agent"}
        $filter = new StatementsFilter();
        $agent = new Agent(InverseFunctionalIdentifier::withMbox(IRI::fromString('mailto:alice@example.com')));
        $filter->byActor($agent);
        $statementResult = $this->createStatementResult();
        $agentJson = '{"mbox":"mailto:alice@example.com","objectType":"Agent"}';
        $this->serializer
            ->expects($this->once())
            ->method('serialize')
            ->with($agent, 'json')
            ->willReturn($agentJson);
        $this->validateRetrieveApiCall(
            'get',
            'statements',
            array('agent' => $agentJson),
            200,
            'StatementResult',
            $statementResult
        );

        $this->assertEquals($statementResult, $this->client->getStatements($filter));
    }

    public function testGetStatementsWithVerbInStatementsFilter()
    {
        $filter = new StatementsFilter();
        $verb = new Verb(IRI::fromString('http://adlnet.gov/expapi/verbs/attended'));
        $filter->byVerb($verb);
        $statementResult = $this->createStatementResult();
        $this->validateRetrieveApiCall(
            'get',
            'statements',
            array('verb' => 'http://adlnet.gov/expapi/verbs/attended'),
            200,
            'StatementResult',
            $statementResult
        );

        $this->assertEquals($statementResult, $this->client->getStatements($filter));
    }

    public function testGetNextStatements()
    {
        $moreUrl = '/xapi/statements/more/b381d8eca64a61a42c7b9b4ecc2fabb6';
        $previousStatementResult = new StatementResult(array(), IRL::fromString($moreUrl));
        $this->validateRetrieveApiCall(
            'get',
            $moreUrl,
            array(),
            200,
            'StatementResult',
            $previousStatementResult
        );

        $statementResult = $this->client->getNextStatements($previousStatementResult);

        $this->assertInstanceOf(StatementResult::class, $statementResult);
    }

    /**
     * @param int $id
     *
     * @return Statement
     */
    private function createStatement($id = null)
    {
        $statement = StatementFixtures::getMinimalStatement($id);

        if (null === $id) {
            $statement = $statement->withId(null);
        }

        return $statement;
    }

    /**
     * @return StatementResult
     */
    private function createStatementResult()
    {
        return new StatementResult(array());
    }
}
