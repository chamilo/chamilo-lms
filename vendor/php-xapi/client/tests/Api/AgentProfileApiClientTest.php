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

use Xabbuh\XApi\Client\Api\AgentProfileApiClient;
use Xabbuh\XApi\DataFixtures\DocumentFixtures;
use Xabbuh\XApi\Model\Agent;
use Xabbuh\XApi\Model\AgentProfile;
use Xabbuh\XApi\Model\AgentProfileDocument;
use Xabbuh\XApi\Model\InverseFunctionalIdentifier;
use Xabbuh\XApi\Model\IRI;
use Xabbuh\XApi\Serializer\Symfony\ActorSerializer;
use Xabbuh\XApi\Serializer\Symfony\DocumentDataSerializer;

/**
 * @author Christian Flothmann <christian.flothmann@xabbuh.de>
 */
class AgentProfileApiClientTest extends ApiClientTest
{
    /**
     * @var AgentProfileApiClient
     */
    private $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = new AgentProfileApiClient(
            $this->requestHandler,
            '1.0.1',
            new DocumentDataSerializer($this->serializer),
            new ActorSerializer($this->serializer)
        );
    }

    public function testCreateOrUpdateDocument()
    {
        $document = DocumentFixtures::getAgentProfileDocument();
        $profile = $document->getAgentProfile();

        $this->validateStoreApiCall(
            'post',
            'agents/profile',
            array(
                'agent' => 'agent-as-json',
                'profileId' => 'profile-id',
            ),
            204,
            '',
            $document->getData(),
            array(array('data' => $profile->getAgent(), 'result' => 'agent-as-json'))
        );

        $this->client->createOrUpdateDocument($document);
    }

    public function testCreateOrReplaceDocument()
    {
        $document = DocumentFixtures::getAgentProfileDocument();
        $profile = $document->getAgentProfile();

        $this->validateStoreApiCall(
            'put',
            'agents/profile',
            array(
                'agent' => 'agent-as-json',
                'profileId' => 'profile-id',
            ),
            204,
            '',
            $document->getData(),
            array(array('data' => $profile->getAgent(), 'result' => 'agent-as-json'))
        );

        $this->client->createOrReplaceDocument($document);
    }

    public function testDeleteDocument()
    {
        $profile = $this->createAgentProfile();

        $this->validateRequest(
            'delete',
            'agents/profile',
            array(
                'agent' => 'agent-as-json',
                'profileId' => 'profile-id',
            ),
            ''
        );
        $this->validateSerializer(array(array('data' => $profile->getAgent(), 'result' => 'agent-as-json')));

        $this->client->deleteDocument(
            $profile
        );
    }

    public function testGetDocument()
    {
        $document = DocumentFixtures::getAgentProfileDocument();
        $profile = $document->getAgentProfile();

        $this->validateRetrieveApiCall(
            'get',
            'agents/profile',
            array(
                'agent' => 'agent-as-json',
                'profileId' => 'profile-id',
            ),
            200,
            'DocumentData',
            $document->getData(),
            array(array('data' => $profile->getAgent(), 'result' => 'agent-as-json'))
        );

        $document = $this->client->getDocument($profile);

        $this->assertInstanceOf(AgentProfileDocument::class, $document);
        $this->assertEquals($profile, $document->getAgentProfile());
    }

    private function createAgentProfile()
    {
        $agent = new Agent(InverseFunctionalIdentifier::withMbox(IRI::fromString('mailto:christian@example.com')));
        $profile = new AgentProfile('profile-id', $agent);

        return $profile;
    }
}
