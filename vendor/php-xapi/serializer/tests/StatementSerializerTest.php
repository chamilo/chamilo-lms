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

use Xabbuh\XApi\DataFixtures\StatementFixtures;
use Xabbuh\XApi\Model\Statement;
use XApi\Fixtures\Json\StatementJsonFixtures;

/**
 * @author Christian Flothmann <christian.flothmann@xabbuh.de>
 */
abstract class StatementSerializerTest extends SerializerTest
{
    private $statementSerializer;

    protected function setUp()
    {
        $this->statementSerializer = $this->createStatementSerializer();
    }

    /**
     * @dataProvider serializeData
     */
    public function testSerializeStatement(Statement $statement, $expectedJson)
    {
        $this->assertJsonStringEqualsJsonString($expectedJson, $this->statementSerializer->serializeStatement($statement));
    }

    public function serializeData()
    {
        $testCases = array();

        foreach ($this->buildSerializeTestCases('Statement') as $fixtures) {
            if ($fixtures[0] instanceof Statement) {
                if ($fixtures[0]->getVerb()->isVoidVerb()) {
                    $fixtures[0] = StatementFixtures::getVoidingStatement(StatementFixtures::DEFAULT_STATEMENT_ID);
                }

                $testCases[] = $fixtures;
            }
        }

        return $testCases;
    }

    /**
     * @dataProvider deserializeData
     */
    public function testDeserializeStatement($json, Statement $expectedStatement)
    {
        $attachments = array();

        if (null !== $expectedStatement->getAttachments()) {
            foreach ($expectedStatement->getAttachments() as $attachment) {
                $attachments[$attachment->getSha2()] = array(
                    'type' => $attachment->getContentType(),
                    'content' => $attachment->getContent(),
                );
            }
        }

        $statement = $this->statementSerializer->deserializeStatement($json, $attachments);

        $this->assertInstanceOf('Xabbuh\XApi\Model\Statement', $statement);
        $this->assertTrue($expectedStatement->equals($statement));
    }

    public function deserializeData()
    {
        $testCases = array();

        foreach ($this->buildDeserializeTestCases('Statement') as $fixtures) {
            if ($fixtures[1] instanceof Statement) {
                if ($fixtures[1]->getVerb()->isVoidVerb()) {
                    $fixtures[1] = StatementFixtures::getVoidingStatement(StatementFixtures::DEFAULT_STATEMENT_ID);
                }

                $testCases[] = $fixtures;
            }
        }

        return $testCases;
    }

    public function testDeserializeStatementCollection()
    {
        /** @var \Xabbuh\XApi\Model\Statement[] $statements */
        $statements = $this->statementSerializer->deserializeStatements(
            StatementJsonFixtures::getStatementCollection()
        );
        $expectedCollection = StatementFixtures::getStatementCollection();

        $this->assertSame(count($expectedCollection), count($statements));

        foreach ($expectedCollection as $index => $expectedStatement) {
            $this->assertTrue($expectedStatement->equals($statements[$index]));
        }
    }

    abstract protected function createStatementSerializer();
}
