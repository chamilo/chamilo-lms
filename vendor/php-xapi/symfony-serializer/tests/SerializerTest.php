<?php

/*
 * This file is part of the xAPI package.
 *
 * (c) Christian Flothmann <christian.flothmann@xabbuh.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Xabbuh\XApi\Serializer\Symfony\Tests;

use PHPUnit\Framework\TestCase;
use Xabbuh\XApi\Model\Account;
use Xabbuh\XApi\Model\Activity;
use Xabbuh\XApi\Model\Actor;
use Xabbuh\XApi\Model\Attachment;
use Xabbuh\XApi\Model\Context;
use Xabbuh\XApi\Model\ContextActivities;
use Xabbuh\XApi\Model\Definition;
use Xabbuh\XApi\Model\Extensions;
use Xabbuh\XApi\Model\Interaction\InteractionComponent;
use Xabbuh\XApi\Model\Result;
use Xabbuh\XApi\Model\Score;
use Xabbuh\XApi\Model\StatementReference;
use Xabbuh\XApi\Model\SubStatement;
use Xabbuh\XApi\Model\Verb;
use Xabbuh\XApi\Serializer\Symfony\Serializer;

class SerializerTest extends TestCase
{
    private $serializer;

    protected function setUp()
    {
        $this->serializer = Serializer::createSerializer();
    }

    /**
     * @dataProvider serializeAccountData
     */
    public function testSerializeAccount(Account $account, $expectedJson)
    {
        $this->assertJsonStringEqualsJsonString($expectedJson, $this->serializer->serialize($account, 'json'));
    }

    public function serializeAccountData()
    {
        return $this->buildSerializeTestCases('Account');
    }

    /**
     * @dataProvider deserializeAccountData
     */
    public function testDeserializeAccount($json, Account $expectedAccount)
    {
        $account = $this->serializer->deserialize($json, 'Xabbuh\XApi\Model\Account', 'json');

        $this->assertInstanceOf('Xabbuh\XApi\Model\Account', $account);
        $this->assertTrue($expectedAccount->equals($account), 'Deserialized account has the expected properties');
    }

    public function deserializeAccountData()
    {
        return $this->buildDeserializeTestCases('Account');
    }

    /**
     * @dataProvider serializeActorData
     */
    public function testSerializeActor(Actor $actor, $expectedJson)
    {
        $this->assertJsonStringEqualsJsonString($expectedJson, $this->serializer->serialize($actor, 'json'));
    }

    public function serializeActorData()
    {
        return $this->buildSerializeTestCases('Actor');
    }

    /**
     * @dataProvider deserializeActorData
     */
    public function testDeserializeActor($json, Actor $expectedActor)
    {
        $actor = $this->serializer->deserialize($json, 'Xabbuh\XApi\Model\Actor', 'json');

        $this->assertInstanceOf('Xabbuh\XApi\Model\Actor', $actor);
        $this->assertTrue($expectedActor->equals($actor), 'Deserialized actor has the expected properties');
    }

    public function deserializeActorData()
    {
        return $this->buildDeserializeTestCases('Actor');
    }

    /**
     * @dataProvider serializeActivityData
     */
    public function testSerializeActivity(Activity $activity, $expectedJson)
    {
        $this->assertJsonStringEqualsJsonString($expectedJson, $this->serializer->serialize($activity, 'json'));
    }

    public function serializeActivityData()
    {
        return $this->buildSerializeTestCases('Activity');
    }

    /**
     * @dataProvider deserializeActivityData
     */
    public function testDeserializeActivity($json, Activity $expectedActivity)
    {
        $activity = $this->serializer->deserialize($json, 'Xabbuh\XApi\Model\Activity', 'json');

        $this->assertInstanceOf('Xabbuh\XApi\Model\Activity', $activity);
        $this->assertTrue($expectedActivity->equals($activity), 'Deserialized activity has the expected properties');
    }

    public function deserializeActivityData()
    {
        return $this->buildDeserializeTestCases('Activity');
    }

    /**
     * @dataProvider serializeAttachmentData
     */
    public function testSerializeAttachment(Attachment $attachment, $expectedJson)
    {
        $this->assertJsonStringEqualsJsonString($expectedJson, $this->serializer->serialize($attachment, 'json'));
    }

    public function serializeAttachmentData()
    {
        $tests = array();

        foreach (get_class_methods('Xabbuh\XApi\DataFixtures\AttachmentFixtures') as $method) {
            if (false !== strpos($method, 'ForQuery')) {
                continue;
            }

            $jsonFixture = json_decode(call_user_func(array('XApi\Fixtures\Json\AttachmentJsonFixtures', $method)));

            $tests[$method] = array(
                call_user_func(array('Xabbuh\XApi\DataFixtures\AttachmentFixtures', $method)),
                json_encode($jsonFixture->metadata),
            );
        }

        return $tests;
    }

    /**
     * @dataProvider deserializeAttachmentData
     */
    public function testDeserializeAttachment($json, $content, Attachment $expectedAttachment)
    {
        $context = array();

        if (null !== $content) {
            $context['xapi_attachments'] = array(
                hash('sha256', $content) => array(
                    'content' => $content,
                )
            );
        }

        $attachment = $this->serializer->deserialize($json, 'Xabbuh\XApi\Model\Attachment', 'json', $context);

        $this->assertInstanceOf('Xabbuh\XApi\Model\Attachment', $attachment);
        $this->assertTrue($expectedAttachment->equals($attachment), 'Deserialized attachment has the expected properties');
    }

    public function deserializeAttachmentData()
    {
        $tests = array();

        foreach (get_class_methods('XApi\Fixtures\Json\AttachmentJsonFixtures') as $method) {
            $jsonFixture = json_decode(call_user_func(array('XApi\Fixtures\Json\AttachmentJsonFixtures', $method)));
            $tests[$method] = array(
                json_encode($jsonFixture->metadata),
                isset($jsonFixture->content) ? $jsonFixture->content : null,
                call_user_func(array('Xabbuh\XApi\DataFixtures\AttachmentFixtures', $method)),
            );
        }

        return $tests;
    }

    /**
     * @dataProvider serializeContextData
     */
    public function testSerializeContext(Context $context, $expectedJson)
    {
        $this->assertJsonStringEqualsJsonString($expectedJson, $this->serializer->serialize($context, 'json'));
    }

    public function serializeContextData()
    {
        return $this->buildSerializeTestCases('Context');
    }

    /**
     * @dataProvider deserializeContextData
     */
    public function testDeserializeContext($json, Context $expectedContext)
    {
        $context = $this->serializer->deserialize($json, 'Xabbuh\XApi\Model\Context', 'json');

        $this->assertInstanceOf('Xabbuh\XApi\Model\Context', $context);
        $this->assertTrue($expectedContext == $context, 'Deserialized context has the expected properties');
    }

    public function deserializeContextData()
    {
        return $this->buildDeserializeTestCases('Context');
    }

    /**
     * @dataProvider serializeContextActivitiesData
     */
    public function testSerializeContextActivities(ContextActivities $contextActivities, $expectedJson)
    {
        $this->assertJsonStringEqualsJsonString($expectedJson, $this->serializer->serialize($contextActivities, 'json'));
    }

    public function serializeContextActivitiesData()
    {
        return $this->buildSerializeTestCases('ContextActivities');
    }

    /**
     * @dataProvider deserializeContextActivitiesData
     */
    public function testDeserializeContextActivities($json, ContextActivities $expectedContextActivities)
    {
        $contextActivities = $this->serializer->deserialize($json, 'Xabbuh\XApi\Model\ContextActivities', 'json');

        $this->assertInstanceOf('Xabbuh\XApi\Model\ContextActivities', $contextActivities);
        $this->assertTrue($expectedContextActivities == $contextActivities, 'Deserialized context activities have the expected properties');
    }

    public function deserializeContextActivitiesData()
    {
        return $this->buildDeserializeTestCases('ContextActivities');
    }

    /**
     * @dataProvider serializeDefinitionData
     */
    public function testSerializeDefinition(Definition $definition, $expectedJson)
    {
        $this->assertJsonStringEqualsJsonString($expectedJson, $this->serializer->serialize($definition, 'json'));
    }

    public function serializeDefinitionData()
    {
        return $this->buildSerializeTestCases('Definition');
    }

    /**
     * @dataProvider deserializeDefinitionData
     */
    public function testDeserializeDefinition($json, Definition $expectedDefinition)
    {
        $expectedClass = get_class($expectedDefinition);
        $definition = $this->serializer->deserialize($json, $expectedClass, 'json');

        $this->assertSame($expectedClass, get_class($definition), sprintf('Deserialized definition is an instance of "%s"', $expectedClass));
        $this->assertTrue($expectedDefinition->equals($definition), 'Deserialized definition has the expected properties');
    }

    public function deserializeDefinitionData()
    {
        return $this->buildDeserializeTestCases('Definition');
    }

    /**
     * @dataProvider serializeExtensionsData
     */
    public function testSerializeExtensions(Extensions $extensions, $expectedJson)
    {
        $this->assertJsonStringEqualsJsonString($expectedJson, $this->serializer->serialize($extensions, 'json'));
    }

    public function serializeExtensionsData()
    {
        return $this->buildSerializeTestCases('Extensions');
    }

    /**
     * @dataProvider deserializeExtensionsData
     */
    public function testDeserializeExtensions($json, Extensions $expectedExtensions)
    {
        $extensions = $this->serializer->deserialize($json, 'Xabbuh\XApi\Model\Extensions', 'json');

        $this->assertInstanceOf('Xabbuh\XApi\Model\Extensions', $extensions);
        $this->assertTrue($expectedExtensions->equals($expectedExtensions), 'Deserialized extensions have the expected properties');
    }

    public function deserializeExtensionsData()
    {
        return $this->buildDeserializeTestCases('Extensions');
    }

    /**
     * @dataProvider serializeInteractionComponentData
     */
    public function testSerializeInteractionComponent(InteractionComponent $interactionComponent, $expectedJson)
    {
        $this->assertJsonStringEqualsJsonString($expectedJson, $this->serializer->serialize($interactionComponent, 'json'));
    }

    public function serializeInteractionComponentData()
    {
        return $this->buildSerializeTestCases('InteractionComponent');
    }

    /**
     * @dataProvider deserializeInteractionComponentData
     */
    public function testDeserializeInteractionComponent($json, InteractionComponent $expectedInteractionComponent)
    {
        $interactionComponent = $this->serializer->deserialize($json, 'Xabbuh\XApi\Model\Interaction\InteractionComponent', 'json');

        $this->assertInstanceOf('Xabbuh\XApi\Model\Interaction\InteractionComponent', $interactionComponent);
        $this->assertTrue($expectedInteractionComponent->equals($interactionComponent), 'Deserialized interaction component has the expected properties');
    }

    public function deserializeInteractionComponentData()
    {
        return $this->buildDeserializeTestCases('InteractionComponent');
    }

    /**
     * @dataProvider serializeResultData
     */
    public function testSerializeResult(Result $result, $expectedJson)
    {
        $this->assertJsonStringEqualsJsonString($expectedJson, $this->serializer->serialize($result, 'json'));
    }

    public function serializeResultData()
    {
        return $this->buildSerializeTestCases('Result');
    }

    /**
     * @dataProvider deserializeResultData
     */
    public function testDeserializeResult($json, Result $expectedResult)
    {
        $result = $this->serializer->deserialize($json, 'Xabbuh\XApi\Model\Result', 'json');

        $this->assertInstanceOf('Xabbuh\XApi\Model\Result', $result);
        $this->assertTrue($expectedResult->equals($result), 'Deserialized result has the expected properties');
    }

    public function deserializeResultData()
    {
        return $this->buildDeserializeTestCases('Result');
    }

    /**
     * @dataProvider serializeScoreData
     */
    public function testSerializeScore(Score $score, $expectedJson)
    {
        $this->assertJsonStringEqualsJsonString($expectedJson, $this->serializer->serialize($score, 'json'));
    }

    public function serializeScoreData()
    {
        return $this->buildSerializeTestCases('Score');
    }

    /**
     * @dataProvider deserializeScoreData
     */
    public function testDeserializeScore($json, Score $expectedScore)
    {
        $score = $this->serializer->deserialize($json, 'Xabbuh\XApi\Model\Score', 'json');

        $this->assertInstanceOf('Xabbuh\XApi\Model\Score', $score);
        $this->assertTrue($expectedScore->equals($score), 'Deserialized score has the expected properties');
    }

    public function deserializeScoreData()
    {
        return $this->buildDeserializeTestCases('Score');
    }

    /**
     * @dataProvider serializeStatementReferenceData
     */
    public function testSerializeStatementReference(StatementReference $statementReference, $expectedJson)
    {
        $this->assertJsonStringEqualsJsonString($expectedJson, $this->serializer->serialize($statementReference, 'json'));
    }

    public function serializeStatementReferenceData()
    {
        return $this->buildSerializeTestCases('StatementReference');
    }

    /**
     * @dataProvider deserializeStatementReferenceData
     */
    public function testDeserializeStatementReference($json, StatementReference $expectedStatementReference)
    {
        $statementReference = $this->serializer->deserialize($json, 'Xabbuh\XApi\Model\StatementReference', 'json');

        $this->assertInstanceOf('Xabbuh\XApi\Model\StatementReference', $statementReference);
        $this->assertTrue($expectedStatementReference->equals($statementReference), 'Deserialized StatementReference has the expected properties');
    }

    public function deserializeStatementReferenceData()
    {
        return $this->buildDeserializeTestCases('StatementReference');
    }

    /**
     * @dataProvider serializeSubStatementData
     */
    public function testSerializeSubStatement(SubStatement $subStatement, $expectedJson)
    {
        $this->assertJsonStringEqualsJsonString($expectedJson, $this->serializer->serialize($subStatement, 'json'));
    }

    public function serializeSubStatementData()
    {
        return $this->buildSerializeTestCases('SubStatement');
    }

    /**
     * @dataProvider deserializeSubStatementData
     */
    public function testDeserializeSubStatement($json, SubStatement $expectedSubStatement)
    {
        $subStatement = $this->serializer->deserialize($json, 'Xabbuh\XApi\Model\SubStatement', 'json');

        $this->assertInstanceOf('Xabbuh\XApi\Model\SubStatement', $subStatement);
        $this->assertTrue($expectedSubStatement->equals($subStatement), 'Deserialized SubStatement has the expected properties');
    }

    public function deserializeSubStatementData()
    {
        return $this->buildDeserializeTestCases('SubStatement');
    }

    /**
     * @dataProvider serializeVerbData
     */
    public function testSerializeVerb(Verb $verb, $expectedJson)
    {
        $this->assertJsonStringEqualsJsonString($expectedJson, $this->serializer->serialize($verb, 'json'));
    }

    public function serializeVerbData()
    {
        return $this->buildSerializeTestCases('Verb');
    }

    /**
     * @dataProvider deserializeVerbData
     */
    public function testDeserializeVerb($json, Verb $expectedVerb)
    {
        $verb = $this->serializer->deserialize($json, 'Xabbuh\XApi\Model\Verb', 'json');

        $this->assertInstanceOf('Xabbuh\XApi\Model\Verb', $verb);
        $this->assertTrue($expectedVerb->equals($verb), 'Deserialized verb has the expected properties');
    }

    public function deserializeVerbData()
    {
        return $this->buildDeserializeTestCases('Verb');
    }

    private function buildSerializeTestCases($objectType)
    {
        $tests = array();

        $phpFixturesClass = 'Xabbuh\XApi\DataFixtures\\'.$objectType.'Fixtures';
        $jsonFixturesClass = 'XApi\Fixtures\Json\\'.$objectType.'JsonFixtures';
        $jsonFixturesMethods = get_class_methods($jsonFixturesClass);

        foreach (get_class_methods($phpFixturesClass) as $method) {
            if (false !== strpos($method, 'ForQuery')) {
                continue;
            }

            // serialized data will always contain type information
            if (in_array($method.'WithType', $jsonFixturesMethods)) {
                $jsonMethod = $method.'WithType';
            } else {
                $jsonMethod = $method;
            }

            $tests[$method] = array(
                call_user_func(array($phpFixturesClass, $method)),
                call_user_func(array($jsonFixturesClass, $jsonMethod)),
            );
        }

        return $tests;
    }

    private function buildDeserializeTestCases($objectType)
    {
        $tests = array();

        $jsonFixturesClass = 'XApi\Fixtures\Json\\'.$objectType.'JsonFixtures';
        $phpFixturesClass = 'Xabbuh\XApi\DataFixtures\\'.$objectType.'Fixtures';

        foreach (get_class_methods($jsonFixturesClass) as $method) {
            // PHP objects do not contain the type information as a dedicated property
            if ('WithType' === substr($method, -8)) {
                continue;
            }

            $tests[$method] = array(
                call_user_func(array($jsonFixturesClass, $method)),
                call_user_func(array($phpFixturesClass, $method)),
            );
        }

        return $tests;
    }
}
