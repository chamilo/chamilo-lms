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

use Xabbuh\XApi\Client\Api\StateApiClient;
use Xabbuh\XApi\DataFixtures\DocumentFixtures;
use Xabbuh\XApi\Model\Activity;
use Xabbuh\XApi\Model\Agent;
use Xabbuh\XApi\Model\InverseFunctionalIdentifier;
use Xabbuh\XApi\Model\IRI;
use Xabbuh\XApi\Model\State;
use Xabbuh\XApi\Model\StateDocument;
use Xabbuh\XApi\Serializer\Symfony\ActorSerializer;
use Xabbuh\XApi\Serializer\Symfony\DocumentDataSerializer;

/**
 * @author Christian Flothmann <christian.flothmann@xabbuh.de>
 */
class StateApiClientTest extends ApiClientTest
{
    /**
     * @var StateApiClient
     */
    private $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = new StateApiClient(
            $this->requestHandler,
            '1.0.1',
            new DocumentDataSerializer($this->serializer),
            new ActorSerializer($this->serializer)
        );
    }

    public function testCreateOrUpdateDocument()
    {
        $document = DocumentFixtures::getStateDocument();

        $this->validateStoreApiCall(
            'post',
            'activities/state',
            array(
                'activityId' => 'activity-id',
                'agent' => 'agent-as-json',
                'stateId' => 'state-id',
            ),
            204,
            '',
            $document->getData(),
            array(array('data' => $document->getState()->getActor(), 'result' => 'agent-as-json'))
        );

        $this->client->createOrUpdateDocument($document);
    }

    public function testCreateOrReplaceDocument()
    {
        $document = DocumentFixtures::getStateDocument();

        $this->validateStoreApiCall(
            'put',
            'activities/state',
            array(
                'activityId' => 'activity-id',
                'agent' => 'agent-as-json',
                'stateId' => 'state-id',
            ),
            204,
            '',
            $document->getData(),
            array(array('data' => $document->getState()->getActor(), 'result' => 'agent-as-json'))
        );

        $this->client->createOrReplaceDocument($document);
    }

    public function testDeleteDocument()
    {
        $state = $this->createState();

        $this->validateRequest(
            'delete',
            'activities/state',
            array(
                'activityId' => 'activity-id',
                'agent' => 'agent-as-json',
                'stateId' => 'state-id',
            ),
            ''
        );
        $this->validateSerializer(array(array('data' => $state->getActor(), 'result' => 'agent-as-json')));

        $this->client->deleteDocument($state);
    }

    public function testGetDocument()
    {
        $document = DocumentFixtures::getStateDocument();
        $state = $document->getState();

        $this->validateRetrieveApiCall(
            'get',
            'activities/state',
            array(
                'activityId' => 'activity-id',
                'agent' => 'agent-as-json',
                'stateId' => 'state-id',
            ),
            200,
            'DocumentData',
            $document->getData(),
            array(array('data' => $state->getActor(), 'result' => 'agent-as-json'))
        );

        $document = $this->client->getDocument($state);

        $this->assertInstanceOf(StateDocument::class, $document);
        $this->assertEquals($state, $document->getState());
    }

    private function createState()
    {
        $agent = new Agent(InverseFunctionalIdentifier::withMbox(IRI::fromString('mailto:alice@example.com')));
        $activity = new Activity(IRI::fromString('activity-id'));
        $state = new State($activity, $agent, 'state-id');

        return $state;
    }
}
