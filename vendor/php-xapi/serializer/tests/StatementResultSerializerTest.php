<?php

/*
 * This file is part of the xAPI package.
 *
 * (c) Christian Flothmann <christian.flothmann@xabbuh.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Xabbuh\XApi\Serializer\Tests;

use Xabbuh\XApi\Model\StatementResult;

/**
 * @author Christian Flothmann <christian.flothmann@xabbuh.de>
 */
abstract class StatementResultSerializerTest extends SerializerTest
{
    private $statementResultSerializer;

    protected function setUp()
    {
        $this->statementResultSerializer = $this->createStatementResultSerializer();
    }

    /**
     * @dataProvider serializeData
     */
    public function testSerializeStatementResult(StatementResult $statementResult, $expectedJson)
    {
        $this->assertJsonStringEqualsJsonString($expectedJson, $this->statementResultSerializer->serializeStatementResult($statementResult));
    }

    public function serializeData()
    {
        return $this->buildSerializeTestCases('StatementResult');
    }

    /**
     * @dataProvider deserializeData
     */
    public function testDeserializeStatementResult($json, StatementResult $expectedStatementResult)
    {
        $statementResult = $this->statementResultSerializer->deserializeStatementResult($json);

        $this->assertInstanceOf('Xabbuh\XApi\Model\StatementResult', $statementResult);

        $expectedStatements = $expectedStatementResult->getStatements();
        $statements = $statementResult->getStatements();

        $this->assertCount(count($expectedStatements), $statements, 'Statement result sets have the same size');

        foreach ($expectedStatements as $key => $expectedStatement) {
            $this->assertTrue($expectedStatement->equals($statements[$key]), 'Statements in result are the same');
        }

        if (null === $expectedStatementResult->getMoreUrlPath()) {
            $this->assertNull($statementResult->getMoreUrlPath(), 'The more URL path is null');
        } else {
            $this->assertTrue($expectedStatementResult->getMoreUrlPath()->equals($statementResult->getMoreUrlPath()), 'More URL paths are equal');
        }
    }

    public function deserializeData()
    {
        return $this->buildDeserializeTestCases('StatementResult');
    }

    abstract protected function createStatementResultSerializer();
}
